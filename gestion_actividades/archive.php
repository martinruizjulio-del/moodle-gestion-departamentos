<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/archive.php'));
$PAGE->set_title(get_string('workshoparchive', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (optional_param('archive_due', 0, PARAM_BOOL) && confirm_sesskey()) {
    $count = manager::archive_due_workshop_editions();
    redirect(new moodle_url('/local/gestion_actividades/archive.php'), get_string('archiveduecount', 'local_gestion_actividades', $count));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('workshoparchive', 'local_gestion_actividades'));

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), get_string('dashboard', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/archive.php', ['archive_due' => 1, 'sesskey' => sesskey()]), get_string('archivedueworkshops', 'local_gestion_actividades'), ['class' => 'btn btn-primary']),
    'mb-3'
);

echo html_writer::tag('p', get_string('archiveintro', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

$rows = manager::get_workshop_overview_rows();
$archived = [];
foreach ($rows as $row) {
    if ($row->computedstatus === 'archived' || $row->computedstatus === 'past') {
        $archived[] = $row;
    }
}

if (!$archived) {
    echo $OUTPUT->notification(get_string('noarchivedworkshops', 'local_gestion_actividades'), 'info');
} else {
    $table = new html_table();
    $table->head = [get_string('status'), get_string('workshopcode', 'local_gestion_actividades'), get_string('workshopname', 'local_gestion_actividades'), get_string('editioncode', 'local_gestion_actividades'), get_string('date'), get_string('places', 'local_gestion_actividades'), get_string('enrolledstudents', 'local_gestion_actividades'), get_string('teachers', 'local_gestion_actividades'), get_string('group'), get_string('actions')];
    foreach ($archived as $row) {
        $actions = html_writer::link(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $row->id]), get_string('studentsmanualandstatus', 'local_gestion_actividades'));
        $table->data[] = [get_string('status_' . $row->computedstatus, 'local_gestion_actividades'), s($row->workshopcode), format_string($row->workshopname), s($row->editioncode), $row->sessiondate ? manager::format_date_compact((int)$row->sessiondate) : '-', $row->places, $row->enrolledcount, $row->teachers ?: '-', $row->groupname ?: '-', $actions];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
