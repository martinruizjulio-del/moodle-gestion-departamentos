<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
require_sesskey();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$count = manager::mark_selected_as_completed($id);
redirect(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]), 'Marcados como realizados: ' . $count, null, \core\output\notification::NOTIFY_SUCCESS);
