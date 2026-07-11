<?php
require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/index.php'));
$PAGE->set_title(get_string('studentssection', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

$canmanage = has_capability('local/gestion_actividades:manage', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('studentssection', 'local_gestion_actividades'));

echo html_writer::tag('p', get_string('studentspanelcleanintro', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), get_string('dashboard', 'local_gestion_actividades'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('workshops', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/myhours.php'), get_string('openmyhours', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

if ($canmanage) {
    echo html_writer::tag('h4', get_string('studentsmanagement', 'local_gestion_actividades'));
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/gestion_actividades/users.php'), get_string('bulkcreateusers', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']) . ' ' .
        html_writer::link(new moodle_url('/local/gestion_actividades/gradehistory.php'), get_string('gradehistory', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
        'mb-3'
    );

    echo html_writer::tag('p', get_string('legacyrankinghidden_help', 'local_gestion_actividades'), ['class' => 'text-muted']);
}

echo $OUTPUT->footer();
