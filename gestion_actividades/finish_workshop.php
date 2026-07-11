<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // edition id

require_login();

$edition = manager::get_workshop_edition($id);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if (!manager::can_manage_workshop($course, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}

require_sesskey();

manager::archive_finished_workshop_edition((int)$edition->id);

redirect(
    new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshop->id]),
    get_string('workshopfinishedhardarchived', 'local_gestion_actividades'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
