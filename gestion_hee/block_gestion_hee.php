<?php
defined('MOODLE_INTERNAL') || die();

class block_gestion_hee extends block_base {

    public function init(): void {
        $this->title = get_string('title', 'block_gestion_hee');
    }

    public function applicable_formats(): array {
        return [
            'course-view' => true,
            'my' => true,
            'site-index' => false,
            'mod' => false,
        ];
    }

    public function instance_allow_multiple(): bool {
        return false;
    }

    public function has_config(): bool {
        return false;
    }

    public function get_content() {
        global $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($USER->id) || isguestuser()) {
            return $this->content;
        }

        $userid = (int)$USER->id;

        // Se calcula en cada carga de página. No depende de cron ni de acciones manuales.
        $typeahours = $this->get_typea_hours($userid);
        $typebhours = $this->get_typeb_hours($userid);
        $total = $typeahours + $typebhours;
        $remaining = max(0, 54 - $total);

        $this->content->text .= html_writer::tag('style', '.block-gestion-hee-student-summary .local-ga-badge-remaining{background:#d96c06;color:#fff;}');
        $this->content->text .= html_writer::start_div('block-gestion-hee-student-summary');

        if ($total <= 0) {
            $this->content->text .= html_writer::tag('p', get_string('nohoursyet', 'block_gestion_hee'), ['class' => 'text-muted']);
        }

        $this->content->text .= $this->render_metric(get_string('typeahours', 'block_gestion_hee'), $typeahours, 'badge-success');
        $this->content->text .= $this->render_metric(get_string('typebhours', 'block_gestion_hee'), $typebhours, 'badge-success');
        $this->content->text .= html_writer::tag('hr', '');
        $this->content->text .= $this->render_metric(get_string('totalhours', 'block_gestion_hee'), $total, 'badge-success');
        $remainingclass = $remaining <= 0 ? 'badge-success' : 'local-ga-badge-remaining';
        $this->content->text .= $this->render_metric(get_string('remaininghours', 'block_gestion_hee'), $remaining, $remainingclass);

        $this->content->text .= html_writer::start_div('mt-2');
        $this->content->text .= html_writer::link(
            new moodle_url('/local/gestion_actividades/typeb_upload.php'),
            get_string('uploadtypeb', 'block_gestion_hee'),
            ['class' => 'btn btn-sm btn-warning btn-block mb-1']
        );
        $this->content->text .= html_writer::link(
            new moodle_url('/local/gestion_actividades/mycertificates.php'),
            get_string('mycertificates', 'block_gestion_hee'),
            ['class' => 'btn btn-sm btn-outline-secondary btn-block mb-1']
        );
        $this->content->text .= html_writer::link(
            new moodle_url('/local/gestion_actividades/portfolio.php'),
            get_string('myportfolio', 'block_gestion_hee'),
            ['class' => 'btn btn-sm btn-outline-secondary btn-block']
        );
        $this->content->text .= html_writer::end_div();

        $this->content->text .= html_writer::end_div();

        return $this->content;
    }

    private function get_typea_hours(int $userid): float {
        global $DB;

        $total = 0.0;

        // Fuente principal: certificados Tipo A generados.
        if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_certificates'))
            && $DB->get_manager()->table_exists(new xmldb_table('local_ga_workshops'))) {
            $sql = "SELECT COALESCE(SUM(w.hours), 0)
                      FROM {local_ga_certificates} c
                 LEFT JOIN {local_ga_workshops} w ON w.id = c.workshopid
                     WHERE c.userid = :userid";
            $total += (float)$DB->get_field_sql($sql, ['userid' => $userid]);
        }

        // Complemento: historial de horas si existe, evitando depender exclusivamente del certificado.
        if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_hour_history'))) {
            $sql = "SELECT COALESCE(SUM(hours), 0)
                      FROM {local_ga_hour_history}
                     WHERE userid = :userid";
            $history = (float)$DB->get_field_sql($sql, ['userid' => $userid]);
            if ($history > $total) {
                $total = $history;
            }
        }

        return round($total, 2);
    }

    private function get_typeb_hours(int $userid): float {
        global $DB;

        $total = 0.0;

        if ($DB->get_manager()->table_exists(new xmldb_table('local_ga_typeb_certs'))) {
            $sql = "SELECT COALESCE(SUM(hours), 0)
                      FROM {local_ga_typeb_certs}
                     WHERE userid = :userid
                       AND status = :status";
            $total = (float)$DB->get_field_sql($sql, ['userid' => $userid, 'status' => 'validated']);
        }

        return round($total, 2);
    }

    private function render_metric(string $label, float $value, string $badgeclass = 'badge-secondary'): string {
        $valueformatted = format_float($value, 2, true) . ' h';

        $content = html_writer::span(s($label), 'local-ga-label');
        $content .= html_writer::span($valueformatted, 'badge ' . $badgeclass . ' float-right');

        return html_writer::div($content, 'mb-2');
    }
}
