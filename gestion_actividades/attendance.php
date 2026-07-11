<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$activity = manager::get_activity($id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/attendance.php', ['id' => $id]));
$PAGE->set_title(get_string('syncattendance', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncattendance', 'local_gestion_actividades') . ': ' . format_string($activity->name));

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

if (!manager::attendance_tables_available()) {
    echo $OUTPUT->notification(get_string('attendancenotavailable', 'local_gestion_actividades'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if (data_submitted() && confirm_sesskey()) {
    $sessionid = required_param('sessionid', PARAM_INT);
    $summary = manager::sync_attendance_session($id, $sessionid);

    $message = get_string('attendancesyncdone', 'local_gestion_actividades',
        $summary->attended . '/' . $summary->processed
    );
    echo $OUTPUT->notification($message, 'success');

    $table = new html_table();
    $table->data = [
        [get_string('att_processed', 'local_gestion_actividades'), $summary->processed],
        [get_string('att_attended', 'local_gestion_actividades'), $summary->attended],
        [get_string('att_notpresent', 'local_gestion_actividades'), $summary->notpresent],
        [get_string('att_nolog', 'local_gestion_actividades'), $summary->nolog],
        [get_string('att_alreadycompleted', 'local_gestion_actividades'), $summary->alreadycompleted],
    ];
    echo html_writer::table($table);
}

$sessions = manager::get_attendance_sessions((int)$activity->courseid);
if (!$sessions) {
    echo $OUTPUT->notification(get_string('noattendancesessions', 'local_gestion_actividades'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$options = [];
foreach ($sessions as $session) {
    $date = userdate($session->sessdate, get_string('strftimedatetime', 'langconfig'));
    $options[$session->id] = $session->attendancename . ' - ' . $date . ' - ID ' . $session->id;
}

echo html_writer::tag('p', get_string('syncattendance_help', 'local_gestion_actividades'));

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::label(get_string('attendancesession', 'local_gestion_actividades'), 'sessionid', false, ['class' => 'form-label']);
echo html_writer::select($options, 'sessionid', '', false, ['class' => 'form-control mb-3']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-success', 'value' => get_string('syncattendance', 'local_gestion_actividades')]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
