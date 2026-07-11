<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\form\user_upload_form;
use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/users.php'));
$PAGE->set_title(get_string('bulkcreateusers', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

$form = new user_upload_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/gestion_actividades/index.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bulkcreateusers', 'local_gestion_actividades'));
echo html_writer::link(new moodle_url('/local/gestion_actividades/index.php'), get_string('return', 'local_gestion_actividades'), ['class' => 'btn btn-secondary mb-3']);
echo ' ' . html_writer::link(new moodle_url('/local/gestion_actividades/template.php', ['type' => 'users']), get_string('downloadusertemplate', 'local_gestion_actividades'), ['class' => 'btn btn-outline-secondary mb-3']);

echo html_writer::div(get_string('bulkcreateusersinfo', 'local_gestion_actividades'), 'alert alert-info');

if ($data = $form->get_data()) {
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

    $summary = manager::process_users_csv($filepath, $filename, !empty($data->updateexisting));
    echo $OUTPUT->notification(get_string('usersimportsummary', 'local_gestion_actividades', $summary), 'success');

    $table = new html_table();
    $table->head = ['Fila', 'ID Moodle', 'Email', 'Username', get_string('fullname', 'local_gestion_actividades'), get_string('status', 'local_gestion_actividades'), get_string('reason', 'local_gestion_actividades')];
    $table->data = [];
    foreach ($summary->rows as $row) {
        $table->data[] = [
            $row->row,
            $row->userid ?: '-',
            s($row->email),
            s($row->username),
            s(trim($row->firstname . ' ' . $row->lastname)),
            s($row->status),
            s($row->message),
        ];
    }
    echo html_writer::table($table);
} else {
    $form->display();
}

echo $OUTPUT->footer();
