<?php
require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$q = optional_param('q', '', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/portfolio_admin.php', ['q' => $q, 'userid' => $userid]));
$PAGE->set_title('Portafolio de certificados - gestor');
$PAGE->set_heading('Portafolio de certificados - gestor');

function local_ga_admin_portfolio_status(string $status): string {
    $status = trim($status);
    if ($status === '' || $status === 'generated') {
        return html_writer::span('Generado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'pending') {
        return html_writer::span('Pendiente', 'badge badge-warning', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'validated') {
        return html_writer::span('Validado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'rejected') {
        return html_writer::span('Rechazado', 'badge badge-danger', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    return html_writer::span(s($status), 'badge badge-secondary', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Portafolio de certificados - gestor');

echo html_writer::start_div('mb-3');
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio.php'), 'Mi portafolio', ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), 'Panel de gestión', ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
echo html_writer::start_div('input-group');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'q',
    'value' => s($q),
    'class' => 'form-control',
    'placeholder' => 'Buscar alumno por nombre, apellidos o email'
]);
echo html_writer::start_div('input-group-append');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Buscar', 'class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

if (!$DB->get_manager()->table_exists(new xmldb_table('local_ga_certificates'))) {
    echo $OUTPUT->notification('Todavía no existe la tabla de certificados. Ejecuta la actualización del plugin.', 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($userid > 0) {
    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id, firstname, lastname, email', MUST_EXIST);
    echo html_writer::tag('h3', 'Portafolio de ' . fullname($user));

    $certificates = $DB->get_records_sql(
        "SELECT c.*, w.name AS workshopname, w.code AS workshopcode, w.hours, e.name AS editionname, e.sessiondate, co.fullname AS coursename
           FROM {local_ga_certificates} c
           JOIN {local_ga_workshops} w ON w.id = c.workshopid
           JOIN {local_ga_workshop_editions} e ON e.id = c.editionid
           JOIN {course} co ON co.id = c.courseid
          WHERE c.userid = :userid
       ORDER BY c.timeissued DESC, c.id DESC",
        ['userid' => $userid]
    );

    $totalhours = 0.0;
    foreach ($certificates as $cert) {
        $totalhours += !empty($cert->hours) ? (float)$cert->hours : 0.0;
    }
    echo html_writer::tag('div', '<strong>Tipo A:</strong> ' . count($certificates) . ' certificados · <strong>' . round($totalhours, 2) . '</strong> horas', ['class' => 'alert alert-success']);

    if (!$certificates) {
        echo $OUTPUT->notification('Este alumno todavía no tiene certificados Tipo A generados.', 'info');
    } else {
        $table = new html_table();
        $table->head = ['Tipo', 'Curso', 'Taller', 'Edición / fecha', 'Horas', 'Estado', 'Acciones'];
        foreach ($certificates as $cert) {
            $date = !empty($cert->sessiondate) ? userdate((int)$cert->sessiondate, get_string('strftimedatefullshort', 'langconfig')) : '-';
            $download = new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $cert->id]);
            $actions = html_writer::link($download, 'Descargar', ['class' => 'btn btn-primary btn-sm']);
            if (file_exists(__DIR__ . '/regenerate_certificate.php')) {
                $actions .= ' ' . html_writer::link(new moodle_url('/local/gestion_actividades/regenerate_certificate.php', ['id' => $cert->id, 'sesskey' => sesskey()]), 'Regenerar PDF', ['class' => 'btn btn-warning btn-sm']);
            }
            $table->data[] = [
                html_writer::span('Tipo A', 'badge badge-primary', ['style' => 'font-size:0.85rem;padding:6px 9px;']),
                format_string($cert->coursename),
                s($cert->workshopcode . ' - ' . $cert->workshopname),
                s($cert->editionname) . '<br><small>' . s($date) . '</small>',
                !empty($cert->hours) ? round((float)$cert->hours, 2) . ' h' : '-',
                local_ga_admin_portfolio_status((string)($cert->status ?? 'generated')),
                $actions,
            ];
        }
        echo html_writer::table($table);
    }

    echo html_writer::tag('h4', 'Tipo B', ['class' => 'mt-4']);
    echo $OUTPUT->notification('Fase siguiente: aquí se revisarán los certificados Tipo B subidos por el alumno: pendiente, validado o rechazado.', 'info');
    echo $OUTPUT->footer();
    exit;
}

$params = [];
$where = '';
if (trim($q) !== '') {
    $like = '%' . $DB->sql_like_escape(trim($q)) . '%';
    $where = "WHERE " . $DB->sql_like('u.firstname', ':q1', false) .
        " OR " . $DB->sql_like('u.lastname', ':q2', false) .
        " OR " . $DB->sql_like('u.email', ':q3', false);
    $params = ['q1' => $like, 'q2' => $like, 'q3' => $like];
}

$rows = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, u.email,
            COUNT(c.id) AS certificatesa,
            COALESCE(SUM(w.hours), 0) AS hoursa,
            MAX(c.timeissued) AS lastissued
       FROM {local_ga_certificates} c
       JOIN {user} u ON u.id = c.userid
       JOIN {local_ga_workshops} w ON w.id = c.workshopid
       $where
   GROUP BY u.id, u.firstname, u.lastname, u.email
   ORDER BY u.lastname ASC, u.firstname ASC",
    $params,
    0,
    200
);

echo html_writer::tag('h3', 'Alumnos con certificados Tipo A');
if (!$rows) {
    echo $OUTPUT->notification('No se han encontrado alumnos con certificados generados.', 'info');
} else {
    $table = new html_table();
    $table->head = ['Alumno', 'Email', 'Certificados Tipo A', 'Horas Tipo A', 'Última emisión', 'Acciones'];
    foreach ($rows as $row) {
        $table->data[] = [
            fullname($row),
            s($row->email),
            (int)$row->certificatesa,
            round((float)$row->hoursa, 2) . ' h',
            !empty($row->lastissued) ? userdate((int)$row->lastissued) : '-',
            html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php', ['userid' => $row->id]), 'Ver portafolio', ['class' => 'btn btn-secondary btn-sm']),
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::tag('h3', 'Talleres Tipo B', ['class' => 'mt-4']);
echo $OUTPUT->notification('La tabla de revisión de certificados Tipo B se añadirá cuando activemos la subida por alumno. Esta pantalla ya queda separada para incorporarla sin mezclarla con Tipo A.', 'info');

echo $OUTPUT->footer();
