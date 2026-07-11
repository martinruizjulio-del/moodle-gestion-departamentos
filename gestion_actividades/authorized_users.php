<?php
require_once(__DIR__ . '/../../config.php');
use local_gestion_actividades\local\manager;
require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);
$action = optional_param('action', '', PARAM_ALPHA); $userid = optional_param('userid', 0, PARAM_INT); $q = optional_param('q', '', PARAM_TEXT); $courseid = optional_param('courseid', 0, PARAM_INT);
$courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname, shortname', 0, 200);
if ($courseid <= 0) { $fw = $DB->get_record_sql("SELECT courseid FROM {local_ga_workshops} ORDER BY id DESC", [], IGNORE_MULTIPLE); $courseid = $fw ? (int)$fw->courseid : 0; }
if ($action === 'add' && confirm_sesskey() && $userid > 0) { manager::add_authorized_user($userid, $USER->id); redirect(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid'=>$courseid]), get_string('authorizeduseradded','local_gestion_actividades')); }
if ($action === 'remove' && confirm_sesskey() && $userid > 0) { manager::remove_authorized_user($userid); redirect(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid'=>$courseid]), get_string('authorizeduserremoved','local_gestion_actividades')); }
$PAGE->set_context($context); $PAGE->set_url(new moodle_url('/local/gestion_actividades/authorized_users.php', ['courseid'=>$courseid])); $PAGE->set_title(get_string('authorizedusers','local_gestion_actividades')); $PAGE->set_heading(get_string('title','local_gestion_actividades'));
echo $OUTPUT->header(); echo $OUTPUT->heading(get_string('authorizedusers','local_gestion_actividades'));
echo html_writer::tag('p', get_string('authorizedusers_teacherfilter_help','local_gestion_actividades'), ['class'=>'alert alert-info']);
echo html_writer::start_tag('form', ['method'=>'get','class'=>'mb-3']);
echo html_writer::label(get_string('course'), 'courseid'); echo html_writer::start_tag('select', ['name'=>'courseid','id'=>'courseid','class'=>'form-control','style'=>'max-width:520px']);
foreach ($courses as $c) { echo html_writer::tag('option', format_string($c->fullname).' ['.s($c->shortname).'] — ID '.$c->id, ['value'=>$c->id, 'selected'=>((int)$c->id===(int)$courseid)?'selected':null]); }
echo html_writer::end_tag('select');
echo html_writer::label(get_string('searchteacherautocomplete','local_gestion_actividades'), 'q', false, ['class'=>'mt-2']); echo html_writer::empty_tag('input', ['type'=>'text','name'=>'q','id'=>'q','value'=>s($q),'class'=>'form-control','style'=>'max-width:520px','list'=>'teacher-suggestions','autocomplete'=>'on']); echo html_writer::empty_tag('input', ['type'=>'submit','value'=>get_string('search'),'class'=>'btn btn-primary mt-2']); echo html_writer::end_tag('form');
$results = ($courseid > 0 && trim($q) !== '') ? manager::search_course_teachers($courseid, $q) : [];
echo html_writer::start_tag('datalist', ['id'=>'teacher-suggestions']); foreach ($results as $u) { echo html_writer::tag('option','',['value'=>fullname($u).' <'.$u->email.'>']); } echo html_writer::end_tag('datalist');
if (trim($q) !== '') { echo $OUTPUT->heading(get_string('searchresults'),3); if ($results) { $table=new html_table(); $table->head=[get_string('user'),get_string('email'),get_string('actions')]; foreach ($results as $u) { $addurl=new moodle_url('/local/gestion_actividades/authorized_users.php',['action'=>'add','userid'=>$u->id,'courseid'=>$courseid,'sesskey'=>sesskey()]); $table->data[]=[fullname($u),s($u->email),html_writer::link($addurl,get_string('addauthorizeduser','local_gestion_actividades'),['class'=>'btn btn-secondary btn-sm'])]; } echo html_writer::table($table); } else { echo $OUTPUT->notification(get_string('noteachersfoundcourse','local_gestion_actividades'),'info'); } }
echo $OUTPUT->heading(get_string('currentauthorizedusers','local_gestion_actividades'),3); $users=manager::list_authorized_users(); if ($users) { $table=new html_table(); $table->head=[get_string('user'),get_string('email'),get_string('actions')]; foreach ($users as $u) { $removeurl=new moodle_url('/local/gestion_actividades/authorized_users.php',['action'=>'remove','userid'=>$u->id,'courseid'=>$courseid,'sesskey'=>sesskey()]); $table->data[]=[fullname($u),s($u->email),html_writer::link($removeurl,get_string('remove'),['class'=>'btn btn-danger btn-sm'])]; } echo html_writer::table($table); } else { echo $OUTPUT->notification(get_string('noauthorizedusers','local_gestion_actividades'),'info'); }
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), get_string('dashboard','local_gestion_actividades'), ['class'=>'btn btn-secondary']); echo $OUTPUT->footer();
