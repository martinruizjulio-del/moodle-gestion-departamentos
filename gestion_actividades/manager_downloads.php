<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_gestion_actividades\local\manager;
use local_gestion_actividades\local\portfolio_pdf;
use local_gestion_actividades\local\portfolio_typeb;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

function local_ga_dl_clean(string $name, string $fallback = 'documento'): string {
    $name = clean_filename(trim($name));
    return $name !== '' ? $name : $fallback;
}

function local_ga_dl_send_csv(string $filename, array $headers, array $rows): void {
    \core\session\manager::write_close();
    @set_time_limit(0);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . clean_filename($filename) . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

function local_ga_dl_workshop_rows(): array {
    global $DB;
    if (!$DB->get_manager()->table_exists(new xmldb_table('local_ga_workshops'))) {
        return [];
    }
    $editionsql = $DB->get_manager()->table_exists(new xmldb_table('local_ga_workshop_editions'))
        ? "LEFT JOIN {local_ga_workshop_editions} e ON e.workshopid = w.id"
        : "";
    $editionfields = $editionsql !== ''
        ? "e.id AS editionid, e.name AS editionname, e.editioncode, e.sessiondate, e.enrolenddate, e.places, e.status AS editionstatus, e.archived"
        : "0 AS editionid, '' AS editionname, '' AS editioncode, 0 AS sessiondate, 0 AS enrolenddate, 0 AS places, '' AS editionstatus, 0 AS archived";
    $sql = "SELECT " . $DB->sql_concat('w.id', "'-'", 'COALESCE(e.id,0)') . " AS uniqid,
                   w.id AS workshopid, w.code, w.name AS workshopname, w.hours, c.fullname AS coursename,
                   $editionfields
              FROM {local_ga_workshops} w
         LEFT JOIN {course} c ON c.id = w.courseid
              $editionsql
          ORDER BY c.fullname ASC, w.code ASC, e.sessiondate ASC, e.id ASC";
    return $DB->get_records_sql($sql);
}

function local_ga_dl_typea_rows(): array {
    global $DB;
    if (!$DB->get_manager()->table_exists(new xmldb_table('local_ga_certificates'))) {
        return [];
    }
    $sql = "SELECT cert.id, cert.userid, cert.courseid, cert.workshopid, cert.editionid, cert.certcode, cert.filename,
                   cert.status, cert.timeissued,
                   u.firstname, u.lastname, u.email,
                   c.fullname AS coursename,
                   w.code AS workshopcode, w.name AS workshopname,
                   e.name AS editionname, e.editioncode
              FROM {local_ga_certificates} cert
         LEFT JOIN {user} u ON u.id = cert.userid
         LEFT JOIN {course} c ON c.id = cert.courseid
         LEFT JOIN {local_ga_workshops} w ON w.id = cert.workshopid
         LEFT JOIN {local_ga_workshop_editions} e ON e.id = cert.editionid
          ORDER BY cert.timeissued ASC, u.lastname ASC, u.firstname ASC";
    return $DB->get_records_sql($sql);
}

function local_ga_dl_typeb_rows(): array {
    global $DB;
    portfolio_typeb::ensure_table();
    if (!$DB->get_manager()->table_exists(new xmldb_table('local_ga_typeb_certs'))) {
        return [];
    }
    $sql = "SELECT b.*, u.firstname, u.lastname, u.email
              FROM {local_ga_typeb_certs} b
         LEFT JOIN {user} u ON u.id = b.userid
          ORDER BY b.activitydate ASC, u.lastname ASC, u.firstname ASC, b.id ASC";
    return $DB->get_records_sql($sql);
}

function local_ga_dl_userids_with_portfolio(): array {
    global $DB;
    $userids = [];
    if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_certificates'))) {
        $rows = $DB->get_records_sql("SELECT DISTINCT userid FROM {local_ga_certificates} WHERE userid > 0");
        foreach ($rows as $r) { $userids[(int)$r->userid] = true; }
    }
    portfolio_typeb::ensure_table();
    if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_typeb_certs'))) {
        $rows = $DB->get_records_sql("SELECT DISTINCT userid FROM {local_ga_typeb_certs} WHERE userid > 0");
        foreach ($rows as $r) { $userids[(int)$r->userid] = true; }
    }
    return array_keys($userids);
}

function local_ga_dl_add_typea_file(stdClass $cert, string $zipname, string $tempdir, array &$files): void {
    global $DB;
    $course = $DB->get_record('course', ['id' => (int)$cert->courseid], '*', IGNORE_MISSING);
    if (!$course) { return; }
    $coursecontext = context_course::instance((int)$course->id, IGNORE_MISSING);
    if (!$coursecontext) { return; }
    $fs = get_file_storage();
    $filename = (string)($cert->filename ?? '');
    $file = $filename !== '' ? $fs->get_file($coursecontext->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, '/', $filename) : false;
    if (!$file || $file->is_directory()) {
        $area = $fs->get_area_files($coursecontext->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, 'filename', false);
        foreach ($area as $candidate) {
            if (!$candidate->is_directory()) { $file = $candidate; break; }
        }
    }
    if (!$file || $file->is_directory()) { return; }
    $path = $tempdir . '/typea_' . (int)$cert->id . '.pdf';
    $file->copy_content_to($path);
    $files[$zipname] = $path;
}

function local_ga_dl_add_typeb_file(stdClass $cert, string $zipname, string $tempdir, array &$files): void {
    $context = context_system::instance();
    $fs = get_file_storage();
    $filename = (string)($cert->filename ?? '');
    $file = $filename !== '' ? $fs->get_file($context->id, 'local_gestion_actividades', 'typeb_certificate', (int)$cert->id, '/', $filename) : false;
    if (!$file || $file->is_directory()) {
        $area = $fs->get_area_files($context->id, 'local_gestion_actividades', 'typeb_certificate', (int)$cert->id, 'filename', false);
        foreach ($area as $candidate) {
            if (!$candidate->is_directory()) { $file = $candidate; break; }
        }
    }
    if (!$file || $file->is_directory()) { return; }
    $path = $tempdir . '/typeb_' . (int)$cert->id . '.pdf';
    $file->copy_content_to($path);
    $files[$zipname] = $path;
}

function local_ga_dl_send_zip(array $files, string $zipname, string $emptyredirect): void {
    if (!$files) {
        redirect(new moodle_url($emptyredirect), 'No hay archivos para descargar.', null, \core\output\notification::NOTIFY_INFO);
    }
    $packer = get_file_packer('application/zip');
    $tempdir = make_request_directory();
    $zippath = $tempdir . '/' . clean_filename($zipname);
    $packer->archive_to_pathname($files, $zippath);
    send_temp_file($zippath, clean_filename($zipname));
}

if ($action !== '') {
    require_sesskey();

    if ($action === 'workshops_csv') {
        $rows = [];
        foreach (local_ga_dl_workshop_rows() as $r) {
            $rows[] = [
                $r->coursename ?? '', $r->workshopid, $r->code, $r->workshopname,
                isset($r->hours) ? (string)$r->hours : '', $r->editionid ?: '', $r->editioncode ?: '', $r->editionname ?: '',
                !empty($r->sessiondate) ? userdate((int)$r->sessiondate, '%Y-%m-%d %H:%M') : '',
                !empty($r->enrolenddate) ? userdate((int)$r->enrolenddate, '%Y-%m-%d %H:%M') : '',
                $r->places ?: '', $r->editionstatus ?: '', !empty($r->archived) ? 'Sí' : 'No',
            ];
        }
        local_ga_dl_send_csv('listado_talleres_tipo_a.csv', ['Curso', 'ID taller', 'Código taller', 'Taller', 'Horas', 'ID edición', 'Código edición', 'Edición', 'Fecha taller', 'Fin inscripción', 'Plazas', 'Estado', 'Archivado'], $rows);
    }

    if ($action === 'typea_csv') {
        $rows = [];
        foreach (local_ga_dl_typea_rows() as $c) {
            $rows[] = [
                fullname($c), $c->email ?? '', $c->coursename ?? '', $c->workshopcode ?? '', $c->workshopname ?? '',
                $c->editioncode ?? '', $c->editionname ?? '', $c->certcode ?? '', $c->status ?? '',
                !empty($c->timeissued) ? userdate((int)$c->timeissued, '%Y-%m-%d %H:%M') : '', $c->filename ?? '',
            ];
        }
        local_ga_dl_send_csv('listado_certificados_tipo_a.csv', ['Alumno', 'Email', 'Curso', 'Código taller', 'Taller', 'Código edición', 'Edición', 'Código certificado', 'Estado', 'Fecha emisión', 'Archivo'], $rows);
    }

    if ($action === 'typeb_csv') {
        $rows = [];
        foreach (local_ga_dl_typeb_rows() as $c) {
            $rows[] = [
                fullname($c), $c->email ?? '', $c->activityname ?? '',
                !empty($c->activitydate) ? userdate((int)$c->activitydate, '%Y-%m-%d') : '',
                isset($c->hours) ? (string)$c->hours : '', $c->status ?? '', !empty($c->authorizedconfirm) ? 'Sí' : 'No',
                $c->reviewcomment ?? '', !empty($c->timecreated) ? userdate((int)$c->timecreated, '%Y-%m-%d %H:%M') : '', $c->filename ?? '',
            ];
        }
        local_ga_dl_send_csv('listado_certificados_tipo_b.csv', ['Alumno', 'Email', 'Actividad', 'Fecha actividad', 'Horas', 'Estado', 'Declaración normativa', 'Comentario revisión', 'Fecha subida', 'Archivo'], $rows);
    }

    if ($action === 'typea_zip') {
        $tempdir = make_request_directory();
        $files = [];
        $n = 1;
        foreach (local_ga_dl_typea_rows() as $c) {
            $date = !empty($c->timeissued) ? userdate((int)$c->timeissued, '%Y%m%d') : 'sin_fecha';
            $student = local_ga_dl_clean(fullname($c), 'alumno');
            $title = local_ga_dl_clean(($c->workshopcode ?? 'tipo_a') . '_' . ($c->workshopname ?? 'certificado'), 'certificado');
            $zipname = sprintf('Certificados_Tipo_A/%03d_%s_%s_%s.pdf', $n++, $date, $student, $title);
            local_ga_dl_add_typea_file($c, $zipname, $tempdir, $files);
        }
        local_ga_dl_send_zip($files, 'certificados_tipo_a_' . date('Ymd_His') . '.zip', '/local/gestion_actividades/manager_downloads.php');
    }

    if ($action === 'typeb_zip') {
        $tempdir = make_request_directory();
        $files = [];
        $n = 1;
        foreach (local_ga_dl_typeb_rows() as $c) {
            $date = !empty($c->activitydate) ? userdate((int)$c->activitydate, '%Y%m%d') : 'sin_fecha';
            $student = local_ga_dl_clean(fullname($c), 'alumno');
            $title = local_ga_dl_clean($c->activityname ?? 'certificado_tipo_b', 'certificado_tipo_b');
            $zipname = sprintf('Certificados_Tipo_B/%03d_%s_%s_%s.pdf', $n++, $date, $student, $title);
            local_ga_dl_add_typeb_file($c, $zipname, $tempdir, $files);
        }
        local_ga_dl_send_zip($files, 'certificados_tipo_b_' . date('Ymd_His') . '.zip', '/local/gestion_actividades/manager_downloads.php');
    }

    if ($action === 'packages_zip') {
        $tempdir = make_request_directory();
        $files = [];
        foreach (local_ga_dl_userids_with_portfolio() as $userid) {
            $user = $DB->get_record('user', ['id' => (int)$userid, 'deleted' => 0], '*', IGNORE_MISSING);
            if (!$user) { continue; }
            $folder = local_ga_dl_clean(fullname($user), 'alumno_' . (int)$userid);
            $pdf = portfolio_pdf::render_pdf_string((int)$userid);
            $mainpath = $tempdir . '/portfolio_' . (int)$userid . '.pdf';
            file_put_contents($mainpath, $pdf);
            $files[$folder . '/00_portafolio_' . $folder . '.pdf'] = $mainpath;

            $typeacerts = method_exists(manager::class, 'list_user_certificates') ? manager::list_user_certificates((int)$userid) : [];
            usort($typeacerts, function($a, $b) { return ((int)($a->timeissued ?? 0)) <=> ((int)($b->timeissued ?? 0)); });
            $n = 1;
            foreach ($typeacerts as $c) {
                $date = !empty($c->timeissued) ? userdate((int)$c->timeissued, '%Y%m%d') : 'sin_fecha';
                $title = local_ga_dl_clean(($c->workshopcode ?? 'tipo_a') . '_' . ($c->workshopname ?? 'certificado'), 'certificado_tipo_a');
                local_ga_dl_add_typea_file($c, sprintf('%s/01_Tipo_A/%02d_%s_%s.pdf', $folder, $n++, $date, $title), $tempdir, $files);
            }

            $typebcerts = portfolio_typeb::list_for_user((int)$userid);
            usort($typebcerts, function($a, $b) { return ((int)($a->activitydate ?? 0)) <=> ((int)($b->activitydate ?? 0)); });
            $n = 1;
            foreach ($typebcerts as $c) {
                $date = !empty($c->activitydate) ? userdate((int)$c->activitydate, '%Y%m%d') : 'sin_fecha';
                $title = local_ga_dl_clean($c->activityname ?? 'certificado_tipo_b', 'certificado_tipo_b');
                local_ga_dl_add_typeb_file($c, sprintf('%s/02_Tipo_B/%02d_%s_%s.pdf', $folder, $n++, $date, $title), $tempdir, $files);
            }
        }
        local_ga_dl_send_zip($files, 'expedientes_completos_' . date('Ymd_His') . '.zip', '/local/gestion_actividades/manager_downloads.php');
    }
}

$workshoprows = local_ga_dl_workshop_rows();
$typearows = local_ga_dl_typea_rows();
$typebrows = local_ga_dl_typeb_rows();
$portfolioids = local_ga_dl_userids_with_portfolio();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/manager_downloads.php'));
$PAGE->set_title('Listados y descargas');
$PAGE->set_heading('Gestion_actividades');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Listados y descargas del gestor');
echo html_writer::tag('p', 'Acceso centralizado a todos los listados y descargas: talleres, certificados Tipo A, certificados Tipo B y portafolios.', ['class' => 'lead']);

echo html_writer::start_div('row');
$cards = [
    ['Talleres Tipo A', count($workshoprows) . ' fila(s)', 'Listado completo de talleres y ediciones.', 'workshops_csv', 'Descargar CSV'],
    ['Certificados Tipo A', count($typearows) . ' certificado(s)', 'Listado y descarga masiva de certificados generados por el sistema.', 'typea_csv', 'Descargar CSV'],
    ['Certificados Tipo B', count($typebrows) . ' certificado(s)', 'Listado y descarga masiva de PDFs subidos por alumnos.', 'typeb_csv', 'Descargar CSV'],
    ['Portafolios', count($portfolioids) . ' alumno(s)', 'Portafolios principales y expedientes completos.', '', ''],
];
foreach ($cards as $card) {
    echo html_writer::start_div('col-md-6 mb-3');
    echo html_writer::start_div('card h-100 shadow-sm');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', $card[0], ['class' => 'h4']);
    echo html_writer::tag('div', $card[1], ['class' => 'badge badge-light mb-2', 'style' => 'font-size:0.9rem;padding:8px 10px;']);
    echo html_writer::tag('p', $card[2], ['class' => 'text-muted']);
    if ($card[3] !== '') {
        echo html_writer::link(new moodle_url('/local/gestion_actividades/manager_downloads.php', ['action' => $card[3], 'sesskey' => sesskey()]), $card[4], ['class' => 'btn btn-primary mr-1 mb-1']);
    }
    if ($card[0] === 'Certificados Tipo A') {
        echo html_writer::link(new moodle_url('/local/gestion_actividades/manager_downloads.php', ['action' => 'typea_zip', 'sesskey' => sesskey()]), 'Descargar PDFs ZIP', ['class' => 'btn btn-secondary mb-1']);
    } else if ($card[0] === 'Certificados Tipo B') {
        echo html_writer::link(new moodle_url('/local/gestion_actividades/manager_downloads.php', ['action' => 'typeb_zip', 'sesskey' => sesskey()]), 'Descargar PDFs ZIP', ['class' => 'btn btn-secondary mb-1']);
    } else if ($card[0] === 'Portafolios') {
        echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_pdf_all.php', ['sesskey' => sesskey()]), 'Descargar portafolios PDF ZIP', ['class' => 'btn btn-primary mr-1 mb-1']);
        echo html_writer::link(new moodle_url('/local/gestion_actividades/manager_downloads.php', ['action' => 'packages_zip', 'sesskey' => sesskey()]), 'Descargar expedientes completos ZIP', ['class' => 'btn btn-secondary mb-1']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('card mt-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Accesos de revisión', ['class' => 'h4']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), 'Gestionar talleres', ['class' => 'btn btn-secondary mr-1 mb-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php'), 'Portafolio gestor', ['class' => 'btn btn-secondary mr-1 mb-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/hours_report.php'), 'Informe de horas', ['class' => 'btn btn-secondary mr-1 mb-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), 'Volver al panel', ['class' => 'btn btn-outline-secondary mb-1']);
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
