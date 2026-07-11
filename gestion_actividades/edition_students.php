<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$syscontext = context_system::instance();
$id = required_param('id', PARAM_INT);
$studentq = optional_param('studentq', '', PARAM_TEXT);
$manualadd = optional_param('manualadd', 0, PARAM_INT);
$markattendance = optional_param('markattendance', 0, PARAM_INT);
$attended = optional_param('attended', 0, PARAM_BOOL);

try {
    $edition = manager::get_workshop_edition($id);
    $workshop = manager::get_workshop((int)$edition->workshopid);
    $course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);

    if (!manager::can_manage_workshop($course, (int)$USER->id)) {
        throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
    }

    if (!empty($manualadd) && confirm_sesskey()) {
        manager::enrol_user_in_edition((int)$id, (int)$manualadd, 'manual');
        redirect(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $id]), get_string('enrolledok', 'local_gestion_actividades'));
    }

    if (!empty($markattendance) && confirm_sesskey()) {
        manager::set_enrolment_attendance((int)$markattendance, (bool)$attended, (int)$USER->id);
        redirect(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $id, 't' => time()]), get_string('attendancesaved', 'local_gestion_actividades'));
    }

    $PAGE->set_context($coursecontext);
    $PAGE->set_course($course);
    $PAGE->set_url(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $id]));
    $PAGE->set_title(get_string('enrolledstudentsattendance', 'local_gestion_actividades'));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('enrolledstudentsattendance', 'local_gestion_actividades') . ': ' . format_string($workshop->code . ' - ' . $workshop->name));

    echo html_writer::start_tag('div', ['class' => 'card mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h3', get_string('clickattendance', 'local_gestion_actividades'));

    $students = [];
    try {
        $students = manager::list_edition_enrolled_users_ultrasafe((int)$id);
    } catch (\Throwable $e) {
        echo $OUTPUT->notification(get_string('attendance_read_error', 'local_gestion_actividades') . ': ' . s($e->getMessage()), 'warning');
    }

    if ($students) {
        $table = new html_table();
        $table->head = [
            get_string('lastname'),
            get_string('firstname'),
            get_string('email'),
            get_string('attendance', 'local_gestion_actividades'),
            get_string('actions')
        ];
        foreach ($students as $s) {
            $isattended = !empty($s->attended);
            $toggleurl = new moodle_url('/local/gestion_actividades/edition_students.php', [
                'id' => $id,
                'markattendance' => $s->enrolmentid,
                'attended' => $isattended ? 0 : 1,
                'sesskey' => sesskey(),
            ]);
            $status = $isattended
                ? html_writer::span(get_string('attended', 'local_gestion_actividades'), 'badge badge-success')
                : html_writer::span(get_string('notattended', 'local_gestion_actividades'), 'badge badge-secondary');
            $button = html_writer::link(
                $toggleurl,
                $isattended ? get_string('marknotattended', 'local_gestion_actividades') : get_string('markattended', 'local_gestion_actividades'),
                ['class' => $isattended ? 'btn btn-warning btn-sm' : 'btn btn-success btn-sm']
            );
            $table->data[] = [s($s->lastname), s($s->firstname), s($s->email), $status, $button];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('noenrolledstudentsyet', 'local_gestion_actividades'), 'info');
    }

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', ['class' => 'card mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h3', get_string('addmanualstudent', 'local_gestion_actividades'));
    echo html_writer::tag('p', get_string('manualstudent_rolefilter_help', 'local_gestion_actividades'), ['class' => 'text-muted']);

    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
    echo html_writer::label(get_string('studentautocomplete', 'local_gestion_actividades'), 'studentq');
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'studentq', 'id' => 'studentq', 'value' => s($studentq), 'class' => 'form-control', 'style' => 'max-width:520px', 'autocomplete' => 'on']);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search'), 'class' => 'btn btn-primary mt-2']);
    echo html_writer::end_tag('form');

    if (trim($studentq) !== '') {
        try {
            $results = manager::search_course_students((int)$course->id, $studentq);
            if ($results) {
                $stable = new html_table();
                $stable->head = [get_string('user'), get_string('email'), get_string('actions')];
                foreach ($results as $su) {
                    $addurl = new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $id, 'manualadd' => $su->id, 'sesskey' => sesskey()]);
                    $stable->data[] = [fullname($su), s($su->email), html_writer::link($addurl, get_string('addstudenttoworkshop', 'local_gestion_actividades'), ['class' => 'btn btn-secondary btn-sm'])];
                }
                echo html_writer::table($stable);
            } else {
                echo $OUTPUT->notification(get_string('nostudentsfoundcourse', 'local_gestion_actividades'), 'info');
            }
        } catch (\Throwable $e) {
            echo $OUTPUT->notification(get_string('student_search_error', 'local_gestion_actividades') . ': ' . s($e->getMessage()), 'warning');
        }
    }

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo html_writer::link(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshop->id]), get_string('teacherworkshopview', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
    echo ' ';
    echo html_writer::link(new moodle_url('/local/gestion_actividades/workshop_view.php', ['id' => $workshop->id]), get_string('viewworkshop', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);

    echo $OUTPUT->footer();

} catch (\Throwable $e) {
    $PAGE->set_context($syscontext);
    $PAGE->set_url(new moodle_url('/local/gestion_actividades/edition_students.php', ['id' => $id]));
    $PAGE->set_title(get_string('enrolledstudentsattendance', 'local_gestion_actividades'));
    $PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('enrolledstudentsattendance', 'local_gestion_actividades'));
    echo $OUTPUT->notification(get_string('attendance_page_error', 'local_gestion_actividades') . ': ' . s($e->getMessage()), 'error');
    echo html_writer::link(new moodle_url('/local/gestion_actividades/workshops.php'), get_string('workshops', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);
    echo $OUTPUT->footer();
}
