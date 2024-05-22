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
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/engine/datalib.php');
require_once($CFG->libdir . '/questionlib.php');

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
                'canbeedited' => new external_value(PARAM_BOOL, '')
            ]
        );
    }

    /**
     * Set the questions slot parameters to display the question template.
     *
     * @param int $slotid Slot id to display.
     * @param int $newversion the version to set. 0 means 'always latest'.
     * @param bool $canbeedited Wheter the forms were already created
     * @return array
     */
    public static function execute(int $slotid, int $newversion, bool $canbeedited): array {
        global $DB;
        $params = [
            'slotid' => $slotid,
            'newversion' => $newversion,
            'canbeedited' => $canbeedited
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);
        $response = ['result' => false];
        // Get the required data.
        $referencedata = $DB->get_record('question_references',
            ['itemid' => $params['slotid'], 'component' => 'mod_offlinequiz', 'questionarea' => 'slot']);
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

        // The forms are either still not created or the number of answers matches, so a question can be updated ex-post.
        if ($canbeedited || $oldquestioncountanswers == $newquestioncountanswers) {

            $newdata = new stdClass();
            $newdata->id = $slotdata->id;
            $newdata->questionid = $newquestionid;
            $offlinequiz = $DB->get_record('offlinequiz', ['id' => $slotdata->offlinequizid]);

            $reference = new stdClass();
            $reference->id = $referencedata->id;
            if ($params['newversion'] === 0) {
                $reference->version = null;
            } else {
                $reference->version = $params['newversion'];
            }
            if ($response['result']) {
                $response['result'] = $DB->update_record('question_references', $reference);
            }
            \offlinequiz_update_question_instance($offlinequiz,$context->id, $oldquestionid, $slotdata->maxmark, $newquestionid);

            if ($canbeedited) {
                // Regenerates question usages.
                \offlinequiz_delete_template_usages($offlinequiz);
            }

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
                'answersdiffer' => new external_value(PARAM_BOOL)
            ]
        );
    }
}
