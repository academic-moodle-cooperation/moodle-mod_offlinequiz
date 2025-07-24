<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_restored',
        'callback'    => '\mod_offlinequiz\event\fix_group_question_entries::on_course_restored',
        'priority'    => 1000,
        'internal'    => false,
    ],
    [
        'eventname'   => '\core\event\course_module_created',
        'callback'    => '\mod_offlinequiz\event\fix_group_question_entries::on_module_restored',
        'priority'    => 1000,
        'internal'    => false,
    ],
];