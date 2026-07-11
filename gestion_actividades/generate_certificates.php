<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // edition id
require_login();

$edition = manager::get_workshop_edition($id);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_capability('local/gestion_actividades:manage', context_system::instance());
require_sesskey();

$summary = manager::generate_certificates_for_edition((int)$edition->id);

redirect(
    new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $edition->id]),
    get_string('certificatesgeneratedsummary', 'local_gestion_actividades', $summary),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
