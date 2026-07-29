<?php
defined('MOODLE_INTERNAL') || die();

function block_gestion_hee_invalidate_user_cache(int $userid): void {
    \block_gestion_hee\local\student_hours_cache::invalidate_user($userid);
}

function block_gestion_hee_invalidate_users_cache(array $userids): void {
    \block_gestion_hee\local\student_hours_cache::invalidate_users($userids);
}

function block_gestion_hee_invalidate_all_user_caches(): void {
    \block_gestion_hee\local\student_hours_cache::invalidate_all();
}


function block_gestion_hee_invalidate_schema_cache(): void {
    \block_gestion_hee\local\student_hours_cache::invalidate_schema();
}
