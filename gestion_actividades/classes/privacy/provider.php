<?php
namespace local_gestion_actividades\privacy;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider {
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_database_table('local_ga_candidates', [
            'userid' => 'privacy:metadata:userid',
            'grade' => 'privacy:metadata:grade',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_participants', [
            'userid' => 'privacy:metadata:userid',
            'grade' => 'privacy:metadata:grade',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_completions', [
            'userid' => 'privacy:metadata:userid',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_edition_enrolments', [
            'userid' => 'privacy:metadata:userid',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_hour_history', [
            'userid' => 'privacy:metadata:userid',
            'hours' => 'privacy:metadata:hours',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_certificates', [
            'userid' => 'privacy:metadata:userid',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_typeb_certs', [
            'userid' => 'privacy:metadata:userid',
            'hours' => 'privacy:metadata:hours',
            'status' => 'privacy:metadata:status',
        ], 'privacy:metadata');
        $collection->add_database_table('local_ga_course_settings', [
            'usermodified' => 'privacy:metadata:userid',
        ], 'privacy:metadata');
        return $collection;
    }
}
