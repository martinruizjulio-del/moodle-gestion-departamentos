<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_gestion_hee_upgrade($oldversion): bool {
    if ($oldversion < 2026071127) {
        if (class_exists('\block_gestion_hee\local\student_hours_cache')) {
            \block_gestion_hee\local\student_hours_cache::invalidate_schema();
            \block_gestion_hee\local\student_hours_cache::invalidate_all();
        }

        upgrade_block_savepoint(true, 2026071127, 'gestion_hee');
    }


    if ($oldversion < 2026071128) {
        if (class_exists('\block_gestion_hee\local\student_hours_cache')) {
            \block_gestion_hee\local\student_hours_cache::invalidate_schema();
            \block_gestion_hee\local\student_hours_cache::invalidate_all();
        }

        upgrade_block_savepoint(true, 2026071128, 'gestion_hee');
    }

    return true;
}
