<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\user_graded',
        'callback' => '\\local_gestion_actividades\\observer::user_graded',
        'priority' => 9999,
    ],
];
