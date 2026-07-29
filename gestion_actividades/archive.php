<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
if (!\local_gestion_actividades\local\manager::can_manage_globally((int)$USER->id)) {
    throw new required_capability_exception(context_system::instance(), 'local/gestion_actividades:manage', 'nopermissions', '');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/archive.php'));
$PAGE->set_title(get_string('workshoparchive', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

function local_ga_archive_type_badge(?string $type): string {
    $type = manager::normalize_workshop_type((string)($type ?? 'typea'));
    $label = $type === 'typeb' ? 'Tipo B' : 'Tipo A';
    $class = $type === 'typeb' ? 'badge badge-primary' : 'badge badge-success';
    return html_writer::span($label, $class, ['style' => 'font-size:0.82rem;padding:6px 9px;']);
}


if (optional_param('archive_due', 0, PARAM_BOOL) && confirm_sesskey()) {
    $count = manager::archive_due_workshop_editions();
    redirect(new moodle_url('/local/gestion_actividades/archive.php'), get_string('archiveduecount', 'local_gestion_actividades', $count));
}

echo $OUTPUT->header();
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver al panel', ['class' => 'btn btn-outline-secondary mb-3']), 'mb-2');

echo $OUTPUT->heading(get_string('workshoparchive', 'local_gestion_actividades'));

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/archive.php', ['archive_due' => 1, 'sesskey' => sesskey()]), get_string('archivedueworkshops', 'local_gestion_actividades'), ['class' => 'btn btn-primary']),
    'mb-3'
);

echo html_writer::tag('p', get_string('archiveintro', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

$rows = manager::get_workshop_overview_rows();
$archived = [];
foreach ($rows as $row) {
    if ($row->computedstatus === 'archived') {
        $archived[] = $row;
    }
}

if (!$archived) {
    echo $OUTPUT->notification(get_string('noarchivedworkshops', 'local_gestion_actividades'), 'info');
} else {
    $table = new html_table();
    $table->head = ['Tipo', get_string('status'), get_string('workshopcode', 'local_gestion_actividades'), get_string('workshopname', 'local_gestion_actividades'), get_string('editioncode', 'local_gestion_actividades'), get_string('date'), get_string('places', 'local_gestion_actividades'), get_string('enrolledstudents', 'local_gestion_actividades'), get_string('teachers', 'local_gestion_actividades'), get_string('group'), get_string('actions')];
    foreach ($archived as $row) {
        $actions = html_writer::link(
            new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $row->id]),
            get_string('studentsmanualandstatus', 'local_gestion_actividades'),
            ['class' => 'btn btn-secondary btn-sm mr-1 mb-1']
        );
        if (manager::normalize_workshop_type((string)($row->workshoptype ?? 'typea')) === 'typea') {
            $actions .= html_writer::link(
                new moodle_url('/local/gestion_actividades/teacher_view.php', [
                    'id' => (int)$row->workshopid,
                    'editionid' => (int)$row->id,
                ]),
                'Modificar notas',
                ['class' => 'btn btn-primary btn-sm mb-1']
            );
        }
        $table->data[] = [local_ga_archive_type_badge($row->workshoptype ?? 'typea'), get_string('status_' . $row->computedstatus, 'local_gestion_actividades'), s($row->workshopcode), format_string($row->workshopname), s($row->editioncode), $row->sessiondate ? manager::format_date_compact((int)$row->sessiondate) : '-', $row->places, $row->enrolledcount, $row->teachers ?: '-', $row->groupname ?: '-', $actions];
    }
    echo html_writer::table($table);
}

if (function_exists('local_gestion_actividades_enable_interactive_tables')) {
    local_gestion_actividades_enable_interactive_tables();
}
echo $OUTPUT->footer();
