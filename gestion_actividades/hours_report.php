<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/hours_report.php'));
$PAGE->set_title(get_string('hoursbystudent', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('hoursbystudent', 'local_gestion_actividades'));
echo html_writer::tag('p', get_string('storedhoursnote', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

$rows = manager::get_hours_summary_by_student();

if (!$rows) {
    echo $OUTPUT->notification(get_string('noworkshopeditionsyet', 'local_gestion_actividades'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('student', 'local_gestion_actividades'),
        'Email',
        get_string('completedworkshops', 'local_gestion_actividades'),
        get_string('totaltypeahours', 'local_gestion_actividades'),
        get_string('actions'),
    ];

    foreach ($rows as $row) {
        $table->data[] = [
            fullname($row),
            s($row->email),
            (int)$row->completedworkshops,
            round((float)$row->totalhours, 2) . ' h',
            html_writer::link(new moodle_url('/local/gestion_actividades/myhours.php', ['userid' => $row->id]), get_string('view')),
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
