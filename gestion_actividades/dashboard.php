<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;
use local_gestion_actividades\local\portfolio_typeb;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

function local_ga_dash_card(string $title, string $text, moodle_url $url, string $button, string $classes = 'btn btn-primary'): string {
    return html_writer::start_div('col-md-6 col-xl-4 mb-3') .
        html_writer::start_div('card h-100 shadow-sm') .
        html_writer::start_div('card-body d-flex flex-column') .
        html_writer::tag('h3', $title, ['class' => 'h5 card-title']) .
        html_writer::tag('p', $text, ['class' => 'card-text text-muted flex-grow-1']) .
        html_writer::link($url, $button, ['class' => $classes]) .
        html_writer::end_div() . html_writer::end_div() . html_writer::end_div();
}

function local_ga_dash_status_badge(string $status): string {
    $status = trim($status);
    $class = 'badge badge-secondary';
    if (in_array($status, ['open', 'active', 'published'], true)) {
        $class = 'badge badge-success';
    } else if (in_array($status, ['draft', 'pending', 'future'], true)) {
        $class = 'badge badge-warning';
    } else if (in_array($status, ['closed', 'completed'], true)) {
        $class = 'badge badge-info';
    }
    return html_writer::span(s($status), $class, ['style' => 'font-size:0.82rem;padding:6px 9px;']);
}

$pendingtypeb = 0;
try {
    $pendingtypeb = portfolio_typeb::count_pending();
} catch (Throwable $e) {
    $pendingtypeb = 0;
}

$allrows = [];
$rows = [];
try {
    $allrows = manager::get_workshop_overview_rows();
    foreach ($allrows as $r) {
        if (!in_array($r->computedstatus, ['archived', 'past'], true)) {
            $rows[] = $r;
        }
    }
} catch (Throwable $e) {
    $rows = [];
}

$authorized = [];
try {
    $authorized = manager::get_authorized_managers();
} catch (Throwable $e) {
    $authorized = [];
}

echo $OUTPUT->header();

echo html_writer::start_div('mb-4 p-4 rounded', ['style' => 'background:#f7faf5;border:1px solid #dfe8d8;']);
echo html_writer::tag('h2', get_string('dashboard', 'local_gestion_actividades'), ['class' => 'mb-2', 'style' => 'color:#2b4b1e;']);
echo html_writer::tag('p', get_string('dashboardintro_seq', 'local_gestion_actividades'), ['class' => 'lead mb-0']);
echo html_writer::end_div();

if ($pendingtypeb > 0) {
    echo html_writer::div(
        html_writer::tag('strong', 'Atención: ') . 'hay ' . (int)$pendingtypeb . ' certificado(s) Tipo B pendiente(s) de validar. ' .
        html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php', ['status' => 'pending']), 'Revisar ahora', ['class' => 'btn btn-warning btn-sm ml-2']),
        'alert alert-warning'
    );
}

echo html_writer::start_div('row');
echo local_ga_dash_card('1. Alumnos y notas', 'Importación de alumnos, notas de expediente, ranking y convocatorias antiguas.', new moodle_url('/local/gestion_actividades/index.php'), 'Abrir alumnos y ranking');
echo local_ga_dash_card('2. Talleres Tipo A', 'Crear talleres, ediciones, plazas, profesorado, grupos, asistencia y certificados automáticos.', new moodle_url('/local/gestion_actividades/workshops.php'), 'Gestionar talleres Tipo A');
echo local_ga_dash_card('3. Portafolio y Tipo B', 'Revisar certificados Tipo B subidos por alumnos, validar/rechazar y descargar portafolios.', new moodle_url('/local/gestion_actividades/portfolio_admin.php'), 'Abrir portafolio gestor', 'btn btn-success');
echo local_ga_dash_card('4. Listados y descargas', 'Descargar listados de talleres, certificados Tipo A/B, portafolios y expedientes completos.', new moodle_url('/local/gestion_actividades/manager_downloads.php'), 'Abrir listados y descargas', 'btn btn-primary');
echo local_ga_dash_card('5. Informes de horas', 'Consultar horas reconocidas por alumno y revisar acumulados Tipo A y Tipo B.', new moodle_url('/local/gestion_actividades/hours_report.php'), 'Ver informe de horas', 'btn btn-secondary');
echo local_ga_dash_card('6. Archivo y configuración', 'Consultar talleres archivados y editar plantillas, portada del portafolio y usuarios autorizados.', new moodle_url('/local/gestion_actividades/archive.php'), 'Abrir archivo', 'btn btn-secondary');
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('workshopoverview', 'local_gestion_actividades'), ['class' => 'h4']);
echo html_writer::tag('p', 'Vista rápida de talleres y ediciones activas. Las acciones principales están agrupadas para evitar búsquedas innecesarias.', ['class' => 'text-muted']);

if (!$rows) {
    echo $OUTPUT->notification(get_string('noworkshopeditionsyet', 'local_gestion_actividades'), 'info');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-sm';
    $table->head = [
        get_string('status'),
        get_string('workshopcode', 'local_gestion_actividades'),
        get_string('workshopname', 'local_gestion_actividades'),
        get_string('editioncode', 'local_gestion_actividades'),
        get_string('workshophours', 'local_gestion_actividades'),
        get_string('date'),
        get_string('places', 'local_gestion_actividades'),
        get_string('enrolledstudents', 'local_gestion_actividades'),
        get_string('teachers', 'local_gestion_actividades'),
        get_string('actions'),
    ];

    foreach ($rows as $row) {
        $workshopid = $DB->get_field('local_ga_workshop_editions', 'workshopid', ['id' => $row->id]);
        $statuslabel = get_string('status_' . $row->computedstatus, 'local_gestion_actividades');
        $actions = html_writer::link(new moodle_url('/local/gestion_actividades/edition_edit.php', ['id' => $row->id, 'workshopid' => $workshopid]), get_string('edit'), ['class' => 'btn btn-secondary btn-sm']) . ' ' .
                   html_writer::link(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $row->id]), get_string('studentsmanualandstatus', 'local_gestion_actividades'), ['class' => 'btn btn-secondary btn-sm']) . ' ' .
                   html_writer::link(new moodle_url('/local/gestion_actividades/edition_sync.php', ['id' => $row->id]), get_string('synceditionenrolments', 'local_gestion_actividades'), ['class' => 'btn btn-outline-secondary btn-sm']);
        $table->data[] = [
            local_ga_dash_status_badge($statuslabel),
            s($row->workshopcode),
            format_string($row->workshopname),
            s($row->editioncode),
            isset($row->workshophours) && $row->workshophours !== null ? round($row->workshophours, 2) . ' h' : '-',
            $row->sessiondate ? manager::format_date_compact((int)$row->sessiondate) : '-',
            (int)$row->places,
            (int)$row->enrolledcount,
            $row->teachers ?: '-',
            $actions,
        ];
    }
    echo html_writer::table($table);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('authorizedusers', 'local_gestion_actividades'), ['class' => 'h4']);
echo html_writer::tag('p', get_string('authorizedusers_desc', 'local_gestion_actividades'), ['class' => 'text-muted']);
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/authorized_users.php'), get_string('manageauthorizedusers', 'local_gestion_actividades'), ['class' => 'btn btn-secondary mb-3']), '');
if (!$authorized) {
    echo $OUTPUT->notification(get_string('noauthorizedusers', 'local_gestion_actividades'), 'info');
} else {
    $authtable = new html_table();
    $authtable->attributes['class'] = 'generaltable table-sm';
    $authtable->head = [get_string('fullname'), 'Email', get_string('rolepermission', 'local_gestion_actividades')];
    foreach ($authorized as $authuser) {
        $authtable->data[] = [fullname($authuser), s($authuser->email), get_string('managepermission', 'local_gestion_actividades')];
    }
    echo html_writer::table($authtable);
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Accesos rápidos', ['class' => 'h4']);
echo html_writer::tag('p', 'Herramientas de certificados, portafolio y revisión administrativa.', ['class' => 'text-muted']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/certificate_template.php'), get_string('certificatetemplate', 'local_gestion_actividades'), ['class' => 'btn btn-secondary mb-2 mr-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_cover_template.php'), 'Portada del portafolio', ['class' => 'btn btn-secondary mb-2 mr-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/manager_downloads.php'), 'Listados y descargas', ['class' => 'btn btn-primary mb-2 mr-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_pdf_all.php', ['sesskey' => sesskey()]), 'Descargar todos los portafolios', ['class' => 'btn btn-secondary mb-2 mr-1']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/myhours.php'), get_string('openmyhours', 'local_gestion_actividades'), ['class' => 'btn btn-outline-secondary mb-2 mr-1']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('h4', get_string('recommendedflow', 'local_gestion_actividades'), ['class' => 'mt-4']);
echo html_writer::tag('ol',
    html_writer::tag('li', get_string('flow_students', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_workshops', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_editions', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_enrolments', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_attendance_activity_certificate', 'local_gestion_actividades'))
);

echo $OUTPUT->footer();
