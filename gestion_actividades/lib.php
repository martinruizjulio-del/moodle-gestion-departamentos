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

/**
 * Add course navigation links so nobody has to remember plugin URLs.
 */
function local_gestion_actividades_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context): void {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $canmanage = false;
    try {
        $canmanage = \local_gestion_actividades\local\manager::can_manage_workshop($course, (int)$USER->id);
    } catch (Throwable $e) {
        $canmanage = has_capability('local/gestion_actividades:manage', context_system::instance());
    }

    if ($canmanage) {
        $node = $parentnode->add(
            'Gestion_actividades',
            new moodle_url('/local/gestion_actividades/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_dashboard',
            new pix_icon('i/settings', 'Gestion_actividades')
        );
        $node->showinflatnavigation = true;
        return;
    }

    if (is_enrolled($context, $USER, '', true)) {
        $node = $parentnode->add(
            'Mi portafolio de talleres',
            new moodle_url('/local/gestion_actividades/portfolio.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_portfolio',
            new pix_icon('i/report', 'Mi portafolio de talleres')
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Add a lightweight global navigation shortcut too.
 */
function local_gestion_actividades_extend_navigation(global_navigation $navigation): void {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $canmanage = false;
    try {
        $canmanage = has_capability('local/gestion_actividades:manage', context_system::instance());
    } catch (Throwable $e) {
        $canmanage = false;
    }

    if ($canmanage) {
        $node = $navigation->add(
            'Gestion_actividades',
            new moodle_url('/local/gestion_actividades/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_global_dashboard',
            new pix_icon('i/settings', 'Gestion_actividades')
        );
        $node->showinflatnavigation = true;
    } else {
        $node = $navigation->add(
            'Mi portafolio de talleres',
            new moodle_url('/local/gestion_actividades/portfolio.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_global_portfolio',
            new pix_icon('i/report', 'Mi portafolio de talleres')
        );
        $node->showinflatnavigation = true;
    }
}
