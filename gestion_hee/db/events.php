<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\file_created',
        'callback' => '\\block_gestion_hee\\observer::file_changed',
    ],
    [
        'eventname' => '\\core\\event\\file_deleted',
        'callback' => '\\block_gestion_hee\\observer::file_changed',
    ],
];
