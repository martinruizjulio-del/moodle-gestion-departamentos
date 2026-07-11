<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT);
$workshop = manager::get_workshop($id);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
require_login($course);

// teacher_view_switchedrole_guard
if (function_exists('is_role_switched') && is_role_switched($course->id)) {
    throw new required_capability_exception(context_course::instance($course->id), 'moodle/course:update', 'nopermissions', '');
}

$coursecontext = context_course::instance($course->id);
$syscontext = context_system::instance();
if (!manager::can_manage_workshop($course, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}
$edition = manager::get_primary_workshop_edition($id);
$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'complete' && $edition && confirm_sesskey()) {
    manager::mark_edition_completed((int)$edition->id, (int)$USER->id);
    redirect(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $id]), get_string('workshopmarkedcompleted', 'local_gestion_actividades'));
}

$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $id]));
$PAGE->set_title(get_string('teacherworkshopview', 'local_gestion_actividades'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('teacherworkshopview', 'local_gestion_actividades') . ': ' . format_string($workshop->code . ' - ' . $workshop->name));



// certificates_top_manager_box_v141
if (!empty($edition)) {
    echo html_writer::start_tag('div', ['class' => 'card mb-3', 'style' => 'border:2px solid #d8e8d0;']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h3', get_string('certificates', 'local_gestion_actividades'));
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
    echo html_writer::tag('p', get_string('certificates_visible_help', 'local_gestion_actividades'), ['class' => 'text-muted mt-2']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

if ($edition && !empty($edition->completed)) {
    echo $OUTPUT->notification(get_string('workshopalreadycompleted', 'local_gestion_actividades'), 'success');
}

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h3', get_string('materialsfiles', 'local_gestion_actividades'));
echo html_writer::link(new moodle_url('/local/gestion_actividades/material_edit.php', ['workshopid' => $id, 'editionid' => $edition ? $edition->id : 0]), get_string('addmaterial', 'local_gestion_actividades'), ['class' => 'btn btn-primary mb-2']);

$materials = manager::list_materials($id, $edition ? (int)$edition->id : 0, false);
if ($materials) {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('description'), get_string('visible'), get_string('actions')];
    foreach ($materials as $m) {
        $editurl = new moodle_url('/local/gestion_actividades/material_edit.php', ['id' => $m->id, 'workshopid' => $id, 'editionid' => $edition ? $edition->id : 0]);
        $fileurl = manager::get_material_file_url($m, $coursecontext);
        $link = !empty($fileurl) ? html_writer::link($fileurl, s($m->name), ['target' => '_blank']) : (!empty($m->url) ? html_writer::link($m->url, s($m->name), ['target' => '_blank']) : s($m->name));
        $table->data[] = [
            $link,
            s($m->description),
            !empty($m->visible) ? get_string('yes') : get_string('no'),
            html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-secondary btn-sm'])
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nomaterialsyet', 'local_gestion_actividades'), 'info');
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h3', get_string('assignmentquiz', 'local_gestion_actividades'));
// requiredactivity_group_warning
if ($edition && empty($edition->groupid)) {
    echo $OUTPUT->notification(get_string('requiredactivitynogroupwarning', 'local_gestion_actividades'), 'warning');
}
if ($edition && !empty($edition->requiredcmid)) {
    try {
        $modname = manager::get_modname_from_cmid((int)$edition->requiredcmid);
        if ($modname) {
            $url = new moodle_url('/mod/' . $modname . '/view.php', ['id' => $edition->requiredcmid]);
            echo html_writer::link($url, get_string('openrequiredactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
        } else {
            echo html_writer::tag('p', get_string('requiredactivitycleanhelp', 'local_gestion_actividades'), ['class' => 'text-muted']);
        }
    } catch (\Throwable $e) {
        echo html_writer::tag('p', get_string('requiredactivitycleanhelp', 'local_gestion_actividades'), ['class' => 'text-muted']);
    }
} else {
    $configuredmsg = get_string('requiredactivityconfiguredautocreate', 'local_gestion_actividades');
    if ($edition) {
        foreach (['requiredactivitytype', 'activitytype', 'completiontype', 'requiredtype', 'tasktype'] as $field) {
            if (!empty($edition->$field)) {
                $configuredmsg = get_string('requiredactivityconfigured', 'local_gestion_actividades') . ': ' . s($edition->$field);
                break;
            }
        }
        if (!empty($edition->createassignment) || !empty($edition->createquiz) || !empty($edition->autocreateactivity)) {
            $configuredmsg = get_string('requiredactivityconfigured', 'local_gestion_actividades');
        }
    }
    echo html_writer::tag('p', get_string('requiredactivitycleanhelp', 'local_gestion_actividades'), ['class' => 'text-muted']);
}
echo ' ';
if ($edition) {
    echo html_writer::link(new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $edition->id]), get_string('manageconfiguredactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/repair_required_activity.php', ['id' => $edition->id, 'sesskey' => sesskey()]), get_string('repairrequiredactivityrestriction', 'local_gestion_actividades'), ['class' => 'btn btn-warning']);

}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h3', get_string('studentsandattendance', 'local_gestion_actividades'));
if ($edition) {
    echo html_writer::link(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $edition->id]), get_string('enrolledstudentsattendance', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
    if (!empty($edition->attendancecmid)) {
        try {
            $modname = manager::get_modname_from_cmid((int)$edition->attendancecmid);
            if ($modname) {
                echo ' ';
                echo html_writer::link(new moodle_url('/mod/' . $modname . '/view.php', ['id' => $edition->attendancecmid]), get_string('openattendance', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
            }
        } catch (\Throwable $e) {
            echo $OUTPUT->notification(get_string('attendance_read_error', 'local_gestion_actividades') . ': ' . s($e->getMessage()), 'warning');
        }
    }

    echo html_writer::tag('h4', get_string('attendancelist', 'local_gestion_actividades'), ['class' => 'mt-3']);
    try {
        $enrolledusers = manager::list_edition_enrolled_users_ultrasafe((int)$edition->id);
        if ($enrolledusers) {
            $atable = new html_table();
            $atable->head = [get_string('lastname'), get_string('firstname'), get_string('email'), get_string('attendance', 'local_gestion_actividades')];
            foreach ($enrolledusers as $eu) {
                $atable->data[] = [s($eu->lastname), s($eu->firstname), s($eu->email), !empty($eu->attended) ? get_string('attended', 'local_gestion_actividades') : get_string('notattended', 'local_gestion_actividades')];
            }
            echo html_writer::table($atable);
        } else {
            echo $OUTPUT->notification(get_string('noenrolledstudentsyet', 'local_gestion_actividades'), 'info');
        }
    } catch (\Throwable $e) {
        echo $OUTPUT->notification(get_string('attendance_read_error', 'local_gestion_actividades') . ': ' . s($e->getMessage()), 'warning');
    }
} else {
    echo $OUTPUT->notification(get_string('noeditionavailable', 'local_gestion_actividades'), 'warning');
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');


// certificates_teacher_card_v140
echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h3', get_string('certificates', 'local_gestion_actividades'));
if ($edition) {
    echo html_writer::link(new moodle_url('/local/gestion_actividades/generate_certificates.php', ['id' => $edition->id, 'sesskey' => sesskey()]), get_string('generatecertificates', 'local_gestion_actividades'), ['class' => 'btn btn-primary']);
    echo ' ';
    echo html_writer::link(new moodle_url('/local/gestion_actividades/certificates.php', ['editionid' => $edition->id]), get_string('viewgeneratedcertificates', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
    echo ' ';
    echo html_writer::link(new moodle_url('/local/gestion_actividades/certificate_template.php'), get_string('certificatetemplate', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
    echo html_writer::tag('p', get_string('certificates_help', 'local_gestion_actividades'), ['class' => 'text-muted mt-2']);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'card mb-3']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h3', get_string('finishworkshop', 'local_gestion_actividades'));

if ($edition) {
    echo html_writer::link(
        new moodle_url('/local/gestion_actividades/finish_workshop.php', ['id' => $edition->id, 'sesskey' => sesskey()]),
        get_string('finishandarchiveworkshop', 'local_gestion_actividades'),
        ['class' => 'btn btn-danger']
    );
}

echo html_writer::tag('p', get_string('finishworkshop_help', 'local_gestion_actividades'));
if ($edition) {
    $completeurl = new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $id, 'action' => 'complete', 'sesskey' => sesskey()]);
    echo html_writer::link($completeurl, get_string('markworkshopcompleted', 'local_gestion_actividades'), ['class' => 'btn btn-danger']);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::link(new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $id]), get_string('viewworkshop'), ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('workshops', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);

echo $OUTPUT->footer();
