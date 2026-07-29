<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // certificate id
require_login();
require_sesskey();

$cert = $DB->get_record('local_ga_certificates', ['id' => $id], '*', MUST_EXIST);
$edition = manager::get_workshop_edition((int)$cert->editionid);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
if (!manager::can_manage_workshop_instance((int)$cert->workshopid, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}

$DB->delete_records('local_ga_certificates', ['id' => $id]);
try {
    $blocklib = $CFG->dirroot . '/blocks/gestion_hee/lib.php';
    if (!function_exists('block_gestion_hee_invalidate_user_cache') && is_readable($blocklib)) {
        require_once($blocklib);
    }
    if (function_exists('block_gestion_hee_invalidate_user_cache')) {
        block_gestion_hee_invalidate_user_cache((int)$cert->userid);
    }
} catch (Throwable $e) {
    if (function_exists('debugging')) {
        debugging('No se ha podido invalidar la caché del bloque Gestión HEE: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

$newcert = manager::generate_certificate_for_user((int)$cert->editionid, (int)$cert->userid);

if (!$newcert) {
    redirect(
        new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $cert->editionid]),
        get_string('certificateregeneratefailed', 'local_gestion_actividades'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

redirect(
    new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $cert->editionid]),
    get_string('certificateregenerated', 'local_gestion_actividades'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
