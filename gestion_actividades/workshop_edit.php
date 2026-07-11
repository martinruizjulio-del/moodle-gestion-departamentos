<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = optional_param('id', 0, PARAM_INT);
$record = $id ? manager::get_workshop($id) : null;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshop_edit.php', ['id' => $id]));
$PAGE->set_title(get_string('editworkshop', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (data_submitted() && confirm_sesskey()) {
    $data = (object)[
        'id' => optional_param('id', 0, PARAM_INT),
        'courseid' => required_param('courseid', PARAM_INT),
        'code' => optional_param('code', '', PARAM_ALPHANUMEXT),
        'name' => required_param('name', PARAM_TEXT),
        'description' => optional_param('description', '', PARAM_TEXT),
        'hours' => optional_param('hours', '', PARAM_TEXT),
        'sectionnum' => optional_param('sectionnum', $record->sectionnum ?? 0, PARAM_INT),
    ];
    manager::save_workshop($data);
    redirect(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('safemodeactive', 'local_gestion_actividades'), 'warning');

echo $OUTPUT->heading(get_string('editworkshop', 'local_gestion_actividades'));

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sectionnum', 'value' => $record->sectionnum ?? 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);

$courseoptions = manager::get_course_options();
echo html_writer::label(get_string('coursewhereworkshoplives', 'local_gestion_actividades'), 'courseid');
echo html_writer::select($courseoptions, 'courseid', $record->courseid ?? 0, false, ['class' => 'form-control mb-2', 'required' => 'required']);
echo html_writer::tag('div', get_string('coursewhereworkshoplives_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);

if ($id && !empty($record->code)) {
    echo html_writer::label(get_string('workshopcode', 'local_gestion_actividades'), 'codeinfo');
    echo html_writer::tag('div', s($record->code), ['class' => 'alert alert-secondary']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'code', 'value' => $record->code]);
} else {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'code', 'value' => '']);
    echo html_writer::tag('div', get_string('workshopcodeauto_help', 'local_gestion_actividades'), ['class' => 'alert alert-info']);
}

echo html_writer::label(get_string('workshopname', 'local_gestion_actividades'), 'name');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'class' => 'form-control mb-2', 'required' => 'required', 'value' => $record->name ?? '']);

$hoursvalue = isset($record->hours) && $record->hours !== null ? str_replace('.', ',', (string)$record->hours) : '';
echo html_writer::label(get_string('workshophours', 'local_gestion_actividades'), 'hours');
echo html_writer::empty_tag('input', ['type' => 'number', 'step' => '0.25', 'min' => '0', 'name' => 'hours', 'class' => 'form-control mb-2', 'value' => $hoursvalue]);
echo html_writer::tag('div', get_string('workshophours_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);

echo html_writer::label(get_string('description'), 'description');
echo html_writer::tag('textarea', s($record->description ?? ''), ['name' => 'description', 'class' => 'form-control mb-3', 'rows' => 4]);

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
