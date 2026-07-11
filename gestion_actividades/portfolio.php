<?php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/portfolio.php'));
$PAGE->set_title('Portafolio de certificados');
$PAGE->set_heading('Portafolio de certificados');

$canmanage = has_capability('local/gestion_actividades:manage', $context);

function local_ga_portfolio_type_badge(string $text, string $class = 'badge badge-secondary'): string {
    return html_writer::span(s($text), $class, ['style' => 'font-size:0.85rem;padding:6px 9px;']);
}

function local_ga_portfolio_status_badge(string $status): string {
    $status = trim($status);
    if ($status === '' || $status === 'generated') {
        return html_writer::span('Generado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'validated') {
        return html_writer::span('Validado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'pending') {
        return html_writer::span('Pendiente', 'badge badge-warning', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'rejected') {
        return html_writer::span('Rechazado', 'badge badge-danger', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    return html_writer::span(s($status), 'badge badge-secondary', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
}

$certificates = [];
if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_certificates'))) {
    $certificates = $DB->get_records_sql(
        "SELECT c.*, w.name AS workshopname, w.code AS workshopcode, w.hours, e.name AS editionname, e.sessiondate, co.fullname AS coursename
           FROM {local_ga_certificates} c
           JOIN {local_ga_workshops} w ON w.id = c.workshopid
           JOIN {local_ga_workshop_editions} e ON e.id = c.editionid
           JOIN {course} co ON co.id = c.courseid
          WHERE c.userid = :userid
       ORDER BY c.timeissued DESC, c.id DESC",
        ['userid' => (int)$USER->id]
    );
}

$totalhoursa = 0.0;
foreach ($certificates as $cert) {
    $totalhoursa += !empty($cert->hours) ? (float)$cert->hours : 0.0;
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Portafolio de certificados');

echo html_writer::start_div('mb-3');
if ($canmanage) {
    echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php'), 'Portafolio gestor', ['class' => 'btn btn-secondary']);
    echo ' ';
}
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), 'Volver al panel', ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo html_writer::start_div('row');

echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Talleres Tipo A', ['class' => 'card-title']);
echo html_writer::tag('p', 'Certificados generados por el sistema cuando el taller está completado.', ['class' => 'card-text']);
echo html_writer::tag('div', '<strong>' . count($certificates) . '</strong> certificados · <strong>' . round($totalhoursa, 2) . '</strong> horas', ['class' => 'alert alert-success']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card h-100');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Talleres Tipo B', ['class' => 'card-title']);
echo html_writer::tag('p', 'Aquí se almacenarán los certificados subidos por el alumno. Esta fase queda preparada para activarla más adelante.', ['class' => 'card-text']);
echo html_writer::tag('div', 'Próximamente: subir certificado, estado pendiente, validado o rechazado.', ['class' => 'alert alert-secondary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::tag('h3', 'Certificados Tipo A');
if (!$certificates) {
    echo $OUTPUT->notification('Todavía no tienes certificados generados en el portafolio.', 'info');
} else {
    $table = new html_table();
    $table->head = ['Tipo', 'Curso', 'Taller', 'Edición / fecha', 'Horas', 'Estado', 'Certificado'];
    foreach ($certificates as $cert) {
        $date = !empty($cert->sessiondate) ? userdate((int)$cert->sessiondate, get_string('strftimedatefullshort', 'langconfig')) : '-';
        $download = new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $cert->id]);
        $table->data[] = [
            local_ga_portfolio_type_badge('Tipo A', 'badge badge-primary'),
            format_string($cert->coursename),
            s($cert->workshopcode . ' - ' . $cert->workshopname),
            s($cert->editionname) . '<br><small>' . s($date) . '</small>',
            !empty($cert->hours) ? round((float)$cert->hours, 2) . ' h' : '-',
            local_ga_portfolio_status_badge((string)($cert->status ?? 'generated')),
            html_writer::link($download, 'Descargar certificado', ['class' => 'btn btn-primary btn-sm']),
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::tag('h3', 'Certificados Tipo B', ['class' => 'mt-4']);
echo $OUTPUT->notification('La subida de certificados Tipo B por parte del alumno se añadirá en una fase posterior. El portafolio ya reserva este espacio para separarlos de los certificados Tipo A.', 'info');

echo $OUTPUT->footer();
