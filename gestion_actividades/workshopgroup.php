<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$activity = manager::get_activity($id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshopgroup.php', ['id' => $id]));
$PAGE->set_title(get_string('setworkshopgroup', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setworkshopgroup', 'local_gestion_actividades') . ': ' . format_string($activity->name));

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('workshopsandeditions', 'local_gestion_actividades'), ['class' => 'btn btn-primary']),
    'mb-3'
);

echo $OUTPUT->notification(get_string('oldgroupflowwarning', 'local_gestion_actividades'), 'info');

if (data_submitted() && confirm_sesskey()) {
    $groupid = required_param('groupid', PARAM_INT);
    $summary = manager::set_participants_from_group($id, $groupid);

    $message = get_string('workshopgroupsetdone', 'local_gestion_actividades',
        $summary->groupname . ' — ' . $summary->inserted
    );
    echo $OUTPUT->notification($message, 'success');

    $table = new html_table();
    $table->data = [
        [get_string('selectedgroup', 'local_gestion_actividades'), format_string($summary->groupname)],
        [get_string('groupmembers', 'local_gestion_actividades'), $summary->members],
        [get_string('participantsloaded', 'local_gestion_actividades'), $summary->inserted],
        [get_string('activityplaces', 'local_gestion_actividades'), $activity->places],
        [get_string('overplaces', 'local_gestion_actividades'), $summary->overplaces],
    ];
    echo html_writer::table($table);

    if ($summary->overplaces > 0) {
        echo $OUTPUT->notification(get_string('overplaceswarning', 'local_gestion_actividades'), 'warning');
    }
}

$groups = manager::get_course_groups((int)$activity->courseid);
if (!$groups) {
    echo $OUTPUT->notification(get_string('nogroupsincourse', 'local_gestion_actividades'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$options = manager::get_course_group_options((int)$activity->courseid, false);

echo html_writer::tag('p', get_string('setworkshopgroup_help', 'local_gestion_actividades'));
echo html_writer::tag('p', get_string('setworkshopgroup_warning', 'local_gestion_actividades'), ['class' => 'alert alert-warning']);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::label(get_string('moodlegroupforenrolment', 'local_gestion_actividades'), 'groupid', false, ['class' => 'form-label']);
echo html_writer::select($options, 'groupid', '', false, ['class' => 'form-control mb-3']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('setworkshopgroup', 'local_gestion_actividades')]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
