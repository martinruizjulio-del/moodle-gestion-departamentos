<?php
// Library callbacks for Gestion_actividades.

defined('MOODLE_INTERNAL') || die();


function local_gestion_actividades_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if (!in_array($filearea, ['material', 'certificate'], true)) { return false; }
    require_login($course);
    global $USER, $DB;
    if ($filearea === 'certificate') {
        $itemidpeek = !empty($args) ? (int)$args[0] : 0;
        $cert = $itemidpeek ? $DB->get_record('local_ga_certificates', ['id' => $itemidpeek], '*', IGNORE_MISSING) : false;
        if (!$cert) { return false; }
        $canmanage = has_capability('local/gestion_actividades:manage', context_system::instance());
        if ((int)$cert->userid !== (int)$USER->id && !$canmanage) { return false; }
    }
    if (empty($args)) { return false; }
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_gestion_actividades', 'material', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) { return false; }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
