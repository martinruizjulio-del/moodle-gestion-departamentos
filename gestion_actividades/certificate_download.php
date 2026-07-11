<?php
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
require_login();

$cert = $DB->get_record('local_ga_certificates', ['id' => $id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => (int)$cert->courseid], '*', MUST_EXIST);
$context = context_course::instance((int)$course->id);

$canmanage = has_capability('local/gestion_actividades:manage', context_system::instance());
if ((int)$cert->userid !== (int)$USER->id && !$canmanage) {
    throw new required_capability_exception($context, 'local/gestion_actividades:manage', 'nopermissions', '');
}

$fs = get_file_storage();
$file = $fs->get_file($context->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, '/', $cert->filename);

// Fallback: if filename changed or was stored differently, get the first PDF in the certificate area.
if (!$file || $file->is_directory()) {
    $files = $fs->get_area_files($context->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, 'filename', false);
    foreach ($files as $candidate) {
        if (!$candidate->is_directory() && strtolower($candidate->get_filename()) !== '.') {
            $file = $candidate;
            break;
        }
    }
}

if (!$file || $file->is_directory()) {
    throw new moodle_exception('filenotfound');
}

$filename = $cert->filename;
if (empty($filename) || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'pdf') {
    $filename = 'certificado_taller_tipo_a_' . (int)$cert->id . '.pdf';
}

// Important: do not request preview/thumb here.
// The previous version used preview => thumb, so Moodle returned the PDF icon SVG instead of the PDF.
send_stored_file($file, 0, 0, true, [
    'filename' => $filename,
    'dontdie' => false,
]);
