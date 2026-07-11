<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$workshop = manager::get_workshop($id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshop_delete.php', ['id' => $id]));
$PAGE->set_title(get_string('deleteworkshop', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (data_submitted() && confirm_sesskey()) {
    $summary = manager::delete_workshop($id);
    redirect(
        new moodle_url('/local/gestion_actividades/workshops.php'),
        get_string('workshopdeletedsummary', 'local_gestion_actividades', $summary)
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteworkshop', 'local_gestion_actividades') . ': ' . format_string($workshop->name));

echo $OUTPUT->notification(get_string('deleteworkshopwarning', 'local_gestion_actividades'), 'warning');

$table = new html_table();
$table->data = [
    [get_string('workshopcode', 'local_gestion_actividades'), s($workshop->code)],
    [get_string('workshopname', 'local_gestion_actividades'), format_string($workshop->name)],
    [get_string('workshophours', 'local_gestion_actividades'), isset($workshop->hours) && $workshop->hours !== null ? round((float)$workshop->hours, 2) . ' h' : '-'],
];
echo html_writer::table($table);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-danger', 'value' => get_string('confirmdeleteworkshop', 'local_gestion_actividades')]);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
