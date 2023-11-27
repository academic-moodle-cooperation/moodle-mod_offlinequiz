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
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/engine/datalib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/offlinequiz.class.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/lib.php');

use external_api;
use external_description;
use external_function_parameters;
use external_single_structure;
use external_value;
use stdClass;

/**
 * External api for changing the question version in the quiz.
 *
 * @package    mod_offlinequiz
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_question_version extends external_api {

    /**
     * Parameters for the submit_question_version.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters (
            [
                'slotid' => new external_value(PARAM_INT, ''),
                'newversion' => new external_value(PARAM_INT, ''),
            ]
        );
    }

    /**
     * Set the questions slot parameters to display the question template.
     *
     * @param int $slotid Slot id to display.
     * @param int $newversion the version to set. 0 means 'always latest'.
     * @return array
     */
    public static function execute(int $slotid, int $newversion): array {
        global $DB;
        $params = [
            'slotid' => $slotid,
            'newversion' => $newversion
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);
        $response = ['result' => false];

        $slotdata = $DB->get_record('offlinequiz_group_questions', ['id' => $slotid]);
        $questionbankentryid = $DB->get_field('question_versions',
                                                'questionbankentryid',
                                                ['questionid' => $slotdata->questionid]
                                            );

        // Capability check.
        list($course, $cm) = get_course_and_cm_from_instance($slotdata->offlinequizid, 'offlinequiz');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/offlinequiz:manage', $context);

        // Check, if a new version can be chosen if grading.
        $oldquestionid = $slotdata->questionid;
        if ($newversion === 0) {
            $sql = "SELECT MAX(questionid) FROM {question_versions} WHERE ? ";
            $newquestionid = $DB->get_field_sql($sql, ['questionbankentryid' => $questionbankentryid]);
        } else {
            $newquestionid = $DB->get_field('question_versions',
                                            'questionid',
                                            ['questionbankentryid' => $questionbankentryid, 'version' => $newversion]
                                            );
        }

        $oldquestioncountanswers = $DB->count_records('question_answers', ['question' => $oldquestionid]);
        $newquestioncountanswers = $DB->count_records('question_answers', ['question' => $newquestionid]);

        if ($oldquestioncountanswers == $newquestioncountanswers) {
            $response['answersdiffer'] = false;
        } else {
            $response['answersdiffer'] = true;
        }

        // Get the course object and related bits.
        $offlinequizrow = $DB->get_record('offlinequiz', ['id' => $slotdata->offlinequizid]);
        $offlinequizobj = new \offlinequiz($offlinequizrow, $cm, $course);
        $structure = $offlinequizobj->get_structure();
        $canbeedited = $structure->can_be_edited();
        if ($canbeedited) {
            $response['canbeedited'] = true;
        } else {
            $response['canbeedited'] = false;
        }

        if ($newquestionid == $oldquestionid) {
            $response['samequestion'] = true;
            return $response;
        } else {
            $response['samequestion'] = false;
        }

        // The forms are either still not created or the number of answers matches, so a question can be updated ex-post.
        if ($canbeedited || $oldquestioncountanswers == $newquestioncountanswers) {
            $offlinequiz = $DB->get_record('offlinequiz', ['id' => $slotdata->offlinequizid]);
            offlinequiz_update_question_instance($offlinequiz, $oldquestionid, $slotdata->maxmark, $newquestionid);
            offlinequiz_update_all_attempt_sumgrades($offlinequiz);
            offlinequiz_update_grades($offlinequiz);
            offlinequiz_delete_statistics_caches($offlinequiz->id);

            $response['result'] = true;
        }

        return $response;
    }

    /**
     * Define the webservice response.
     *
     * @return external_description
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'result' => new external_value(PARAM_BOOL, ''),
                'answersdiffer' => new external_value(PARAM_BOOL),
                'canbeedited' => new external_value(PARAM_BOOL),
                'samequestion'  => new external_value(PARAM_BOOL)
            ]
        );
    }
}
