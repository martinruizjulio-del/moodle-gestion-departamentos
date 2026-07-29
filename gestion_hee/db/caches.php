<?php
defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Freshness is controlled in classes/local/student_hours_cache.php.
    // MUC TTL is intentionally longer so the last valid value can be reused
    // briefly if a recalculation fails or another request is already refreshing it.
    'student_hours' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600,
    ],
    'teacher_workshops' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600,
    ],
    'schema' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600,
    ],
];
