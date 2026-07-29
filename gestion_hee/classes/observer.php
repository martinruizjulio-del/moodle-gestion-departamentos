<?php
namespace block_gestion_hee;

defined('MOODLE_INTERNAL') || die();

use block_gestion_hee\local\student_hours_cache;

class observer {
    public static function file_changed(\core\event\base $event): void {
        global $DB;

        try {
            $component = isset($event->other['component']) ? (string)$event->other['component'] : '';
            $filearea = isset($event->other['filearea']) ? (string)$event->other['filearea'] : '';
            $itemid = isset($event->other['itemid']) ? (int)$event->other['itemid'] : 0;

            if ($component !== 'local_gestion_actividades' || $itemid <= 0) {
                return;
            }

            if ($filearea === 'typeb_certificate' && $DB->get_manager()->table_exists(new \xmldb_table('local_ga_typeb_certs'))) {
                $userid = (int)$DB->get_field('local_ga_typeb_certs', 'userid', ['id' => $itemid], IGNORE_MISSING);
                if ($userid > 0) {
                    student_hours_cache::invalidate_user($userid);
                }
                return;
            }

            if ($filearea === 'certificate' && $DB->get_manager()->table_exists(new \xmldb_table('local_ga_certificates'))) {
                $userid = (int)$DB->get_field('local_ga_certificates', 'userid', ['id' => $itemid], IGNORE_MISSING);
                if ($userid > 0) {
                    student_hours_cache::invalidate_user($userid);
                }
            }
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('No se ha podido procesar el observer de caché de block_gestion_hee. ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }
}
