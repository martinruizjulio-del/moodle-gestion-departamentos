<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\portfolio_typeb;

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$comment = optional_param('comment', '', PARAM_TEXT);

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);
require_sesskey();

$status = $action === 'validate' ? 'validated' : ($action === 'reject' ? 'rejected' : 'pending');
portfolio_typeb::set_status($id, $status, $comment, (int)$USER->id);

redirect(new moodle_url('/local/gestion_actividades/portfolio_admin.php'), $status === 'validated' ? 'Certificado Tipo B validado.' : 'Certificado Tipo B actualizado.', null, \core\output\notification::NOTIFY_SUCCESS);
