<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;
use local_gestion_actividades\local\portfolio_pdf;
use local_gestion_actividades\local\portfolio_typeb;

$userid = optional_param('userid', 0, PARAM_INT);
require_login();

$context = context_system::instance();
$canmanage = has_capability('local/gestion_actividades:manage', $context);

if ($userid <= 0) {
    $userid = (int)$USER->id;
}

if ((int)$userid !== (int)$USER->id && !$canmanage) {
    throw new required_capability_exception($context, 'local/gestion_actividades:manage', 'nopermissions', '');
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
portfolio_typeb::ensure_table();

$packer = get_file_packer('application/zip');
$tempdir = make_request_directory();
$files = [];

$mainpdf = portfolio_pdf::render_pdf_string((int)$userid);
$mainname = '00_portafolio_' . clean_filename(fullname($user)) . '.pdf';
$mainpath = $tempdir . '/' . $mainname;
file_put_contents($mainpath, $mainpdf);
$files[$mainname] = $mainpath;

// Anexos Tipo A: certificados generados por el sistema, ordenados por fecha de emisión.
$typeacerts = method_exists(manager::class, 'list_user_certificates') ? manager::list_user_certificates((int)$userid) : [];
usort($typeacerts, function($a, $b) {
    return ((int)($a->timeissued ?? 0)) <=> ((int)($b->timeissued ?? 0));
});

$n = 1;
foreach ($typeacerts as $cert) {
    $course = $DB->get_record('course', ['id' => (int)$cert->courseid], '*', IGNORE_MISSING);
    if (!$course) {
        continue;
    }
    $coursecontext = context_course::instance((int)$course->id, IGNORE_MISSING);
    if (!$coursecontext) {
        continue;
    }
    $fs = get_file_storage();
    $file = $fs->get_file($coursecontext->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, '/', $cert->filename);
    if (!$file || $file->is_directory()) {
        $area = $fs->get_area_files($coursecontext->id, 'local_gestion_actividades', 'certificate', (int)$cert->id, 'filename', false);
        foreach ($area as $candidate) {
            if (!$candidate->is_directory()) {
                $file = $candidate;
                break;
            }
        }
    }
    if (!$file || $file->is_directory()) {
        continue;
    }
    $name = sprintf('01_Tipo_A/%02d_%s_%s.pdf', $n++, userdate((int)$cert->timeissued, '%Y%m%d'), clean_filename(($cert->workshopcode ?? 'certificado') . '_' . ($cert->workshopname ?? 'tipo_a')));
    $path = $tempdir . '/tipoa_' . $n . '.pdf';
    $file->copy_content_to($path);
    $files[$name] = $path;
}

// Anexos Tipo B: PDFs subidos por el alumno, ordenados por fecha de actividad.
$typebcerts = portfolio_typeb::list_for_user((int)$userid);
usort($typebcerts, function($a, $b) {
    return ((int)($a->activitydate ?? 0)) <=> ((int)($b->activitydate ?? 0));
});

$n = 1;
$fs = get_file_storage();
foreach ($typebcerts as $cert) {
    $file = $fs->get_file($context->id, 'local_gestion_actividades', 'typeb_certificate', (int)$cert->id, '/', $cert->filename);
    if (!$file || $file->is_directory()) {
        $area = $fs->get_area_files($context->id, 'local_gestion_actividades', 'typeb_certificate', (int)$cert->id, 'filename', false);
        foreach ($area as $candidate) {
            if (!$candidate->is_directory()) {
                $file = $candidate;
                break;
            }
        }
    }
    if (!$file || $file->is_directory()) {
        continue;
    }
    $name = sprintf('02_Tipo_B/%02d_%s_%s.pdf', $n++, userdate((int)$cert->activitydate, '%Y%m%d'), clean_filename($cert->activityname));
    $path = $tempdir . '/tipob_' . $n . '.pdf';
    $file->copy_content_to($path);
    $files[$name] = $path;
}

$zipname = clean_filename('expediente_portafolio_' . fullname($user) . '.zip');
$zippath = $tempdir . '/' . $zipname;
$packer->archive_to_pathname($files, $zippath);

send_temp_file($zippath, $zipname);
