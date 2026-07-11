<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:view', $context);

$id = required_param('id', PARAM_INT);
$activity = manager::get_activity($id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/gradehistory.php', ['id' => $id]));
$PAGE->set_title(get_string('gradehistory', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

$records = manager::get_grade_history($activity->activitykey, 2000);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gradehistory', 'local_gestion_actividades') . ': ' . format_string($activity->name));
echo html_writer::div(get_string('gradehistoryinfo', 'local_gestion_actividades'), 'alert alert-info');
echo html_writer::link(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary mb-3']);

if (!$records) {
    echo $OUTPUT->notification(get_string('nogradehistory', 'local_gestion_actividades'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('academicyearcol', 'local_gestion_actividades'),
    get_string('fullname', 'local_gestion_actividades'),
    'Email',
    get_string('grade', 'local_gestion_actividades'),
    get_string('timeupdated', 'local_gestion_actividades'),
];
$table->data = [];
foreach ($records as $r) {
    $table->data[] = [
        s($r->academicyear),
        s(fullname($r)),
        s($r->email),
        is_null($r->grade) ? '-' : format_float($r->grade, 2),
        userdate($r->timemodified),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
