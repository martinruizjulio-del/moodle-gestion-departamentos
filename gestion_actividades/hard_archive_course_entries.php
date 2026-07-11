<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;

require_login();
require_capability('local/gestion_actividades:manage', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
require_sesskey();

$removed = 0;
$courses = 0;

if ($courseid > 0) {
    $courses = 1;
    $removed += manager::hide_finished_workshop_cards_in_course($courseid);
    $removed += manager::hard_archive_required_activities_in_course($courseid);
} else {
    global $DB;
    $courseids = $DB->get_fieldset_select('local_ga_workshops', 'DISTINCT courseid', 'courseid > 0');
    foreach ($courseids as $cid) {
        $courses++;
        $removed += manager::hide_finished_workshop_cards_in_course((int)$cid);
        $removed += manager::hard_archive_required_activities_in_course((int)$cid);
    }
}

redirect(
    new moodle_url('/local/gestion_actividades/workshops.php'),
    get_string('hardarchivecompleted', 'local_gestion_actividades', (object)['courses' => $courses, 'removed' => $removed]),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
