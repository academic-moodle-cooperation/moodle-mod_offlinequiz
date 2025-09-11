<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
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
 * Define the capabilities for offine quizzes
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // The standard capability mod/offlinequiz:addinstance.
    'mod/offlinequiz:addinstance' => [
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW,
        ],
    ],

    // Ability to see that the offline quiz exists, and the basic information
    // about it, for example the start date and time limit.
    'mod/offlinequiz:view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Ability to attempt the offline quiz as a 'student'.
    'mod/offlinequiz:attempt' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'student' => CAP_ALLOW,
        ],
    ],

    // Edit the offline quiz settings, add and remove questions.
    'mod/offlinequiz:manage' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Create an offline quiz.
    'mod/offlinequiz:createofflinequiz' => [

        'captype' => 'write', // Only just a write.
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Manually grade and comment on student attempts at a question.
    'mod/offlinequiz:grade' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // View the offline quiz reports.
    'mod/offlinequiz:viewreports' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Delete attempts using the overview report.
    'mod/offlinequiz:deleteattempts' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    // Change the way how answer sheets are evaluated.
    'mod/offlinequiz:changeevaluationmode' => [

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
        ],
    ],
];
