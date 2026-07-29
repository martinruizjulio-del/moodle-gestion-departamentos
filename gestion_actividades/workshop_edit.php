<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();

$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHA);
$type = $type === 'typeb' ? 'typeb' : 'typea';
$record = $id ? manager::get_workshop($id) : null;
$syscontext = context_system::instance();
$context = $syscontext;
$canmanageplugin = manager::can_manage_globally((int)$USER->id);
if ($record) {
    $course = $DB->get_record('course', ['id' => $record->courseid], '*', MUST_EXIST);
    $context = context_course::instance((int)$course->id);
    if (!manager::can_manage_workshop_instance((int)$record->id, (int)$USER->id)) {
        throw new required_capability_exception($context, 'moodle/course:update', 'nopermissions', '');
    }
} else {
    if (!\local_gestion_actividades\local\manager::can_manage_globally((int)$USER->id)) {
    throw new required_capability_exception(context_system::instance(), 'local/gestion_actividades:manage', 'nopermissions', '');
}
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshop_edit.php', ['id' => $id, 'type' => $record ? ($record->workshoptype ?? 'typea') : $type]));
$PAGE->set_title(get_string('editworkshop', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (data_submitted() && confirm_sesskey()) {
    $data = (object)[
        'id' => optional_param('id', 0, PARAM_INT),
        'courseid' => ($record && empty($canmanageplugin)) ? (int)$record->courseid : required_param('courseid', PARAM_INT),
        'code' => optional_param('code', '', PARAM_ALPHANUMEXT),
        'name' => required_param('name', PARAM_TEXT),
        'description' => optional_param('description', '', PARAM_TEXT),
        'hours' => optional_param('hours', '', PARAM_RAW_TRIMMED),
        'sectionnum' => optional_param('sectionnum', $record->sectionnum ?? 0, PARAM_INT),
        'workshoptype' => $record ? (string)($record->workshoptype ?? 'typea') : $type,
    ];
    $savedworkshopid = manager::save_workshop($data);
    if (empty($data->id)) {
        redirect(new moodle_url('/local/gestion_actividades/edition_edit.php', [
            'workshopid' => $savedworkshopid,
            'prefillhours' => $data->hours,
            'prefilldescription' => $data->description,
            'prefillname' => $data->name,
        ]), get_string('changessaved'));
    }
    redirect(new moodle_url('/local/gestion_actividades/workshops.php', ['type' => $record ? ($record->workshoptype ?? 'typea') : $type]), get_string('changessaved'));
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver al panel', ['class' => 'btn btn-outline-secondary mr-2 mb-3']) .
    html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php', ['type' => $record ? ($record->workshoptype ?? 'typea') : $type]), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver a Talleres Tipo A', ['class' => 'btn btn-outline-secondary mb-3']),
    'mb-2'
);
echo $OUTPUT->heading(get_string('editworkshop', 'local_gestion_actividades'));

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sectionnum', 'value' => $record->sectionnum ?? 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'workshoptype', 'value' => $record ? ($record->workshoptype ?? 'typea') : $type]);

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
}

echo html_writer::label(get_string('workshopname', 'local_gestion_actividades'), 'name');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'class' => 'form-control mb-2', 'required' => 'required', 'value' => $record->name ?? '']);

$hoursvalue = isset($record->hours) && $record->hours !== null ? str_replace('.', ',', (string)$record->hours) : '';
echo html_writer::label(get_string('workshophours', 'local_gestion_actividades'), 'hours');
echo html_writer::empty_tag('input', ['type' => 'text', 'inputmode' => 'decimal', 'name' => 'hours', 'class' => 'form-control mb-2', 'value' => $hoursvalue]);
echo html_writer::tag('div', get_string('workshophours_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);

echo html_writer::label(get_string('description'), 'description');
echo html_writer::tag('textarea', s($record->description ?? ''), ['name' => 'description', 'class' => 'form-control mb-3', 'rows' => 4]);

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php', ['type' => $record ? ($record->workshoptype ?? 'typea') : $type]), get_string('cancel'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
