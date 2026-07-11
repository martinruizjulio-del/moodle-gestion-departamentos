<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT);
$workshop = manager::get_workshop($id);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);

$canmanage = manager::can_manage_workshop($course, (int)$USER->id);
$edition = manager::get_primary_workshop_edition($id);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $id]));
$PAGE->set_title(format_string($workshop->name));
$PAGE->set_heading(format_string($course->fullname));

$message = null;
$messagetype = 'info';

if ($action === 'enrol' && confirm_sesskey()) {
    if (!$edition) {
        $message = get_string('noeditionavailable', 'local_gestion_actividades');
        $messagetype = 'warning';
    } else {
        $result = manager::enrol_user_in_edition((int)$edition->id, (int)$USER->id, 'self');
        $message = $result->message;
        $messagetype = $result->success ? 'success' : 'warning';
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($workshop->code . ' - ' . $workshop->name));

if ($message) {
    echo $OUTPUT->notification($message, $messagetype);
}

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h4', get_string('workshopinfo', 'local_gestion_actividades'));

$description = trim((string)($workshop->description ?? ''));
if ($description !== '') {
    echo html_writer::tag('p', s($description));
}

$hours = isset($workshop->hours) && $workshop->hours !== null ? round((float)$workshop->hours, 2) . ' h' : '-';
$date = $edition ? manager::format_workshop_date((int)$edition->sessiondate) : '-';
$enrolend = $edition ? manager::format_workshop_date((int)$edition->enrolenddate) : '-';
$places = $edition ? (int)($edition->places ?? 0) : 0;
$enrolled = $edition ? manager::get_edition_enrolment_count((int)$edition->id) : 0;

$table = new html_table();
$table->data = [
    [get_string('date'), $date],
    [get_string('enrolenddate', 'local_gestion_actividades'), $enrolend],
    [get_string('workshophours', 'local_gestion_actividades'), $hours],
    [get_string('places', 'local_gestion_actividades'), $places > 0 ? $enrolled . ' / ' . $places : $enrolled],
];
echo html_writer::table($table);

if (!$edition) {
    echo $OUTPUT->notification(get_string('noeditionavailable', 'local_gestion_actividades'), 'warning');
} else {
    $enrolment = manager::get_edition_enrolment((int)$edition->id, (int)$USER->id);
    if ($enrolment && $enrolment->status === 'enrolled') {
        echo html_writer::div(get_string('alreadyenrolled', 'local_gestion_actividades'), 'local-ga-pill local-ga-pill-ok', ['style' => 'display:inline-block;background:#e9f7ef;border:1px solid #badbcc;border-radius:999px;padding:8px 14px;margin:10px 0;color:#0f5132;font-weight:600;']);
    } else {
        $url = new moodle_url('/local/gestion_actividades/workshop_view.php', [
            'id' => $id,
            'action' => 'enrol',
            'sesskey' => sesskey(),
        ]);
        echo html_writer::link($url, get_string('enrolme', 'local_gestion_actividades'), ['class' => 'btn btn-primary']);
    }
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h4', get_string('workshopresources', 'local_gestion_actividades'));

// materialsview_safe_try
try {
$materials = manager::list_materials((int)$workshop->id, $edition ? (int)$edition->id : 0, true);
if ($materials) {
    echo html_writer::start_tag('ul');
    foreach ($materials as $m) {
        $fileurl = manager::get_material_file_url($m, $context);
        $label = !empty($fileurl) ? html_writer::link($fileurl, s($m->name), ['target' => '_blank']) : (!empty($m->url) ? html_writer::link($m->url, s($m->name), ['target' => '_blank']) : s($m->name));
        echo html_writer::tag('li', $label . (!empty($m->description) ? ' — ' . s($m->description) : ''));
    }
    echo html_writer::end_tag('ul');
}
if ($edition && !empty($edition->requiredcmid)) {
    $modname = manager::get_modname_from_cmid((int)$edition->requiredcmid);
    if ($modname) {
        $url = new moodle_url('/mod/' . $modname . '/view.php', ['id' => $edition->requiredcmid]);
        echo html_writer::tag('p', html_writer::link($url, get_string('openrequiredactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']));
    }
}
if (!$materials && (empty($edition) || empty($edition->requiredcmid))) {
    echo html_writer::tag('p', get_string('studentresourcespending', 'local_gestion_actividades'), ['class' => 'text-muted']);
}
} catch (\Throwable $e) {
    echo html_writer::tag('p', get_string('studentresourcespending', 'local_gestion_actividades'), ['class' => 'text-muted']);
    if (!empty($canmanage)) {
        echo $OUTPUT->notification('Detalle materiales: ' . s($e->getMessage()), 'warning');
    }
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

if ($canmanage) {
    echo html_writer::link(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $id]), get_string('teacherworkshopview', 'local_gestion_actividades'), ['class' => 'btn btn-primary']);
    echo ' ';
}
echo html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), get_string('backtocourse', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);


// certificates_visible_manager_box_v141
try {
    if (!empty($edition) && manager::can_manage_workshop($course, (int)$USER->id)) {
        echo html_writer::start_tag('div', ['class' => 'card mb-3', 'style' => 'border:2px solid #d8e8d0;']);
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('h3', get_string('certificates', 'local_gestion_actividades'));
        echo html_writer::tag('p', get_string('certificates_visible_help', 'local_gestion_actividades'), ['class' => 'text-muted']);
        echo html_writer::link(
            new moodle_url('/local/gestion_actividades/generate_certificates.php', ['id' => $edition->id, 'sesskey' => sesskey()]),
            get_string('generatecertificates', 'local_gestion_actividades'),
            ['class' => 'btn btn-primary']
        );
        echo ' ';
        echo html_writer::link(
            new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $edition->id]),
            get_string('viewgeneratedcertificates', 'local_gestion_actividades'),
            ['class' => 'btn btn-secondary']
        );
        echo ' ';
        echo html_writer::link(
            new moodle_url('/local/gestion_actividades/certificate_template.php'),
            get_string('certificatetemplate', 'local_gestion_actividades'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
} catch (\Throwable $e) {
    // Do not break workshop view if certificate block fails.
}


echo $OUTPUT->footer();


// student_attendance_status_v136
if (!empty($edition)) {
    try {
        if (manager::is_user_attended_edition((int)$edition->id, (int)$USER->id)) {
            echo html_writer::div(
                html_writer::tag('strong', get_string('attendance', 'local_gestion_actividades') . ': ') . get_string('studentattendanceconfirmed', 'local_gestion_actividades'),
                'local-ga-status local-ga-status-ok',
                ['style' => 'display:inline-block;background:#e9f7ef;border:1px solid #badbcc;border-radius:999px;padding:8px 14px;margin:10px 0;color:#0f5132;font-weight:600;']
            );
        } else if (manager::get_user_edition_enrolment((int)$edition->id, (int)$USER->id)) {
            echo html_writer::div(
                html_writer::tag('strong', get_string('attendance', 'local_gestion_actividades') . ': ') . get_string('studentattendancepending', 'local_gestion_actividades'),
                'local-ga-status local-ga-status-pending',
                ['style' => 'display:inline-block;background:#edf4ff;border:1px solid #b6d4fe;border-radius:999px;padding:8px 14px;margin:10px 0;color:#084298;font-weight:600;']
            );
        }
} catch (\Throwable $e) {
        // Do not break student view.
    }
}



// student_certificate_link_v140
if (!empty($edition)) {
    try {
        $cert = manager::get_user_certificate_for_edition((int)$edition->id, (int)$USER->id);
        if ($cert) {
            echo html_writer::div(
                html_writer::tag('strong', get_string('certificate', 'local_gestion_actividades') . ': ') .
                html_writer::link(new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $cert->id]), get_string('downloadcertificate', 'local_gestion_actividades'), ['class' => 'btn btn-primary btn-sm']),
                'local-ga-certificate-link',
                ['style' => 'background:#f7f9fb;border:1px solid #d8dee9;border-radius:10px;padding:12px 14px;margin:12px 0;']
            );
        }
    } catch (\Throwable $e) {
        // Do not break student view.
    }
}

