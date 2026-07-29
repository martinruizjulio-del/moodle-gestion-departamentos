<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\portfolio_typeb;

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$comment = optional_param('comment', '', PARAM_TEXT);

require_login();
$context = context_system::instance();
if (!\local_gestion_actividades\local\manager::can_manage_globally((int)$USER->id)) {
    throw new required_capability_exception(context_system::instance(), 'local/gestion_actividades:manage', 'nopermissions', '');
}
require_sesskey();

$status = $action === 'validate' ? 'validated' : ($action === 'reject' ? 'rejected' : 'pending');
portfolio_typeb::set_status($id, $status, $comment, (int)$USER->id);

redirect(new moodle_url('/local/gestion_actividades/manager_downloads.php', ['action' => 'view_typeb_workshops']), $status === 'validated' ? 'Taller B antiguo confirmado.' : 'Taller B antiguo actualizado.', null, \core\output\notification::NOTIFY_SUCCESS);
