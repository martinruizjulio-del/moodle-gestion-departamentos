<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboard', 'local_gestion_actividades'));

echo html_writer::tag('p', get_string('dashboardintro_seq', 'local_gestion_actividades'), ['class' => 'lead']);

echo html_writer::start_div('row');

// Step 1: students.
echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', '1. ' . get_string('studentssection', 'local_gestion_actividades'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('studentssection_desc', 'local_gestion_actividades'), ['class' => 'card-text']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/index.php'), get_string('openstudentssection', 'local_gestion_actividades'), ['class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Step 2: workshops.
echo html_writer::start_div('col-md-6 mb-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', '2. ' . get_string('workshopssection', 'local_gestion_actividades'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('workshopssection_desc', 'local_gestion_actividades'), ['class' => 'card-text']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('openworkshopssection', 'local_gestion_actividades'), ['class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::tag('h3', get_string('workshopoverview', 'local_gestion_actividades'));
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/archive.php'), get_string('openworkshoparchive', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']), 'mb-3');

$allrows = manager::get_workshop_overview_rows();
$rows = [];
foreach ($allrows as $r) {
    if (!in_array($r->computedstatus, ['archived', 'past'])) {
        $rows[] = $r;
    }
}

if (!$rows) {
    echo $OUTPUT->notification(get_string('noworkshopeditionsyet', 'local_gestion_actividades'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('status'),
        get_string('workshopcode', 'local_gestion_actividades'),
        get_string('workshopname', 'local_gestion_actividades'),
        get_string('editioncode', 'local_gestion_actividades'),
        get_string('workshophours', 'local_gestion_actividades'),
        get_string('date'),
        get_string('enrolenddate', 'local_gestion_actividades'),
        get_string('places', 'local_gestion_actividades'),
        get_string('enrolledstudents', 'local_gestion_actividades'),
        get_string('teachers', 'local_gestion_actividades'),
        get_string('group'),
        get_string('actions'),
    ];

    foreach ($rows as $row) {
        $statuslabel = get_string('status_' . $row->computedstatus, 'local_gestion_actividades');
        $actions = html_writer::link(new moodle_url('/local/gestion_actividades/edition_edit.php', ['id' => $row->id, 'workshopid' => $DB->get_field('local_ga_workshop_editions', 'workshopid', ['id' => $row->id])]), get_string('edit')) . ' | ' .
                   html_writer::link(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $row->id]), get_string('studentsmanualandstatus', 'local_gestion_actividades')) . ' | ' .
                   html_writer::link(new moodle_url('/local/gestion_actividades/edition_sync.php', ['id' => $row->id]), get_string('synceditionenrolments', 'local_gestion_actividades'));
        $table->data[] = [
            $statuslabel,
            s($row->workshopcode),
            format_string($row->workshopname),
            s($row->editioncode),
            isset($row->workshophours) && $row->workshophours !== null ? round($row->workshophours, 2) : '-',
            $row->sessiondate ? manager::format_date_compact((int)$row->sessiondate) : '-',
            $row->enrolenddate ? manager::format_date_compact((int)$row->enrolenddate) : '-',
            $row->places,
            $row->enrolledcount,
            $row->teachers ?: '-',
            $row->groupname ?: '-',
            $actions,
        ];
    }

    echo html_writer::table($table);
}


echo html_writer::tag('h3', get_string('authorizedusers', 'local_gestion_actividades'));
echo html_writer::tag('p', get_string('authorizedusers_desc', 'local_gestion_actividades'), ['class' => 'alert alert-info']);
$authorized = manager::get_authorized_managers();
if (!$authorized) {
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/authorized_users.php'), get_string('manageauthorizedusers', 'local_gestion_actividades'), ['class' => 'btn btn-primary']), 'mb-3');

    echo $OUTPUT->notification(get_string('noauthorizedusers', 'local_gestion_actividades'), 'info');
} else {
    $authtable = new html_table();
    $authtable->head = [get_string('fullname'), 'Email', get_string('rolepermission', 'local_gestion_actividades')];
    foreach ($authorized as $authuser) {
        $authtable->data[] = [fullname($authuser), s($authuser->email), get_string('managepermission', 'local_gestion_actividades')];
    }
    echo html_writer::table($authtable);
}

echo html_writer::tag('h3', get_string('hoursbystudent', 'local_gestion_actividades'));
echo html_writer::div(
    html_writer::link(new moodle_url('/local/gestion_actividades/hours_report.php'), get_string('openhoursreport', 'local_gestion_actividades'), ['class' => 'btn btn-primary']) . ' ' .
    html_writer::link(new moodle_url('/local/gestion_actividades/myhours.php'), get_string('openmyhours', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']),
    'mb-3'
);

echo html_writer::tag('h2', 'Portafolio de certificados');
echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('p', 'Consulta y gestión de certificados: Tipo A generado por el sistema y Tipo B preparado para subida futura del alumno.', ['class' => 'card-text']);
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php'), 'Portafolio gestor', ['class' => 'btn btn-primary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio.php'), 'Mi portafolio', ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/certificate_template.php'), get_string('certificatetemplate', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');


echo html_writer::tag('h4', get_string('recommendedflow', 'local_gestion_actividades'));
echo html_writer::tag('ol',
    html_writer::tag('li', get_string('flow_students', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_workshops', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_editions', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_enrolments', 'local_gestion_actividades')) .
    html_writer::tag('li', get_string('flow_attendance_activity_certificate', 'local_gestion_actividades'))
);

echo $OUTPUT->footer();
