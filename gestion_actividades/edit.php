<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\form\edit_activity_form;
use local_gestion_actividades\local\manager;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$id = optional_param('id', 0, PARAM_INT);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/edit.php', ['id' => $id]));
$PAGE->set_title(get_string($id ? 'editactivity' : 'newactivity', 'local_gestion_actividades'));
$PAGE->set_heading(get_string('title', 'local_gestion_actividades'));

$form = new edit_activity_form();
if ($id) {
    $activity = manager::get_activity($id);
    $form->set_data($activity);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/gestion_actividades/index.php'));
} else if ($data = $form->get_data()) {
    $activityid = manager::save_activity($data);
    redirect(new moodle_url('/local/gestion_actividades/view.php', ['id' => $activityid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($id ? 'editactivity' : 'newactivity', 'local_gestion_actividades'));
$form->display();
echo $OUTPUT->footer();
