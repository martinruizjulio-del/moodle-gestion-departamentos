<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

$id = required_param('id', PARAM_INT); // edition id
$go = optional_param('go', 0, PARAM_BOOL);
$linkcmid = optional_param('linkcmid', 0, PARAM_INT);
$unlink = optional_param('unlink', 0, PARAM_BOOL);

require_login();

$edition = manager::get_workshop_edition($id);
$workshop = manager::get_workshop((int)$edition->workshopid);
$course = $DB->get_record('course', ['id' => $workshop->courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

if (!manager::can_manage_workshop($course, (int)$USER->id)) {
    throw new required_capability_exception($coursecontext, 'moodle/course:update', 'nopermissions', '');
}

function local_ga_task_get_valid_cm(int $cmid, int $courseid): ?stdClass {
    global $DB;
    if ($cmid <= 0) {
        return null;
    }
    $sql = "SELECT cm.id, cm.course, cm.instance, cm.module, cm.deletioninprogress, m.name AS modname
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module
             WHERE cm.id = :cmid";
    $cm = $DB->get_record_sql($sql, ['cmid' => $cmid]);
    if (!$cm || (int)$cm->course !== (int)$courseid || !empty($cm->deletioninprogress)) {
        return null;
    }
    if (!in_array($cm->modname, ['assign', 'quiz'], true)) {
        return null;
    }
    if (!$DB->get_manager()->table_exists(new xmldb_table($cm->modname))) {
        return null;
    }
    if (!$DB->record_exists($cm->modname, ['id' => (int)$cm->instance])) {
        return null;
    }
    return $cm;
}

function local_ga_task_update_required_cmid(int $editionid, int $cmid): void {
    global $DB;
    $record = (object)[
        'id' => $editionid,
        'requiredcmid' => $cmid,
        'timemodified' => time(),
    ];
    $columns = $DB->get_columns('local_ga_workshop_editions');
    if (isset($columns['requiredmodname'])) {
        $record->requiredmodname = $cmid > 0 ? manager::get_modname_from_cmid($cmid) : '';
    }
    $DB->update_record('local_ga_workshop_editions', manager::filter_record_to_existing_fields('local_ga_workshop_editions', $record));
}

$staleactivity = false;
$currentcm = !empty($edition->requiredcmid) ? local_ga_task_get_valid_cm((int)$edition->requiredcmid, (int)$course->id) : null;
if (!empty($edition->requiredcmid) && !$currentcm) {
    $staleactivity = true;
}

if ($unlink && confirm_sesskey()) {
    local_ga_task_update_required_cmid((int)$edition->id, 0);
    redirect(new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]), 'Actividad requerida desvinculada. Ahora puedes seleccionar o crear una tarea/cuestionario válido.', null, \core\output\notification::NOTIFY_SUCCESS);
}

// If already linked and valid, open Moodle's native edit form rather than a potentially broken view URL.
if ($currentcm) {
    redirect(new moodle_url('/course/modedit.php', ['update' => (int)$currentcm->id, 'return' => 1]));
}

if (!empty($linkcmid) && confirm_sesskey()) {
    $linkedcm = local_ga_task_get_valid_cm((int)$linkcmid, (int)$course->id);
    if ($linkedcm) {
        local_ga_task_update_required_cmid((int)$id, (int)$linkedcm->id);
        redirect(
            new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshop->id]),
            get_string('requiredactivitylinked', 'local_gestion_actividades')
        );
    }
    redirect(
        new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]),
        'La actividad seleccionada no es una tarea/cuestionario válido de este curso. Selecciona otra o crea una nueva.',
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$type = manager::detect_required_activity_type($edition);
if ($type === '') {
    $type = 'assign';
}
if ($type !== 'quiz') {
    $type = 'assign';
}

$sectionnum = manager::get_or_create_course_section((int)$course->id, manager::get_main_workshop_section_name());

// If a likely activity already exists, link it automatically and return to teacher view.
$candidates = array_values(array_filter(manager::find_candidate_required_activities($edition), function($candidate) use ($course) {
    return !empty($candidate->cmid) && local_ga_task_get_valid_cm((int)$candidate->cmid, (int)$course->id);
}));
if (!$go && !$staleactivity && count($candidates) === 1) {
    $candidate = reset($candidates);
    local_ga_task_update_required_cmid((int)$id, (int)$candidate->cmid);
    redirect(
        new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshop->id]),
        get_string('requiredactivitylinked', 'local_gestion_actividades')
    );
}

if ($go && confirm_sesskey()) {
    if (!$DB->record_exists('modules', ['name' => $type])) {
        redirect(
            new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]),
            get_string('requiredmodnotavailable', 'local_gestion_actividades') . ': ' . $type,
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    // Open Moodle's native creation form inside TALLERES TIPO A section.
    redirect(new moodle_url('/course/modedit.php', [
        'add' => $type,
        'type' => '',
        'course' => $course->id,
        'section' => $sectionnum,
        'return' => 1,
        'sr' => $sectionnum,
    ]));
}

$PAGE->set_context($coursecontext);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id]));
$PAGE->set_title(get_string('configuretaskquiz', 'local_gestion_actividades'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), '← Volver al panel', ['class' => 'btn btn-outline-secondary mb-3']), '');
echo $OUTPUT->heading(get_string('configuretaskquiz', 'local_gestion_actividades') . ': ' . format_string($workshop->code . ' - ' . $workshop->name));

$typename = $type === 'quiz' ? get_string('quiz', 'quiz') : get_string('modulename', 'assign');

if ($staleactivity) {
    echo $OUTPUT->notification('La actividad requerida guardada para este taller ya no es válida o no pertenece a este curso. Se mantiene el taller, pero debes desvincularla y seleccionar/crear una tarea o cuestionario válido.', 'warning');
    echo html_writer::link(new moodle_url('/local/gestion_actividades/task_activity.php', ['id' => $id, 'unlink' => 1, 'sesskey' => sesskey()]), 'Desvincular actividad no válida', ['class' => 'btn btn-warning mb-3']);
}

if ($candidates && count($candidates) > 1) {
    echo $OUTPUT->notification(get_string('chooseactivitytolink', 'local_gestion_actividades'), 'info');
} else {
    echo $OUTPUT->notification(get_string('nativeactivitycreationselected', 'local_gestion_actividades', $typename), 'info');
}

$createurl = new moodle_url('/local/gestion_actividades/task_activity.php', [
    'id' => $id,
    'go' => 1,
    'sesskey' => sesskey(),
]);
echo html_writer::link($createurl, get_string('opennativeactivityform', 'local_gestion_actividades', $typename), ['class' => 'btn btn-primary']);

echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/teacher_view.php', ['id' => $workshop->id]), get_string('teacherworkshopview', 'local_gestion_actividades'), ['class' => 'btn btn-secondary']);

if ($candidates) {
    echo html_writer::start_tag('div', ['class' => 'card mt-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h3', get_string('linkexistingrequiredactivity', 'local_gestion_actividades'));
    $table = new html_table();
    $table->head = [get_string('name'), get_string('type'), get_string('actions')];
    foreach ($candidates as $candidate) {
        $linkurl = new moodle_url('/local/gestion_actividades/task_activity.php', [
            'id' => $id,
            'linkcmid' => $candidate->cmid,
            'sesskey' => sesskey(),
        ]);
        $table->data[] = [
            format_string($candidate->activityname),
            s($candidate->modname),
            html_writer::link($linkurl, get_string('linkthisactivity', 'local_gestion_actividades'), ['class' => 'btn btn-secondary btn-sm'])
        ];
    }
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo $OUTPUT->footer();
