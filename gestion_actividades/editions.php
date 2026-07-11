<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$workshopid = required_param('workshopid', PARAM_INT);
$workshop = manager::get_workshop($workshopid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshopid]));
$PAGE->set_title(get_string('editions', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editions', 'local_gestion_actividades') . ': ' . s($workshop->code) . ' - ' . format_string($workshop->name));
$course = $DB->get_record('course', ['id' => $workshop->courseid]);
if ($course) { echo html_writer::tag('p', get_string('coursewherecreated', 'local_gestion_actividades') . ': ' . html_writer::tag('strong', format_string($course->fullname) . ' [' . s($course->shortname) . '] — ID ' . $course->id), ['class' => 'alert alert-info']); }


echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/edition_edit.php', ['workshopid' => $workshopid]), get_string('newedition', 'local_gestion_actividades'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

$table = new html_table();
$table->head = [
    get_string('editioncode', 'local_gestion_actividades'),
    get_string('name'),
    get_string('date'),
    get_string('enrolenddate', 'local_gestion_actividades'),
    get_string('workshophours', 'local_gestion_actividades'),
    get_string('places', 'local_gestion_actividades'),
    get_string('teachers', 'local_gestion_actividades'),
    get_string('status'),
    get_string('actions'),
];

foreach (manager::list_workshop_editions($workshopid) as $e) {
    $group = $e->groupid ? $DB->get_record('groups', ['id' => $e->groupid]) : null;
    $teachers = manager::get_edition_teachers($e->id);
    $tnames = [];
    foreach ($teachers as $t) {
        $tnames[] = fullname($t);
    }
    $actions = html_writer::link(new moodle_url('/local/gestion_actividades/edition_edit.php', ['id' => $e->id, 'workshopid' => $workshopid]), $OUTPUT->pix_icon('t/edit', get_string('edit')), ['class' => 'btn btn-secondary btn-sm', 'title' => get_string('edit')]) . ' ' .
        html_writer::link(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $e->id]), $OUTPUT->pix_icon('i/users', get_string('studentsmanualandstatus', 'local_gestion_actividades')), ['class' => 'btn btn-secondary btn-sm', 'title' => get_string('studentsmanualandstatus', 'local_gestion_actividades')]) . ' ' .
        html_writer::link(new moodle_url('/local/gestion_actividades/edition_sync.php', ['id' => $e->id]), $OUTPUT->pix_icon('t/reload', get_string('synceditionenrolments', 'local_gestion_actividades')), ['class' => 'btn btn-secondary btn-sm', 'title' => get_string('synceditionenrolments', 'local_gestion_actividades')]) . ' ' .
        html_writer::link(new moodle_url('/local/gestion_actividades/edition_delete.php', ['id' => $e->id]), $OUTPUT->pix_icon('t/delete', get_string('deleteedition', 'local_gestion_actividades')), ['class' => 'btn btn-danger btn-sm', 'title' => get_string('deleteedition', 'local_gestion_actividades')]);
    $table->data[] = [
        s($e->editioncode),
        format_string($e->name),
        $e->sessiondate ? userdate($e->sessiondate) : '-',
        $e->enrolenddate ? userdate($e->enrolenddate) : '-',
        $e->places,
        $group ? format_string($group->name) . ' (' . $DB->count_records('groups_members', ['groupid' => $group->id]) . ' ' . get_string('studentscount', 'local_gestion_actividades') . ')' : '-',
        $tnames ? implode(', ', $tnames) : '-',
        s($e->status),
        $actions,
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
