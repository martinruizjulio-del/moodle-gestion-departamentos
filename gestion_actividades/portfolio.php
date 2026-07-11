<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;
use local_gestion_actividades\local\portfolio_typeb;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/portfolio.php'));
$PAGE->set_title('Portafolio de certificados');
$PAGE->set_heading('Portafolio de certificados');

function local_ga_portfolio_badge(string $status): string {
    if ($status === 'generated' || $status === 'validated') {
        return html_writer::span($status === 'generated' ? 'Generado' : 'Validado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'pending') {
        return html_writer::span('Pendiente de revisión', 'badge badge-warning', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'rejected') {
        return html_writer::span('Rechazado', 'badge badge-danger', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    return html_writer::span(s($status), 'badge badge-secondary', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
}

$typeacerts = method_exists(manager::class, 'list_user_certificates') ? manager::list_user_certificates((int)$USER->id) : [];
$typeahours = method_exists(manager::class, 'get_student_total_hours') ? manager::get_student_total_hours((int)$USER->id) : 0.0;
$typebcerts = portfolio_typeb::list_for_user((int)$USER->id);
$typebvalidatedhours = portfolio_typeb::total_validated_hours((int)$USER->id);
$typebuploadedhours = portfolio_typeb::total_uploaded_hours((int)$USER->id);
$totalvalidated = (float)$typeahours + (float)$typebvalidatedhours;

echo $OUTPUT->header();
echo $OUTPUT->heading('Portafolio de certificados');

echo html_writer::start_div('mb-3');
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_pdf_download.php'), 'Descargar mi portafolio en PDF', ['class' => 'btn btn-primary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/typeb_upload.php'), 'Subir certificado Tipo B', ['class' => 'btn btn-secondary']);
if (has_capability('local/gestion_actividades:manage', $context)) {
    echo ' ';
    echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php'), 'Abrir portafolio gestor', ['class' => 'btn btn-secondary']);
}
echo html_writer::end_div();

echo html_writer::start_div('row mb-3');
$cards = [
    ['Talleres Tipo A', round((float)$typeahours, 2) . ' h', 'Generadas por el sistema'],
    ['Talleres Tipo B validadas', round((float)$typebvalidatedhours, 2) . ' h', 'Subidas por el alumno y validadas'],
    ['Total reconocido', round((float)$totalvalidated, 2) . ' h', 'Tipo A + Tipo B validadas'],
];
foreach ($cards as $card) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', s($card[0]), ['class' => 'card-title']);
    echo html_writer::tag('div', s($card[1]), ['style' => 'font-size:2rem;font-weight:700;']);
    echo html_writer::tag('p', s($card[2]), ['class' => 'text-muted']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::tag('h2', 'Talleres Tipo A');
echo html_writer::tag('p', 'Certificados generados automáticamente por el sistema cuando se cumplen los requisitos del taller.', ['class' => 'text-muted']);
if ($typeacerts) {
    $table = new html_table();
    $table->head = ['Curso', 'Taller', 'Horas', 'Fecha de emisión', 'Estado', 'Acciones'];
    foreach ($typeacerts as $c) {
        $url = new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $c->id]);
        $table->data[] = [
            format_string($c->coursename),
            s($c->workshopcode . ' - ' . $c->workshopname),
            !empty($c->hours) ? s((float)$c->hours) . ' h' : '-',
            userdate((int)$c->timeissued),
            local_ga_portfolio_badge($c->status ?: 'generated'),
            html_writer::link($url, 'Descargar certificado', ['class' => 'btn btn-primary btn-sm']),
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('Todavía no tienes certificados Tipo A generados.', 'info');
}

echo html_writer::tag('h2', 'Talleres Tipo B', ['class' => 'mt-4']);
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('p', 'Aquí puedes subir certificados PDF de cursos o actividades externas. Debes indicar nombre, fecha, horas y confirmar que la actividad está autorizada para este tipo de talleres según la normativa.', ['class' => 'card-text']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/typeb_upload.php'), 'Subir certificado Tipo B', ['class' => 'btn btn-primary']);
echo ' ';
echo html_writer::span('Horas Tipo B subidas: ' . round((float)$typebuploadedhours, 2) . ' h · validadas: ' . round((float)$typebvalidatedhours, 2) . ' h', 'badge badge-light', ['style' => 'font-size:0.9rem;padding:9px 12px;']);
echo html_writer::end_div();
echo html_writer::end_div();

if ($typebcerts) {
    $table = new html_table();
    $table->head = ['Actividad', 'Fecha', 'Horas', 'Declaración normativa', 'Estado', 'Comentario', 'Acciones'];
    foreach ($typebcerts as $c) {
        $download = new moodle_url('/local/gestion_actividades/typeb_download.php', ['id' => $c->id]);
        $table->data[] = [
            s($c->activityname),
            !empty($c->activitydate) ? userdate((int)$c->activitydate, get_string('strftimedatefullshort', 'langconfig')) : '-',
            round((float)$c->hours, 2) . ' h',
            !empty($c->authorizedconfirm) ? 'Confirmada' : 'No confirmada',
            local_ga_portfolio_badge((string)$c->status),
            !empty($c->reviewcomment) ? s($c->reviewcomment) : '-',
            html_writer::link($download, 'Descargar certificado', ['class' => 'btn btn-secondary btn-sm']),
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('Todavía no has subido certificados Tipo B.', 'info');
}

echo $OUTPUT->footer();
