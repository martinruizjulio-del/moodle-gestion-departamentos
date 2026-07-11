<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\form\upload_form;
use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = required_param('id', PARAM_INT);
$activity = manager::get_activity($id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/upload.php', ['id' => $id]));
$PAGE->set_title(get_string('uploadcsv', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

$form = new upload_form(null, ['activity' => $activity]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]));
} else if ($data = $form->get_data()) {
    $filepath = $form->save_temp_file('csvfile');
    if (!$filepath) {
        throw new moodle_exception('No se ha podido guardar temporalmente el CSV.');
    }
    $filename = '';
    $draftid = file_get_submitted_draft_itemid('csvfile');
    $fs = get_file_storage();
    $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftid, 'id', false);
    if ($files) {
        $file = reset($files);
        $filename = $file->get_filename();
    }
    $summary = manager::process_csv($activity, $filepath, $filename, $data->gradecolumn, !empty($data->creategroup), !empty($data->createmissingusers), trim((string)$data->academicyear), !empty($data->savegradehistory), !empty($data->updategradebook), trim((string)($data->gradeitemname ?? '')));
    $message = get_string('importsummary', 'local_gestion_actividades', $summary);
    if (!empty($summary->groupid)) {
        $message .= ' ' . get_string('groupcreated', 'local_gestion_actividades', $summary->groupid);
    } else {
        $message .= ' ' . get_string('groupnotcreated', 'local_gestion_actividades');
    }
    redirect(new moodle_url('/local/gestion_actividades/view.php', ['id' => $id]), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($activity->name) . ': ' . get_string('uploadcsv', 'local_gestion_actividades'));
echo html_writer::div('CSV esperado: columna identificadora (' . s($activity->idfield) . ') y columna de nota (nota o grade). Separador aceptado: coma o punto y coma.', 'alert alert-info');
$form->display();
echo $OUTPUT->footer();
