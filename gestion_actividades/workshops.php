<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshops.php'));
$PAGE->set_title(get_string('workshops', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('workshopssection', 'local_gestion_actividades'));
echo html_writer::tag('p', get_string('workshopsworkflowintro', 'local_gestion_actividades'), ['class' => 'alert alert-info']);
echo html_writer::tag('p', get_string('workshopactions_help', 'local_gestion_actividades'), ['class' => 'alert alert-secondary']);

echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/workshop_edit.php'), get_string('newworkshop', 'local_gestion_actividades'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), get_string('dashboard', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/repair_course_visuals.php'), get_string('repaircoursevisuals', 'local_gestion_actividades') . ' ' . html_writer::link(new moodle_url('/local/gestion_actividades/cleanup_course_entries.php'), get_string('cleanupcourseentries', 'local_gestion_actividades'), ['class' => 'btn btn-danger']), ['class' => 'btn btn-secondary']),
    'mb-3'
);

$workshops = manager::list_workshops();
$table = new html_table();
$table->head = [
    get_string('course'),
    get_string('workshopcode', 'local_gestion_actividades'),
    get_string('workshopname', 'local_gestion_actividades'),
    get_string('workshophours', 'local_gestion_actividades'),
    get_string('editions', 'local_gestion_actividades'),
    get_string('actions'),
];

foreach ($workshops as $w) {
    $course = $DB->get_record('course', ['id' => $w->courseid]);
    $editions = manager::list_workshop_editions($w->id);
    $editioncount = count($editions);
        if ($editioncount === 1) {
            $firstedition = reset($editions);
            $editurl = new moodle_url('/local/gestion_actividades/edition_edit.php', ['id' => $firstedition->id, 'workshopid' => $w->id]);
            $edittitle = get_string('editeditionfull', 'local_gestion_actividades');
        } else if ($editioncount === 0) {
            $editurl = new moodle_url('/local/gestion_actividades/edition_edit.php', ['workshopid' => $w->id]);
            $edittitle = get_string('createfirsteditionfull', 'local_gestion_actividades');
        } else {
            $editurl = new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $w->id]);
            $edittitle = get_string('selecteditiontoedit', 'local_gestion_actividades');
        }

        $actions = html_writer::link(
            $editurl,
            $OUTPUT->pix_icon('t/edit', $edittitle),
            ['class' => 'btn btn-secondary btn-sm', 'title' => $edittitle, 'aria-label' => $edittitle]
        ) . ' ' .
        html_writer::link(
            new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $w->id]),
            $OUTPUT->pix_icon('t/viewdetails', get_string('vieweditions', 'local_gestion_actividades')),
            ['class' => 'btn btn-secondary btn-sm', 'title' => get_string('vieweditions', 'local_gestion_actividades'), 'aria-label' => get_string('vieweditions', 'local_gestion_actividades')]
        ) . ' ' .
        html_writer::link(
            new moodle_url('/local/gestion_actividades/workshop_delete.php', ['id' => $w->id]),
            $OUTPUT->pix_icon('t/delete', get_string('deleteworkshop', 'local_gestion_actividades')),
            ['class' => 'btn btn-danger btn-sm', 'title' => get_string('deleteworkshop', 'local_gestion_actividades'), 'aria-label' => get_string('deleteworkshop', 'local_gestion_actividades')]
        );

        $table->data[] = [
        $course ? format_string($course->fullname) : $w->courseid,
        s($w->code),
        format_string($w->name),
        isset($w->hours) && $w->hours !== null ? round($w->hours, 2) : '-',
        count($editions),
        $actions,
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
