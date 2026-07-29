<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\portfolio_typeb;
use local_gestion_actividades\local\grade_manager;

require_login();
$courseid = optional_param('courseid', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/typeb_upload.php', $courseid > 0 ? ['courseid' => $courseid] : []));
$PAGE->set_title('Subir Talleres B (antiguos)');
$PAGE->set_heading('Gestión HEE');

if (data_submitted()) {
    require_sesskey();
    $activityname = required_param('activityname', PARAM_TEXT);
    $activitydescription = required_param('activitydescription', PARAM_TEXT);
    $hours = required_param('hours', PARAM_FLOAT);
    $activitydateinput = required_param('activitydate', PARAM_RAW_TRIMMED);
    $activitydate = strtotime($activitydateinput . ' 12:00:00');
    if ($activitydate === false) {
        throw new moodle_exception('invaliddate');
    }
    if ($hours <= 0 || $hours > 500) {
        throw new moodle_exception('invaliddata', 'error', '', 'Las horas deben ser superiores a 0.');
    }
    if (empty($_FILES['evidencefile']) || !is_uploaded_file($_FILES['evidencefile']['tmp_name']) || $_FILES['evidencefile']['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('uploadproblem', 'moodle');
    }
    $maxbytes = 20 * 1024 * 1024;
    if ((int)$_FILES['evidencefile']['size'] > $maxbytes) {
        throw new moodle_exception('maxbytes', 'error', '', display_size($maxbytes));
    }
    $filename = clean_filename((string)$_FILES['evidencefile']['name']);
    if ($filename === '') {
        throw new moodle_exception('invalidfilename', 'error');
    }
    portfolio_typeb::create_upload(
        (int)$USER->id,
        $activityname,
        (int)$activitydate,
        (float)$hours,
        $activitydescription,
        $filename,
        (string)$_FILES['evidencefile']['tmp_name']
    );
    grade_manager::sync_user_safely((int)$USER->id);
    $params = $courseid > 0 ? ['courseid' => $courseid] : [];
    redirect(new moodle_url('/local/gestion_actividades/portfolio.php', $params), 'Taller B antiguo enviado. Queda pendiente de confirmación por los gestores.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/portfolio.php', $courseid > 0 ? ['courseid' => $courseid] : []), $OUTPUT->pix_icon('t/left', '', 'moodle', ['class' => 'iconsmall mr-1']) . ' Volver al portafolio', ['class' => 'btn btn-outline-secondary mb-3']));
echo $OUTPUT->heading('Subir Talleres B (antiguos)');
echo html_writer::tag('p', 'Registra un Taller Tipo B realizado anteriormente y adjunta el archivo que sirve como evidencia. Las horas se incorporarán cuando un gestor lo confirme.', ['class' => 'alert alert-info']);
echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'card']);
echo html_writer::start_div('card-body');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
if ($courseid > 0) echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
$fields = [
    ['Nombre del taller', 'activityname', 'text'],
    ['Fecha', 'activitydate', 'date'],
    ['Horas', 'hours', 'number'],
];
foreach ($fields as [$label,$name,$type]) {
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', $label, ['for' => $name]);
    $attrs=['type'=>$type,'name'=>$name,'id'=>$name,'class'=>'form-control','required'=>'required'];
    if ($type==='number') { $attrs['min']='0.5'; $attrs['max']='500'; $attrs['step']='0.5'; }
    echo html_writer::empty_tag('input',$attrs);
    echo html_writer::end_div();
}
echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Texto descriptivo del taller', ['for' => 'activitydescription']);
echo html_writer::tag('textarea', '', ['name'=>'activitydescription','id'=>'activitydescription','class'=>'form-control','rows'=>6,'required'=>'required']);
echo html_writer::end_div();
echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Archivo de evidencia', ['for'=>'evidencefile']);
echo html_writer::empty_tag('input', ['type'=>'file','name'=>'evidencefile','id'=>'evidencefile','class'=>'form-control','required'=>'required']);
echo html_writer::tag('small', 'Tamaño máximo: 20 MB.', ['class'=>'form-text text-muted']);
echo html_writer::end_div();
echo html_writer::tag('button', 'Enviar para confirmar', ['type'=>'submit','class'=>'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
