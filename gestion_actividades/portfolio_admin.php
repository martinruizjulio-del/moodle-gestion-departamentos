<?php
require_once(__DIR__ . '/../../config.php');

use local_gestion_actividades\local\manager;
use local_gestion_actividades\local\portfolio_typeb;

require_login();
$context = context_system::instance();
require_capability('local/gestion_actividades:manage', $context);

$q = optional_param('q', '', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$status = optional_param('status', '', PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gestion_actividades/portfolio_admin.php', ['q' => $q, 'userid' => $userid, 'status' => $status]));
$PAGE->set_title('Portafolio de certificados - gestor');
$PAGE->set_heading('Portafolio de certificados - gestor');

function local_ga_admin_badge(string $status): string {
    if ($status === 'generated' || $status === 'validated') {
        return html_writer::span($status === 'generated' ? 'Generado' : 'Validado', 'badge badge-success', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'pending') {
        return html_writer::span('Pendiente', 'badge badge-warning', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    if ($status === 'rejected') {
        return html_writer::span('Rechazado', 'badge badge-danger', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
    }
    return html_writer::span(s($status), 'badge badge-secondary', ['style' => 'font-size:0.85rem;padding:6px 9px;']);
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Portafolio de certificados - gestor');

echo html_writer::start_div('mb-3');
echo html_writer::link(new moodle_url('/local/gestion_actividades/dashboard.php'), 'Panel de gestión', ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio.php'), 'Mi portafolio', ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_cover_template.php'), 'Editar portada PDF', ['class' => 'btn btn-secondary']);
echo ' ';
echo html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_pdf_all.php', ['sesskey' => sesskey()]), 'Descargar todos los portafolios', ['class' => 'btn btn-primary']);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
echo html_writer::start_div('input-group');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'q', 'value' => s($q), 'placeholder' => 'Buscar alumno por nombre o email', 'class' => 'form-control']);
echo html_writer::select(['' => 'Todos los estados Tipo B', 'pending' => 'Pendientes', 'validated' => 'Validados', 'rejected' => 'Rechazados'], 'status', $status, false, ['class' => 'custom-select']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Buscar', 'class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$selecteduser = null;
if ($userid <= 0 && trim($q) !== '') {
    $like = '%' . $DB->sql_like_escape(trim($q)) . '%';
    $users = $DB->get_records_sql("SELECT id, firstname, lastname, email FROM {user} WHERE deleted = 0 AND (" . $DB->sql_like('firstname', ':q1', false) . " OR " . $DB->sql_like('lastname', ':q2', false) . " OR " . $DB->sql_like('email', ':q3', false) . ") ORDER BY lastname, firstname", ['q1' => $like, 'q2' => $like, 'q3' => $like], 0, 30);
    if (count($users) === 1) {
        $u = reset($users);
        $userid = (int)$u->id;
    } else if ($users) {
        echo html_writer::tag('h3', 'Resultados de búsqueda');
        $table = new html_table();
        $table->head = ['Alumno', 'Email', 'Acción'];
        foreach ($users as $u) {
            $table->data[] = [fullname($u), s($u->email), html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_admin.php', ['userid' => $u->id]), 'Ver portafolio', ['class' => 'btn btn-secondary btn-sm'])];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification('No se han encontrado alumnos.', 'info');
    }
}

if ($userid > 0) {
    $selecteduser = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', IGNORE_MISSING);
}

if ($selecteduser) {
    echo html_writer::tag('h2', 'Portafolio de ' . fullname($selecteduser));
    echo html_writer::div(html_writer::link(new moodle_url('/local/gestion_actividades/portfolio_pdf_download.php', ['userid' => $selecteduser->id]), 'Descargar portafolio PDF de este alumno', ['class' => 'btn btn-primary']), 'mb-3');
    $typeahours = method_exists(manager::class, 'get_student_total_hours') ? manager::get_student_total_hours((int)$selecteduser->id) : 0.0;
    $typebvalidated = portfolio_typeb::total_validated_hours((int)$selecteduser->id);
    echo html_writer::tag('p', 'Horas Tipo A: ' . round((float)$typeahours, 2) . ' h · Horas Tipo B validadas: ' . round((float)$typebvalidated, 2) . ' h · Total reconocido: ' . round((float)$typeahours + (float)$typebvalidated, 2) . ' h', ['class' => 'alert alert-info']);

    echo html_writer::tag('h3', 'Talleres Tipo A');
    $typeacerts = method_exists(manager::class, 'list_user_certificates') ? manager::list_user_certificates((int)$selecteduser->id) : [];
    if ($typeacerts) {
        $table = new html_table();
        $table->head = ['Taller', 'Horas', 'Fecha emisión', 'Estado', 'Acciones'];
        foreach ($typeacerts as $c) {
            $actions = html_writer::link(new moodle_url('/local/gestion_actividades/certificate_download.php', ['id' => $c->id]), 'Descargar', ['class' => 'btn btn-secondary btn-sm']);
            if (file_exists(__DIR__ . '/regenerate_certificate.php')) {
                $actions .= ' ' . html_writer::link(new moodle_url('/local/gestion_actividades/regenerate_certificate.php', ['id' => $c->id, 'sesskey' => sesskey()]), 'Regenerar', ['class' => 'btn btn-warning btn-sm']);
            }
            $table->data[] = [s($c->workshopcode . ' - ' . $c->workshopname), !empty($c->hours) ? round((float)$c->hours, 2) . ' h' : '-', userdate((int)$c->timeissued), local_ga_admin_badge($c->status ?: 'generated'), $actions];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification('Este alumno no tiene certificados Tipo A generados.', 'info');
    }

    echo html_writer::tag('h3', 'Talleres Tipo B');
    $typebcerts = portfolio_typeb::list_all((int)$selecteduser->id, $status);
} else {
    echo html_writer::tag('h2', 'Certificados Tipo B pendientes y revisados');
    $typebcerts = portfolio_typeb::list_all(0, $status);
}

if (!empty($typebcerts)) {
    $table = new html_table();
    $table->head = ['Alumno', 'Actividad', 'Fecha', 'Horas', 'Declaración', 'Estado', 'Comentario', 'Acciones'];
    foreach ($typebcerts as $c) {
        $actions = html_writer::link(new moodle_url('/local/gestion_actividades/typeb_download.php', ['id' => $c->id]), 'Descargar', ['class' => 'btn btn-secondary btn-sm']);
        $commentinput = html_writer::empty_tag('input', ['type' => 'text', 'name' => 'comment', 'placeholder' => 'Comentario opcional', 'class' => 'form-control form-control-sm mb-1']);
        $actions .= html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/local/gestion_actividades/typeb_review.php'), 'style' => 'display:inline-block;margin-left:6px;min-width:220px;']);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $actions .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $c->id]);
        $actions .= $commentinput;
        $actions .= html_writer::tag('button', 'Validar', ['type' => 'submit', 'name' => 'action', 'value' => 'validate', 'class' => 'btn btn-success btn-sm']);
        $actions .= ' ' . html_writer::tag('button', 'Rechazar', ['type' => 'submit', 'name' => 'action', 'value' => 'reject', 'class' => 'btn btn-danger btn-sm']);
        $actions .= html_writer::end_tag('form');
        $table->data[] = [
            isset($c->firstname) ? fullname($c) . '<br><small>' . s($c->email) . '</small>' : '-',
            s($c->activityname),
            !empty($c->activitydate) ? userdate((int)$c->activitydate, get_string('strftimedatefullshort', 'langconfig')) : '-',
            round((float)$c->hours, 2) . ' h',
            !empty($c->authorizedconfirm) ? 'Confirmada' : 'No confirmada',
            local_ga_admin_badge((string)$c->status),
            !empty($c->reviewcomment) ? s($c->reviewcomment) : '-',
            $actions,
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No hay certificados Tipo B con esos criterios.', 'info');
}

echo $OUTPUT->footer();
