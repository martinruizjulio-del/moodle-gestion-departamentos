<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
if (!manager::can_manage_globally((int)$USER->id)) {
    throw new required_capability_exception(context_system::instance(), 'local/gestion_actividades:manage', 'nopermissions', '');
}

$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$q = optional_param('q', '', PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname, shortname', 0, 200);
if ($courseid <= 0) {
    $fw = $DB->get_record_sql("SELECT courseid FROM {local_ga_workshops} ORDER BY id DESC", [], IGNORE_MULTIPLE);
    $courseid = $fw ? (int)$fw->courseid : 0;
}

if ($action === 'add' && confirm_sesskey() && $userid > 0) {
    manager::add_authorized_user($userid, $USER->id);
    redirect(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid' => $courseid]), get_string('authorizeduseradded', 'local_gestion_actividades'));
}
if ($action === 'remove' && confirm_sesskey() && $userid > 0) {
    manager::remove_authorized_user($userid);
    redirect(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid' => $courseid]), get_string('authorizeduserremoved', 'local_gestion_actividades'));
}

function local_ga_auth_label(stdClass $user): string {
    return fullname($user) . ' <' . $user->email . '>';
}

function local_ga_auth_extract_email(string $text): string {
    if (preg_match('/<([^>]+)>/', $text, $matches)) {
        return trim($matches[1]);
    }
    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches)) {
        return trim($matches[0]);
    }
    return '';
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('authorizedusers', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

echo $OUTPUT->header();

$course = $courseid > 0 ? $DB->get_record('course', ['id' => $courseid], 'id,fullname,shortname', IGNORE_MISSING) : null;
echo html_writer::start_div('mb-3');
if ($course) {
    echo html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver al curso', ['class' => 'btn btn-outline-secondary mr-2']);
}
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php', $courseid > 0 ? ['courseid' => $courseid] : []), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver al panel', ['class' => 'btn btn-secondary']);
echo html_writer::end_div();

echo $OUTPUT->heading(get_string('authorizedusers', 'local_gestion_actividades'));
echo html_writer::tag('p', get_string('authorizedusers_teacherfilter_help', 'local_gestion_actividades'), ['class' => 'alert alert-info']);

$suggestions = ($courseid > 0) ? manager::search_course_teachers($courseid, '') : [];
$results = [];
$qtrim = trim($q);
if ($courseid > 0 && $qtrim !== '') {
    $results = manager::search_course_teachers($courseid, $qtrim);

    // Strong fallback for datalist selections such as "Usuario Prueba1 <prueba@prueba1.es>".
    // If Moodle/PARAM_TEXT/theme handling prevents the SQL search from matching, compare
    // against the already loaded course teacher list and inject the exact candidate.
    $needle = \core_text::strtolower($qtrim);
    $email = local_ga_auth_extract_email($qtrim);
    $emailneedle = $email !== '' ? \core_text::strtolower($email) : '';
    foreach ($suggestions as $candidate) {
        $label = local_ga_auth_label($candidate);
        $labelneedle = \core_text::strtolower($label);
        $candemail = \core_text::strtolower((string)$candidate->email);
        if ($labelneedle === $needle || ($emailneedle !== '' && $candemail === $emailneedle) || strpos($labelneedle, $needle) !== false) {
            $results[$candidate->id] = $candidate;
        }
    }

    // Last resort: if the user typed/pasted an email of a valid user, show it so the
    // administrator can authorize it instead of getting stuck without an add button.
    if (!$results && $email !== '') {
        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0, 'confirmed' => 1], 'id,firstname,lastname,email', IGNORE_MISSING);
        if ($user) {
            $results[$user->id] = $user;
        }
    }
}

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
echo html_writer::label(get_string('course'), 'courseid');
echo html_writer::start_tag('select', ['name' => 'courseid', 'id' => 'courseid', 'class' => 'form-control', 'style' => 'max-width:520px']);
foreach ($courses as $c) {
    echo html_writer::tag('option', format_string($c->fullname) . ' [' . s($c->shortname) . '] — ID ' . $c->id, ['value' => $c->id, 'selected' => ((int)$c->id === (int)$courseid) ? 'selected' : null]);
}
echo html_writer::end_tag('select');
echo html_writer::label(get_string('searchteacherautocomplete', 'local_gestion_actividades'), 'q', false, ['class' => 'mt-2']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'id' => 'q', 'value' => s($q), 'class' => 'form-control', 'style' => 'max-width:520px', 'list' => 'teacher-suggestions', 'autocomplete' => 'on']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search'), 'class' => 'btn btn-primary mt-2']);
echo html_writer::end_tag('form');

echo html_writer::start_tag('datalist', ['id' => 'teacher-suggestions']);
foreach ($suggestions as $u) {
    echo html_writer::tag('option', '', ['value' => local_ga_auth_label($u)]);
}
echo html_writer::end_tag('datalist');

if ($qtrim !== '') {
    echo $OUTPUT->heading(get_string('searchresults'), 3);
    if ($results) {
        $table = new html_table();
        $table->head = [get_string('user'), get_string('email'), get_string('actions')];
        foreach ($results as $u) {
            $addurl = new moodle_url('/local/gestion_actividades/authorized_users.php', ['action' => 'add', 'userid' => $u->id, 'courseid' => $courseid, 'sesskey' => sesskey()]);
            $table->data[] = [fullname($u), s($u->email), html_writer::link($addurl, get_string('addauthorizeduser', 'local_gestion_actividades'), ['class' => 'btn btn-primary btn-sm'])];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('noteachersfoundcourse', 'local_gestion_actividades'), 'info');
    }
}

echo $OUTPUT->heading(get_string('currentauthorizedusers', 'local_gestion_actividades'), 3);
$users = manager::list_authorized_users();
if ($users) {
    $table = new html_table();
    $table->head = [get_string('user'), get_string('email'), get_string('actions')];
    foreach ($users as $u) {
        $removeurl = new moodle_url('/local/gestion_actividades/authorized_users.php', ['action' => 'remove', 'userid' => $u->id, 'courseid' => $courseid, 'sesskey' => sesskey()]);
        $table->data[] = [fullname($u), s($u->email), html_writer::link($removeurl, get_string('remove'), ['class' => 'btn btn-danger btn-sm'])];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('noauthorizedusers', 'local_gestion_actividades'), 'info');
}

if (function_exists('local_gestion_actividades_enable_interactive_tables')) {
    local_gestion_actividades_enable_interactive_tables();
}
echo $OUTPUT->footer();
