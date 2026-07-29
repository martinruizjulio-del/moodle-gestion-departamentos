<?php
// Administration settings for Gestion_actividades.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_gestion_actividades',
        get_string('pluginname', 'local_gestion_actividades'),
        new moodle_url('/local/gestion_actividades/index.php'),
        'local/gestion_actividades:manage'
    ));
}


$ADMIN->add('localplugins', new admin_externalpage(
    'local_gestion_actividades_dashboard',
    get_string('dashboard', 'local_gestion_actividades'),
    new moodle_url('/local/gestion_actividades/dashboard.php'),
    'local/gestion_actividades:manage'
));
