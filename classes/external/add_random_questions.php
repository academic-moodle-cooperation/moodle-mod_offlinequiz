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

namespace mod_offlinequiz\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

use external_function_parameters;
use external_single_structure;
use external_value;
use external_api;
use core_question\category_manager;
use mod_offlinequiz\question\bank\filter\custom_category_condition;
use mod_offlinequiz\quiz_settings;
use mod_offlinequiz\structure;

/**
 * Add random questions to a offlinequiz.
 *
 * @package    mod_offlinequiz
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_random_questions extends external_api {
    /**
     * Parameters for the web service function
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT, 'The cmid of the quiz'),
                'addonpage' => new external_value(PARAM_INT, 'The page where random questions will be added to'),
                'randomcount' => new external_value(PARAM_INT, 'Number of random questions'),
                'filtercondition' => new external_value(
                    PARAM_TEXT,
                    '(Optional) The filter condition used when adding random questions from an existing category.
                    Not required if adding random questions from a new category.',
                    VALUE_DEFAULT,
                    '',
                ),
                'newcategory' => new external_value(
                    PARAM_TEXT,
                    '(Optional) The name of a new question category to create and use for the random questions.',
                    VALUE_DEFAULT,
                    '',
                ),
                'parentcategory' => new external_value(
                    PARAM_TEXT,
                    '(Optional) The parent of the new question category, if creating one.',
                    VALUE_DEFAULT,
                    0,
                ),
                'groupnumber' => new external_value(
                    PARAM_INT,
                    'Groupnumber',
                    VALUE_DEFAULT,
                    1,
                ),
                'includesubcategories' => new external_value(
                    PARAM_INT,
                    'Recurse',
                    VALUE_DEFAULT,
                    0,
                ),
                'preventsamequestion' => new external_value(
                    PARAM_INT,
                    'Prevent same question',
                    VALUE_DEFAULT,
                    0,
                ),
            ]
        );
    }

    /**
     * Add random questions.
     *
     * @param int $cmid The cmid of the quiz
     * @param int $addonpage The page where random questions will be added to
     * @param int $randomcount Number of random questions
     * @param string $filtercondition Filter condition
     * @param string $newcategory add new category
     * @param string $parentcategory parent category of new category
     * @param int $groupnumber
     * @param int $recurse
     * @param int $preventsamequestion
     * @return array result
     */
    public static function execute(
        int $cmid,
        int $addonpage,
        int $randomcount,
        string $filtercondition = '',
        string $newcategory = '',
        string $parentcategory = '',
        int $groupnumber = 0,
        int $recurse = 0,
        int $preventsamequestion = 0,
    ): array {
        [
            'cmid' => $cmid,
            'addonpage' => $addonpage,
            'randomcount' => $randomcount,
            'filtercondition' => $filtercondition,
            'newcategory' => $newcategory,
            'parentcategory' => $parentcategory,
            'groupnumber' => $groupnumber,
            'includesubcategories' => $recurse,
            'preventsamequestion' => $preventsamequestion,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'addonpage' => $addonpage,
            'randomcount' => $randomcount,
            'filtercondition' => $filtercondition,
            'newcategory' => $newcategory,
            'parentcategory' => $parentcategory,
            'groupnumber' => $groupnumber,
            'includesubcategories' => $recurse,
            'preventsamequestion' => $preventsamequestion,
        ]);

        // Validate context.
        $thiscontext = \context_module::instance($cmid);
        self::validate_context($thiscontext);
        require_capability('mod/offlinequiz:manage', $thiscontext);

        // If filtercondition is not empty, decode it. Otherwise, set it to empty array.
        $filtercondition = !empty($filtercondition) ? json_decode($filtercondition, true) : [];

        // Create new category.
        if (!empty($newcategory)) {
            $categorymanager = new category_manager();
            $categoryid = $categorymanager->add_category($parentcategory, $newcategory, '');
            $filter = [
                'category' => [
                    'jointype' => custom_category_condition::JOINTYPE_DEFAULT,
                    'values' => [$categoryid],
                    'filteroptions' => ['includesubcategories' => false],
                ],
            ];
            // Generate default filter condition for the random question to be added in the new category.
            $filtercondition = [
                'qpage' => 0,
                'cat' => "{$categoryid},{$thiscontext->id}",
                'qperpage' => DEFAULT_QUESTIONS_PER_PAGE,
                'tabname' => 'questions',
                'sortdata' => [],
                'filter' => $filter,
            ];
        }

        $categoryid = $filtercondition['filter']['category']['values'][0];

        // Add random question to the quiz.
        [$quiz, ] = get_module_from_cmid($cmid);
        $group = offlinequiz_get_group($quiz, $groupnumber);

        offlinequiz_add_random_questions($quiz, $addonpage, $categoryid, $randomcount, $group->id, $recurse, $preventsamequestion);
        offlinequiz_delete_template_usages($quiz);
        offlinequiz_update_sumgrades($quiz);

        return ['message' => get_string('addarandomquestion_success', 'mod_quiz')];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL),
        ]);
    }
}
