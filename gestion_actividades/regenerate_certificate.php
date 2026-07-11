<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // certificate id
require_login();
require_capability('local/gestion_actividades:manage', context_system::instance());
require_sesskey();

$cert = $DB->get_record('local_ga_certificates', ['id' => $id], '*', MUST_EXIST);
$DB->delete_records('local_ga_certificates', ['id' => $id]);

$newcert = manager::generate_certificate_for_user((int)$cert->editionid, (int)$cert->userid);

if (!$newcert) {
    redirect(
        new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $cert->editionid]),
        get_string('certificateregeneratefailed', 'local_gestion_actividades'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

redirect(
    new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $cert->editionid]),
    get_string('certificateregenerated', 'local_gestion_actividades'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
