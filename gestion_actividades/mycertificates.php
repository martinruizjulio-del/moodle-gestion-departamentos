<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/gestion_actividades/mycertificates.php'));
$PAGE->set_title(get_string('mycertificates', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('mycertificates', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mycertificates', 'local_gestion_actividades'));

$certificates = manager::list_user_certificates((int)$USER->id);
if ($certificates) {
    $table = new html_table();
    $table->head = [get_string('course'), get_string('workshop', 'local_gestion_actividades'), get_string('hours', 'local_gestion_actividades'), get_string('timeissued', 'local_gestion_actividades'), get_string('actions')];
    foreach ($certificates as $c) {
        $url = new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $c->id]);
        $table->data[] = [
            format_string($c->coursename),
            s($c->workshopcode . ' - ' . $c->workshopname),
            !empty($c->hours) ? s((float)$c->hours) . ' h' : '-',
            userdate((int)$c->timeissued),
            html_writer::link($url, get_string('downloadcertificate', 'local_gestion_actividades'), ['class' => 'btn btn-primary btn-sm'])
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nomycertificatesyet', 'local_gestion_actividades'), 'info');
}

echo $OUTPUT->footer();
