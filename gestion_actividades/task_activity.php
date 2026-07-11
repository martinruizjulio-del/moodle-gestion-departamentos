<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // edition id
$go = optional_param('go', 0, PARAM_BOOL);
$linkcmid = optional_param('linkcmid', 0, PARAM_INT);

require_login();

$edition = manager::get_workshop_edition($id);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if (!manager::can_manage_workshop($course, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}

// If already linked, open it.
if (!empty($edition->requiredcmid) && $DB->record_exists('course_modules', ['id' => $edition->requiredcmid])) {
    $modname = manager::get_modname_from_cmid((int)$edition->requiredcmid);
    redirect(new moodle_url('/mod/' . $modname . '/view.php', ['id' => $edition->requiredcmid]));
}

if (!empty($linkcmid) && confirm_sesskey()) {
    if (manager::link_required_activity_to_edition((int)$id, (int)$linkcmid)) {
        redirect(
            new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]),
            get_string('requiredactivitylinked', 'local_gestion_actividades')
        );
    }
}

$type = manager::detect_required_activity_type($edition);
if ($type === '') {
    $type = 'assign';
}
if ($type !== 'quiz') {
    $type = 'assign';
}

$sectionnum = manager::get_or_create_course_section((int)$course->id, manager::get_main_workshop_section_name());

// If a likely activity already exists, link it automatically and return to workshop.
$candidates = manager::find_candidate_required_activities($edition);
if (!$go && count($candidates) === 1) {
    $candidate = reset($candidates);
    if (manager::link_required_activity_to_edition((int)$id, (int)$candidate->cmid)) {
        redirect(
            new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]),
            get_string('requiredactivitylinked', 'local_gestion_actividades')
        );
    }
}

if ($go && confirm_sesskey()) {
    if (!$DB->record_exists('modules', ['name' => $type])) {
        redirect(
            new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]),
            get_string('requiredmodnotavailable', 'local_gestion_actividades') . ': ' . $type,
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Open Moodle's native creation form inside TALLERES TIPO A section.
    redirect(new moodle_url('/course/modedit.php', [
        'add' => $type,
        'type' => '',
        'course' => $course->id,
        'section' => $sectionnum,
        'return' => 1,
        'sr' => $sectionnum,
    ]));
}

$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]));
$PAGE->set_title(get_string('configuretaskquiz', 'local_gestion_actividades'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configuretaskquiz', 'local_gestion_actividades') . ': ' . format_string($workshop->code . ' - ' . $workshop->name));

$typename = $type === 'quiz' ? get_string('quiz', 'quiz') : get_string('modulename', 'assign');

if ($candidates && count($candidates) > 1) {
    echo $OUTPUT->notification(get_string('chooseactivitytolink', 'local_gestion_actividades'), 'info');
} else {
    echo $OUTPUT->notification(get_string('nativeactivitycreationselected', 'local_gestion_actividades', $typename), 'info');
}

$createurl = new moodle_url('/local/gestion_actividades/task_activity.php', [
    'id' => $id,
    'go' => 1,
    'sesskey' => sesskey(),
]);
echo html_writer::link($createurl, get_string('opennativeactivityform', 'local_gestion_actividades', $typename), ['class' => 'btn btn-primary']);

echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]), get_string('viewworkshop', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);

if ($candidates) {
    echo html_writer::start_tag('div', ['class' => 'card mt-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h3', get_string('linkexistingrequiredactivity', 'local_gestion_actividades'));
    $table = new html_table();
    $table->head = [get_string('name'), get_string('type'), get_string('actions')];
    foreach ($candidates as $candidate) {
        $linkurl = new moodle_url('/local/gestion_actividades/task_activity.php', [
            'id' => $id,
            'linkcmid' => $candidate->cmid,
            'sesskey' => sesskey(),
        ]);
        $table->data[] = [
            format_string($candidate->activityname),
            s($candidate->modname),
            html_writer::link($linkurl, get_string('linkthisactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary btn-sm'])
        ];
    }
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
