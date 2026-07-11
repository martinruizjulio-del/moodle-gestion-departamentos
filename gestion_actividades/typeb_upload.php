<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\portfolio_typeb;

require_login();
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/typeb_upload.php'));
$PAGE->set_title('Subir certificado Tipo B');
$PAGE->set_heading('Subir certificado Tipo B');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $activityname = required_param('activityname', PARAM_TEXT);
    $activitydateparts = optional_param_array('activitydate', [], PARAM_INT);
    $hours = required_param('hours', PARAM_FLOAT);
    $authorized = optional_param('authorizedconfirm', 0, PARAM_INT);

    $activitydate = 0;
    if (!empty($activitydateparts['year']) && !empty($activitydateparts['month']) && !empty($activitydateparts['day'])) {
        $activitydate = make_timestamp((int)$activitydateparts['year'], (int)$activitydateparts['month'], (int)$activitydateparts['day']);
    }

    if (trim($activityname) === '') {
        $errors[] = 'Indica el nombre de la actividad.';
    }
    if ($activitydate <= 0) {
        $errors[] = 'Indica la fecha de la actividad.';
    }
    if ($hours <= 0) {
        $errors[] = 'Indica un número de horas válido.';
    }
    if (!$authorized) {
        $errors[] = 'Debes confirmar que la actividad está autorizada según la normativa para Talleres Tipo B.';
    }
    if (empty($_FILES['certificatefile']) || empty($_FILES['certificatefile']['tmp_name']) || !is_uploaded_file($_FILES['certificatefile']['tmp_name'])) {
        $errors[] = 'Sube el certificado en PDF.';
    } else {
        $filename = clean_param($_FILES['certificatefile']['name'], PARAM_FILE);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $errors[] = 'El archivo debe ser PDF.';
        }
    }

    if (!$errors) {
        $id = portfolio_typeb::create_upload((int)$USER->id, $activityname, $activitydate, (float)$hours, $filename, $_FILES['certificatefile']['tmp_name']);
        redirect(new moodle_url('/local/gestion_actividades/portfolio.php'), 'Certificado Tipo B subido correctamente. Queda pendiente de revisión por el gestor.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Subir certificado Tipo B');

echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/portfolio.php'), 'Volver al portafolio', ['class' => 'btn btn-secondary']), 'mb-3');

if ($errors) {
    echo $OUTPUT->notification(implode('<br>', array_map('s', $errors)), 'error');
}

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', 'Datos de la actividad');

echo html_writer::tag('label', 'Nombre de la actividad', ['for' => 'activityname']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'activityname', 'id' => 'activityname', 'class' => 'form-control mb-3', 'required' => 'required']);

echo html_writer::tag('label', 'Fecha de la actividad');
echo html_writer::start_div('mb-3');
echo html_writer::select_time('days', 'activitydate[day]', time());
echo ' ';
echo html_writer::select_time('months', 'activitydate[month]', time());
echo ' ';
echo html_writer::select_time('years', 'activitydate[year]', time());
echo html_writer::end_div();

echo html_writer::tag('label', 'Número de horas', ['for' => 'hours']);
echo html_writer::empty_tag('input', ['type' => 'number', 'step' => '0.5', 'min' => '0.5', 'name' => 'hours', 'id' => 'hours', 'class' => 'form-control mb-3', 'required' => 'required']);

echo html_writer::tag('label', 'Certificado PDF', ['for' => 'certificatefile']);
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'certificatefile', 'id' => 'certificatefile', 'accept' => 'application/pdf,.pdf', 'class' => 'form-control mb-3', 'required' => 'required']);

echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'authorizedconfirm', 'value' => '1', 'id' => 'authorizedconfirm', 'class' => 'form-check-input', 'required' => 'required']);
echo html_writer::tag('label', 'Confirmo que esta actividad está autorizada para Talleres Tipo B según la normativa aplicable.', ['for' => 'authorizedconfirm', 'class' => 'form-check-label']);
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Subir certificado Tipo B', 'class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
