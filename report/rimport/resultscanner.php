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
namespace offlinequiz_result_import;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/questionlib.php');

define('ANSWERS_BLOCK_SIZE', 8);
define('ANSWERS_MAX_QUESTIONS_PER_COLUMN', 24);
define('ANSWERS_COLUMNS_PER_PAGE_LIMITS' , [1 => 13, 2 => 8, 3 => 6]);

define('ANSWERS_DISTANCE_X', 100);
define('ANSWERS_DISTANCE_Y', 988);
define('ANSWERS_BOX_DISTANCE_X_NORMAL', 65);
// Distance between the begin of two columns, 1 => 0 for programming necessary.
define('ANSWERS_BOX_DISTANCE_X_NEW_COLUMN', [1 => 0, 2 => 906, 3 => 582, 4 => 454.7]);
define('ANSWERS_BOX_DISTANCE_Y_NORMAL', 65);
define('ANSWERS_BOX_DISTANCE_Y_NEW_BLOCK', 564.6);
define('ANSWERS_BOX_SIZE', 35);
class offlinequiz_resultscanner {
    private $boxscanner;

    public function __construct($boxscanner) {
        $this->boxscanner = $boxscanner;
    }

    // Scans the results of a page.
    public function scanresults(offlinequiz_result_page $page) {
        // Firstly load the questions.
        $quba = \question_engine::load_questions_usage_by_activity($page->group->templateusageid);
        // Find out, how whats the maximum of options for a single question in this offlinequiz group.
        $maxanswers = $this->get_max_answers($page);

        // Depending on the amount of maxanswers there is a limit of columns per page.
        $columns = $this->get_number_of_columns($maxanswers);
        $questionsperpage = $this->get_questions_per_page($columns);
        $columndistance = ANSWERS_BOX_DISTANCE_X_NEW_COLUMN[$columns];

        // Load the first and the last number of the questions on the page.
        $startingnumber = ($page->pagenumber - 1) * $questionsperpage;
        $page->startanswer = $startingnumber;
        $endnumber = $startingnumber + $questionsperpage;
        $slots = $quba->get_slots();
        $answercounts = $this->get_answer_counts($page->group->id);
        // For every question on the sheet.
        for ($i = $startingnumber; $i <= $endnumber; $i++) {
            // If there are still questions.
            if (array_key_exists($i, $slots)) {
                // Find out where the question is on the page.
                $position = $this->get_question_cell($startingnumber, $i);

                $slot = $slots[$i];
                $slotquestion = $quba->get_question($slot);
                   print("slotquestion" . $slotquestion->id . "\n");
                $answercount = $answercounts[$slotquestion->id]->count;
                print("answercount" . $answercount . "\n");
                   // Calculate the result of this answer and store it on the page-object.
                $this->calculate_result($page, $columndistance, $position, $i, $answercount);
            } else {
                break;
            }
        }

    }

    private function get_max_answers(offlinequiz_result_page $page) {
        global $DB;
        // Finds the maximal number of answers for a offlinequizgroup.
        $sql = "SELECT MAX(co) as maxanswers
                  FROM (SELECT COUNT(1) AS co
    	                  FROM {question_answers} qa
    	                 WHERE EXISTS
    		                   (SELECT 1
                                  FROM {offlinequiz_group_questions} ogq
                                 WHERE ogq.offlinegroupid = :groupid
                                   AND qa.question = ogq.questionid)
    	              GROUP BY qa.question) AS answercount";
        return $DB->get_field_sql($sql, ['groupid' => $page->group->id]);

    }

    private function get_answer_counts($groupid) {
        global $DB;
        $sql = "SELECT qa.question as questionid, count(*) as count
				FROM {question_answers} qa
				WHERE EXISTS
					(SELECT 1
					 FROM {offlinequiz_group_questions} ogq
					 WHERE ogq.offlinegroupid = :groupid
					 AND qa.question = ogq.questionid)
				GROUP BY qa.question";
        return $DB->get_records_sql($sql, ['groupid' => $groupid]);

    }

    private function get_number_of_columns ($maxanswers) {
        $i = 1;
        $columnlimits = ANSWERS_COLUMNS_PER_PAGE_LIMITS;
        while (array_key_exists($i, $columnlimits) && $columnlimits[$i] > $maxanswers) {
            $i++;
        }
        return $i;
    }

    private function get_questions_per_page($columns) {
        return $columns * ANSWERS_MAX_QUESTIONS_PER_COLUMN;
    }

    private function get_question_cell($startingnumber, $slotnumber) {
        $numberonsheet = $slotnumber - $startingnumber;
        $position['column'] = floor(($numberonsheet) / ANSWERS_MAX_QUESTIONS_PER_COLUMN);
        $numberincolumn = $numberonsheet - $position['column'] * ANSWERS_MAX_QUESTIONS_PER_COLUMN;
        $position['block'] = floor($numberincolumn / ANSWERS_BLOCK_SIZE);
        $position['blockposition'] = $numberincolumn - $position['block'] * ANSWERS_BLOCK_SIZE;
        return $position;
    }



    private function calculate_result(offlinequiz_result_page $page, $columndistance, $position, $questiononpage, $answercount) {
        for ($i = 0; $i < $answercount; $i++) {
            $expectedx = ANSWERS_DISTANCE_X + ($columndistance * $position['column'])
                + (ANSWERS_BOX_DISTANCE_X_NORMAL * $i) + (ANSWERS_BOX_SIZE / 2);
            $expectedy = ANSWERS_DISTANCE_Y + (ANSWERS_BOX_DISTANCE_Y_NEW_BLOCK * $position['block'])
                + (ANSWERS_BOX_DISTANCE_Y_NORMAL * $position['blockposition']) + (ANSWERS_BOX_SIZE / 2);
            $page->answers[$questiononpage][$i]['position'] =
                calculate_point_relative_to_corner($page, new offlinequiz_point($expectedx, $expectedy, 1));
            $result  = $this->boxscanner->scan_box($page, $page->answers[$questiononpage][$i]['position'], ANSWERS_BOX_SIZE);
            $page->answers[$questiononpage][$i]['result'] = $result;
            if ($result == -1) {
                $page->status = PAGE_STATUS_INSECURE_RESULT;
            }
        }
    }


}