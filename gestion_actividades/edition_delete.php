<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$edition = manager::get_workshop_edition($id);
$workshop = manager::get_workshop($edition->workshopid);
$group = !empty($edition->groupid) ? $DB->get_record('groups', ['id' => $edition->groupid]) : null;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/edition_delete.php', ['id' => $id]));
$PAGE->set_title(get_string('deleteedition', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (data_submitted() && confirm_sesskey()) {
    manager::delete_workshop_edition($id, true);
    redirect(
        new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshop->id]),
        get_string('editiondeletedwithgroup', 'local_gestion_actividades')
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteedition', 'local_gestion_actividades') . ': ' . format_string($edition->name));

echo $OUTPUT->notification(get_string('deleteeditionwarning', 'local_gestion_actividades'), 'warning');

$table = new html_table();
$table->data = [
    [get_string('editioncode', 'local_gestion_actividades'), s($edition->editioncode)],
    [get_string('name'), format_string($edition->name)],
    [get_string('moodlegroupforenrolment', 'local_gestion_actividades'), $group ? format_string($group->name) : '-'],
    [get_string('groupmembers', 'local_gestion_actividades'), $group ? $DB->count_records('groups_members', ['groupid' => $group->id]) : 0],
];
echo html_writer::table($table);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-danger', 'value' => get_string('confirmdeleteedition', 'local_gestion_actividades')]);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshop->id]), get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
