<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_restored',
        'callback'    => '\mod_offlinequiz\event\fix_group_question_entries::on_course_restored',
        'priority'    => 1000,
        'internal'    => false,
    ],
];