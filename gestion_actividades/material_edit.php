<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$syscontext = context_system::instance();
require_capability('local/gestion_actividades:manage', $syscontext);

$id = optional_param('id', 0, PARAM_INT);
$workshopid = required_param('workshopid', PARAM_INT);
$editionid = optional_param('editionid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);

$workshop = manager::get_workshop($workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if (!manager::can_manage_workshop($course, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}

$material = $id ? manager::get_material($id) : (object)[
    'id' => 0,
    'workshopid' => $workshopid,
    'editionid' => $editionid,
    'name' => '',
    'description' => '',
    'url' => '',
    'visible' => 1,
    'fileitemid' => 0,
];

if ($id && $delete && confirm_sesskey()) {
    manager::delete_material($id);
    redirect(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshopid]), get_string('materialdeleted', 'local_gestion_actividades'));
}

if (data_submitted() && confirm_sesskey() && !$delete) {
    $fileitemid = (int)($material->fileitemid ?? 0);
    try {
        $fileitemid = manager::store_material_upload($coursecontext->id, $fileitemid, 'materialfileupload');
    } catch (\Throwable $e) {
        redirect(new moodle_url('/local/gestion_actividades/material_edit.php', ['workshopid' => $workshopid, 'id' => $id]), get_string('materialuploaderror', 'local_gestion_actividades') . ': ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }

    $data = [
        'id' => optional_param('id', 0, PARAM_INT),
        'workshopid' => $workshopid,
        'editionid' => optional_param('editionid', 0, PARAM_INT),
        'name' => required_param('name', PARAM_TEXT),
        'description' => optional_param('description', '', PARAM_TEXT),
        'url' => optional_param('url', '', PARAM_RAW_TRIMMED),
        'visible' => optional_param('visible', 0, PARAM_BOOL),
        'fileitemid' => $fileitemid,
    ];
    manager::save_material((object)$data);
    redirect(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshopid]), get_string('changessaved'));
}

$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/material_edit.php', ['workshopid' => $workshopid, 'id' => $id]));
$PAGE->set_title(get_string('editmaterial', 'local_gestion_actividades'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editmaterial', 'local_gestion_actividades') . ': ' . format_string($workshop->name));
echo html_writer::tag('p', get_string('materialupload_simple_help', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

$currentfileurl = '';
try {
    $currentfileurl = manager::get_material_file_url($material, $coursecontext);
} catch (\Throwable $e) {
    $currentfileurl = '';
}
if ($currentfileurl !== '') {
    echo html_writer::tag('p', html_writer::link($currentfileurl, get_string('currentfile', 'local_gestion_actividades'), ['target' => '_blank']), ['class' => 'alert alert-secondary']);
}

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $material->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'workshopid', 'value' => $workshopid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editionid', 'value' => $editionid]);

echo html_writer::label(get_string('name'), 'name');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'id' => 'name', 'value' => s($material->name), 'class' => 'form-control mb-2', 'required' => 'required']);

echo html_writer::label(get_string('description'), 'description');
echo html_writer::tag('textarea', s($material->description), ['name' => 'description', 'id' => 'description', 'class' => 'form-control mb-2', 'rows' => 4]);

echo html_writer::label(get_string('materialurl', 'local_gestion_actividades'), 'url');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'url', 'id' => 'url', 'value' => s($material->url), 'class' => 'form-control mb-2', 'placeholder' => 'https://...']);

echo html_writer::label(get_string('uploadfile', 'local_gestion_actividades'), 'materialfileupload');
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'materialfileupload', 'id' => 'materialfileupload', 'class' => 'form-control mb-2']);

echo html_writer::label(html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'visible', 'value' => 1, 'checked' => !empty($material->visible) ? 'checked' : null]) . ' ' . get_string('visible'), 'visible');
echo html_writer::empty_tag('br');

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary mt-3']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshopid]), get_string('cancel'), ['class' => 'btn btn-secondary mt-3']);
echo html_writer::end_tag('form');

if ($id) {
    echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/material_edit.php', ['id' => $id, 'workshopid' => $workshopid, 'delete' => 1, 'sesskey' => sesskey()]), get_string('delete'), ['class' => 'btn btn-danger mt-3']), 'mt-2');
}

echo $OUTPUT->footer();
