<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\portfolio_pdf;

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
$pdf = portfolio_pdf::render_pdf_string((int)$userid);
$filename = portfolio_pdf::filename_for_user($user);

send_file($pdf, $filename, 0, 0, true, true, 'application/pdf');
