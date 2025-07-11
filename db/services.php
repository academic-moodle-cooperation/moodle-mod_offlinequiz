<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Offlinequiz external functions and service definitions.
 *
 * @package    mod_offlinequiz
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_offlinequiz_set_question_version' => [
        'classname'     => 'mod_offlinequiz\external\submit_question_version',
        'description'   => 'Set the version of question that would be required for a given offlinequiz.',
        'type'          => 'write',
        'capabilities'  => 'mod/offlinequiz:view',
        'ajax'          => true,
    ],
    'mod_offlinequiz_add_random_questions' => [
        'classname'     => 'mod_offlinequiz\external\add_random_questions',
        'description'   => 'Add a number of random questions to a offlinequiz.',
        'type'          => 'write',
        'capabilities'  => 'mod/offlinequiz:manage',
        'ajax'          => true,
    ],
];
