<?php
// Library callbacks for Gestion_actividades.

defined('MOODLE_INTERNAL') || die();

function local_gestion_actividades_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if (!in_array($filearea, ['material', 'certificate'], true)) {
        return false;
    }

    require_login($course);
    global $USER, $DB;

    if ($filearea === 'certificate') {
        $itemidpeek = !empty($args) ? (int)$args[0] : 0;
        $cert = $itemidpeek ? $DB->get_record('local_ga_certificates', ['id' => $itemidpeek], '*', IGNORE_MISSING) : false;
        if (!$cert) {
            return false;
        }
        $canmanage = has_capability('local/gestion_actividades:manage', context_system::instance());
        if ((int)$cert->userid !== (int)$USER->id && !$canmanage) {
            return false;
        }
    }

    if (empty($args)) {
        return false;
    }

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/' . implode('/', $args) . '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_gestion_actividades', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * True only for users who may enter the global management panel.
 */
function local_gestion_actividades_can_open_management_panel(): bool {
    if (!isloggedin() || isguestuser()) {
        return false;
    }
    return has_capability('local/gestion_actividades:manage', context_system::instance());
}

/**
 * Add course navigation links.
 * The management panel is never shown to normal enrolled students.
 */
function local_gestion_actividades_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context): void {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (local_gestion_actividades_can_open_management_panel()) {
        $node = $parentnode->add(
            'Gestión HEE',
            new moodle_url('/local/gestion_actividades/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_dashboard',
            new pix_icon('i/settings', 'Gestión HEE')
        );
        $node->showinflatnavigation = true;
        return;
    }

    if (is_enrolled($context, $USER, '', true) && has_capability('local/gestion_actividades:viewowncertificates', context_system::instance())) {
        $node = $parentnode->add(
            'Mi portafolio HEE',
            new moodle_url('/local/gestion_actividades/portfolio.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_portfolio',
            new pix_icon('i/report', 'Mi portafolio HEE')
        );
        $node->showinflatnavigation = true;
    }
}

/**
 * Add a lightweight global navigation shortcut.
 * Students only get their own portfolio, never the management dashboard.
 */
function local_gestion_actividades_extend_navigation(global_navigation $navigation): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (local_gestion_actividades_can_open_management_panel()) {
        $node = $navigation->add(
            'Gestión HEE',
            new moodle_url('/local/gestion_actividades/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_global_dashboard',
            new pix_icon('i/settings', 'Gestión HEE')
        );
        $node->showinflatnavigation = true;
        return;
    }

    if (has_capability('local/gestion_actividades:viewowncertificates', context_system::instance())) {
        $node = $navigation->add(
            'Mi portafolio HEE',
            new moodle_url('/local/gestion_actividades/portfolio.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_gestion_actividades_global_portfolio',
            new pix_icon('i/report', 'Mi portafolio HEE')
        );
        $node->showinflatnavigation = true;
    }
}
