<?php
namespace local_gestion_actividades;

defined('MOODLE_INTERNAL') || die();

use local_gestion_actividades\local\grade_manager;

/**
 * Event observers for lightweight HEE grade synchronisation.
 */
class observer {
    /** @var bool Prevent recursive processing of grade events created by our own manual items. */
    private static $processing = false;

    /**
     * Refresh HEE grades when the selected self-assessment or a linked Type A activity is graded.
     */
    public static function user_graded(\core\event\user_graded $event): void {
        if (self::$processing) {
            return;
        }
        $courseid = (int)$event->courseid;
        $userid = (int)$event->relateduserid;
        $itemid = (int)($event->other['itemid'] ?? 0);
        if ($courseid <= 0 || $userid <= 0 || $itemid <= 0) {
            return;
        }
        if (!grade_manager::is_relevant_source_grade_item($courseid, $itemid)) {
            return;
        }

        self::$processing = true;
        try {
            grade_manager::get_user_grade_summary($courseid, $userid, true);
        } catch (\Throwable $e) {
            if (function_exists('debugging')) {
                debugging('No se han podido sincronizar las notas HEE tras actualizar una calificación: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        } finally {
            self::$processing = false;
        }
    }
}
