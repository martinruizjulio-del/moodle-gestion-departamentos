<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$workshopid = required_param('workshopid', PARAM_INT);
$ok = manager::ensure_workshop_sections_safely($workshopid);

redirect(
    new moodle_url('/local/gestion_actividades/editions.php', ['workshopid' => $workshopid]),
    $ok ? get_string('sectionsrepairedok', 'local_gestion_actividades') : get_string('sectionsrepairedpartial', 'local_gestion_actividades')
);
