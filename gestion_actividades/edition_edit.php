<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = optional_param('id', 0, PARAM_INT);
$workshopid = required_param('workshopid', PARAM_INT);

$workshop = manager::get_workshop($workshopid);
$record = $id ? manager::get_workshop_edition($id) : null;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/edition_edit.php', ['id' => $id, 'workshopid' => $workshopid]));
$PAGE->set_title(get_string('editedition', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

if (data_submitted() && confirm_sesskey()) {
    $teachers = optional_param_array('teachers', [], PARAM_INT);
    $data = (object)[
        'id' => optional_param('id', 0, PARAM_INT),
        'workshopid' => $workshopid,
        'activityid' => optional_param('activityid', 0, PARAM_INT),
        'name' => required_param('name', PARAM_TEXT),
        'editioncode' => required_param('editioncode', PARAM_ALPHANUMEXT),
        'sessiondate' => strtotime(required_param('sessiondate_text', PARAM_TEXT)) ?: 0,
        'enrolenddate' => strtotime(required_param('enrolenddate_text', PARAM_TEXT)) ?: 0,
        'places' => required_param('places', PARAM_INT),
        'groupid' => $record->groupid ?? 0,
        'attendancecmid' => optional_param('attendancecmid', 0, PARAM_INT),
        'certificatecmid' => optional_param('certificatecmid', 0, PARAM_INT),
        'requiredcmid' => optional_param('requiredcmid', 0, PARAM_INT),
        'requiredmodname' => manager::get_module_name_from_cmid(optional_param('requiredcmid', 0, PARAM_INT)),
        'activitycreationtype' => optional_param('activitycreationtype', $record->activitycreationtype ?? '', PARAM_ALPHA),
        'tasknumericgrade' => optional_param('tasknumericgrade', $record->tasknumericgrade ?? '', PARAM_FLOAT),
        'quizgradingmode' => optional_param('quizgradingmode', $record->quizgradingmode ?? 'completion', PARAM_ALPHA),
        'archived' => $record->archived ?? 0,
        'timearchived' => $record->timearchived ?? 0,
        'status' => optional_param('status', 'open', PARAM_ALPHANUMEXT),
        'teachers' => $teachers,
    ];
    manager::save_workshop_edition($data);
    redirect(new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshopid]), get_string('changessaved'));
}

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('safemodeactive', 'local_gestion_actividades'), 'warning');

echo $OUTPUT->heading(get_string('editedition', 'local_gestion_actividades') . ': ' . s($workshop->code));
echo html_writer::tag('p', get_string('editionfulledit_help', 'local_gestion_actividades'), ['class' => 'alert alert-info']);
echo html_writer::tag('p', get_string('dateformat_example_help', 'local_gestion_actividades'), ['class' => 'alert alert-secondary']);
echo html_writer::tag('p', get_string('standardsectionnotnested_help', 'local_gestion_actividades'), ['class' => 'alert alert-secondary']);
$course = $DB->get_record('course', ['id' => $workshop->courseid]);
if ($course) { echo html_writer::tag('p', get_string('coursewherecreated', 'local_gestion_actividades') . ': ' . html_writer::tag('strong', format_string($course->fullname) . ' [' . s($course->shortname) . '] — ID ' . $course->id), ['class' => 'alert alert-info']); }
echo html_writer::tag('p', get_string('workshophours', 'local_gestion_actividades') . ': ' . html_writer::tag('strong', (isset($workshop->hours) && $workshop->hours !== null ? round((float)$workshop->hours, 2) . ' h' : '-')), ['class' => 'alert alert-success']);



$teachers = manager::get_course_teachers($workshop->courseid);
$selectedteachers = $id ? array_keys(manager::get_edition_teachers($id)) : [];

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'workshopid', 'value' => $workshopid]);

$fields = [
    ['name', get_string('name'), $record->name ?? ($workshop->code . ' - ' . $workshop->name)],
    ['editioncode', get_string('editioncode', 'local_gestion_actividades'), $record->editioncode ?? ($workshop->code . '_E1')],
    ['sessiondate_text', get_string('date'), !empty($record->sessiondate) ? date('Y-m-d H:i', $record->sessiondate) : date('Y-m-d H:i')],
    ['enrolenddate_text', get_string('enrolenddate', 'local_gestion_actividades'), !empty($record->enrolenddate) ? date('Y-m-d H:i', $record->enrolenddate) : date('Y-m-d H:i', time() + 7*24*3600)],
    ['places', get_string('places', 'local_gestion_actividades'), $record->places ?? 20],
];
foreach ($fields as $f) {
    [$name, $label, $value] = $f;
    echo html_writer::label($label, $name);
    echo html_writer::empty_tag('input', ['type' => $name === 'places' ? 'number' : 'text', 'name' => $name, 'class' => 'form-control mb-2', 'required' => in_array($name, ['name','editioncode','places']) ? 'required' : null, 'value' => $value]);
}

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'activityid', 'value' => $record->activityid ?? 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attendancecmid', 'value' => $record->attendancecmid ?? 0]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'certificatecmid', 'value' => $record->certificatecmid ?? 0]);
echo html_writer::tag('div', get_string('technicalids_hidden_help', 'local_gestion_actividades'), ['class' => 'alert alert-secondary']);

echo html_writer::label(get_string('moodlegroupforenrolment', 'local_gestion_actividades'), 'groupinfo');
if ($id && !empty($record->groupid) && $DB->record_exists('groups', ['id' => $record->groupid])) {
    $linkedgroup = $DB->get_record('groups', ['id' => $record->groupid]);
    $membercount = $DB->count_records('groups_members', ['groupid' => $record->groupid]);
    echo html_writer::tag('div', format_string($linkedgroup->name) . ' (' . $membercount . ' ' . get_string('studentscount', 'local_gestion_actividades') . ')', ['class' => 'alert alert-success']);
} else if ($id) {
    echo html_writer::tag('div', get_string('groupwillbecreatedonsave', 'local_gestion_actividades'), ['class' => 'alert alert-warning']);
} else {
    echo html_writer::tag('div', get_string('groupwillbecreated', 'local_gestion_actividades'), ['class' => 'alert alert-info']);
}
echo html_writer::tag('div', get_string('automaticgroup_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);


echo html_writer::tag('h4', get_string('activitycertificateconfig', 'local_gestion_actividades'));
if ($id && !empty($record->requiredcmid) && $DB->record_exists('course_modules', ['id' => $record->requiredcmid])) {
    $modname = manager::get_module_name_from_cmid($record->requiredcmid);
    echo html_writer::tag('div', get_string('linkedrequiredactivity', 'local_gestion_actividades') . ': CMID ' . $record->requiredcmid . ' (' . s($modname) . ')', ['class' => 'alert alert-success']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'activitycreationtype', 'value' => $record->activitycreationtype ?? $modname]);
} else {
    $creationoptions = [
        '' => get_string('donotcreateactivityyet', 'local_gestion_actividades'),
        'assign' => get_string('createassignautomatically', 'local_gestion_actividades'),
        'quiz' => get_string('createquizautomatically', 'local_gestion_actividades'),
    ];
    echo html_writer::label(get_string('activitycreationtype', 'local_gestion_actividades'), 'activitycreationtype');
    echo html_writer::select($creationoptions, 'activitycreationtype', $record->activitycreationtype ?? '', false, ['class' => 'form-control mb-2']);
    echo html_writer::tag('div', get_string('activitycreationtype_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);
}
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'requiredcmid', 'value' => $record->requiredcmid ?? 0]);

echo html_writer::label(get_string('tasknumericgrade', 'local_gestion_actividades'), 'tasknumericgrade');
echo html_writer::empty_tag('input', ['type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '19', 'name' => 'tasknumericgrade', 'class' => 'form-control mb-2', 'value' => $record->tasknumericgrade ?? '']);
echo html_writer::tag('div', get_string('tasknumericgrade_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);

$quizoptions = [
    'completion' => get_string('quizmodecompletion', 'local_gestion_actividades'),
    'points' => get_string('quizmodepoints', 'local_gestion_actividades'),
];
echo html_writer::label(get_string('quizgradingmode', 'local_gestion_actividades'), 'quizgradingmode');
echo html_writer::select($quizoptions, 'quizgradingmode', $record->quizgradingmode ?? 'completion', false, ['class' => 'form-control mb-2']);
echo html_writer::tag('div', get_string('quizgradingmode_help', 'local_gestion_actividades'), ['class' => 'form-text mb-3']);

echo html_writer::label(get_string('teachers', 'local_gestion_actividades'), 'teachers');
echo html_writer::start_tag('select', ['name' => 'teachers[]', 'multiple' => 'multiple', 'class' => 'form-control mb-3', 'size' => 8]);
foreach ($teachers as $t) {
    echo html_writer::tag('option', fullname($t) . ' — ' . $t->email, ['value' => $t->id, 'selected' => in_array($t->id, $selectedteachers) ? 'selected' : null]);
}
echo html_writer::end_tag('select');

echo html_writer::label(get_string('status'), 'status');
echo html_writer::select(['open' => 'open', 'closed_full' => 'closed_full', 'closed_date' => 'closed_date', 'finished' => 'finished'], 'status', $record->status ?? 'open', false, ['class' => 'form-control mb-3']);

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshopid]), get_string('cancel'), ['class' => 'btn btn-secondary']);
if ($id) { echo ' ' . html_writer::link(new moodle_url('/local/gestion_actividades/edition_delete.php', ['id' => $id]), get_string('deleteedition', 'local_gestion_actividades'), ['class' => 'btn btn-danger']); }
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
