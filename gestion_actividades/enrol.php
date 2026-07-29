<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$editionid = required_param('id', PARAM_INT);
$edition = manager::get_workshop_edition($editionid);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);

require_login($course);

$result = manager::enrol_user_in_edition($editionid, (int)$USER->id, 'self');

redirect(
    new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]),
    $result->message,
    null,
    $result->success ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
);
