<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$courseid = optional_param('courseid', 0, PARAM_INT);
$summary = manager::ensure_all_workshop_course_visuals($courseid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/repair_course_visuals.php'));
$PAGE->set_title(get_string('repaircoursevisuals', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('repaircoursevisuals', 'local_gestion_actividades'));

echo $OUTPUT->notification(get_string('coursevisualsrepaired_detailed', 'local_gestion_actividades', $summary), $summary->failed ? 'warning' : 'success');

if (!empty($summary->messages)) {
    echo html_writer::start_tag('ul');
    foreach ($summary->messages as $message) {
        echo html_writer::tag('li', s($message));
    }
    echo html_writer::end_tag('ul');
}

echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);

echo $OUTPUT->footer();
