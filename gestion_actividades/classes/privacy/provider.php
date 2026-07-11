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
        return $collection;
    }
}
