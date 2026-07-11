<?php
namespace local_gestion_actividades\local;

defined('MOODLE_INTERNAL') || die();

class portfolio_pdf {
    public static function default_cover_template(): string {
        return '<h1 style="text-align:center;color:#2b4b1e;">Portafolio de certificados</h1>' .
               '<h2 style="text-align:center;">{alumno}</h2>' .
               '<p style="text-align:center;font-size:13pt;">Curso: <strong>{curso}</strong></p>' .
               '<p style="text-align:center;">Horas Tipo A: <strong>{horas_tipo_a}</strong> · Horas Tipo B: <strong>{horas_tipo_b}</strong> · Total: <strong>{horas_total}</strong></p>' .
               '<p style="text-align:center;color:#666;">Fecha de emisión: {fecha_emision}</p>';
    }

    public static function get_cover_template(): string {
        $value = get_config('local_gestion_actividades', 'portfoliocoverhtml');
        if ($value === false || trim((string)$value) === '') {
            return self::default_cover_template();
        }
        return (string)$value;
    }

    public static function save_cover_template(string $html): void {
        set_config('portfoliocoverhtml', $html, 'local_gestion_actividades');
    }

    public static function get_typea_hours(int $userid): float {
        if (class_exists('local_gestion_actividades\\local\\manager') && method_exists(manager::class, 'get_student_total_hours')) {
            return (float)manager::get_student_total_hours($userid);
        }
        return 0.0;
    }

    public static function get_typea_certificates(int $userid): array {
        if (class_exists('local_gestion_actividades\\local\\manager') && method_exists(manager::class, 'list_user_certificates')) {
            return manager::list_user_certificates($userid);
        }
        return [];
    }

    public static function replace_cover_placeholders(string $html, \stdClass $user, string $course, float $typeahours, float $typebhours): string {
        $replacements = [
            '{alumno}' => fullname($user),
            '{curso}' => $course,
            '{horas_tipo_a}' => self::format_hours($typeahours),
            '{horas_tipo_b}' => self::format_hours($typebhours),
            '{horas_total}' => self::format_hours($typeahours + $typebhours),
            '{fecha_emision}' => userdate(time(), get_string('strftimedatefullshort', 'langconfig')),
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    public static function format_hours(float $hours): string {
        return rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') . ' h';
    }

    public static function detect_course_label(array $typeacerts, array $typebcerts): string {
        foreach ($typeacerts as $c) {
            if (!empty($c->coursename)) {
                return format_string($c->coursename);
            }
        }
        return get_string('site');
    }

    public static function render_pdf_string(int $userid): string {
        global $DB, $CFG;
        require_once($CFG->libdir . '/pdflib.php');

        portfolio_typeb::ensure_table();

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        $typeacerts = self::get_typea_certificates($userid);
        $typebcerts = portfolio_typeb::list_for_user($userid);
        $typeahours = self::get_typea_hours($userid);
        $typebhours = portfolio_typeb::total_validated_hours($userid);
        $course = self::detect_course_label($typeacerts, $typebcerts);

        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Gestion_actividades');
        $pdf->SetAuthor('Gestion_actividades');
        $pdf->SetTitle('Portafolio de certificados - ' . fullname($user));
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();
        $cover = self::replace_cover_placeholders(self::get_cover_template(), $user, $course, $typeahours, $typebhours);
        $pdf->writeHTML('<div style="margin-top:55mm;">' . $cover . '</div>', true, false, true, false, '');

        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->writeHTML('<h1>Resumen de horas</h1>', true, false, true, false, '');
        $summary = '<table border="1" cellpadding="6">' .
            '<tr style="background-color:#f2f2f2;font-weight:bold;"><td>Tipo</td><td>Horas reconocidas</td></tr>' .
            '<tr><td>Talleres Tipo A</td><td>' . self::format_hours($typeahours) . '</td></tr>' .
            '<tr><td>Talleres Tipo B validados</td><td>' . self::format_hours($typebhours) . '</td></tr>' .
            '<tr><td><strong>Total reconocido</strong></td><td><strong>' . self::format_hours($typeahours + $typebhours) . '</strong></td></tr>' .
            '</table>';
        $pdf->SetFont('helvetica', '', 11);
        $pdf->writeHTML($summary, true, false, true, false, '');

        $pdf->writeHTML('<h2>Talleres Tipo A</h2>', true, false, true, false, '');
        if ($typeacerts) {
            $html = '<table border="1" cellpadding="5"><tr style="background-color:#f2f2f2;font-weight:bold;"><td>Taller</td><td>Curso</td><td>Horas</td><td>Fecha emisión</td></tr>';
            foreach ($typeacerts as $c) {
                $html .= '<tr><td>' . s(($c->workshopcode ?? '') . ' - ' . ($c->workshopname ?? '')) . '</td><td>' . s($c->coursename ?? '') . '</td><td>' . (!empty($c->hours) ? self::format_hours((float)$c->hours) : '-') . '</td><td>' . userdate((int)$c->timeissued, get_string('strftimedatefullshort', 'langconfig')) . '</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->writeHTML('<p>No constan certificados Tipo A generados.</p>', true, false, true, false, '');
        }

        $pdf->writeHTML('<h2>Talleres Tipo B</h2>', true, false, true, false, '');
        if ($typebcerts) {
            $html = '<table border="1" cellpadding="5"><tr style="background-color:#f2f2f2;font-weight:bold;"><td>Actividad</td><td>Fecha</td><td>Horas</td><td>Estado</td></tr>';
            foreach ($typebcerts as $c) {
                $status = (string)$c->status;
                if ($status === 'validated') { $statuslabel = 'Validado'; }
                else if ($status === 'pending') { $statuslabel = 'Pendiente'; }
                else if ($status === 'rejected') { $statuslabel = 'Rechazado'; }
                else { $statuslabel = $status; }
                $html .= '<tr><td>' . s($c->activityname) . '</td><td>' . userdate((int)$c->activitydate, get_string('strftimedatefullshort', 'langconfig')) . '</td><td>' . self::format_hours((float)$c->hours) . '</td><td>' . s($statuslabel) . '</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->writeHTML('<p>No constan certificados Tipo B subidos.</p>', true, false, true, false, '');
        }

        $pdf->writeHTML('<p style="color:#666;font-size:9pt;">Documento generado automáticamente por Gestion_actividades.</p>', true, false, true, false, '');

        return $pdf->Output('', 'S');
    }

    public static function filename_for_user(\stdClass $user): string {
        return clean_filename('portafolio_certificados_' . fullname($user) . '.pdf');
    }
}
