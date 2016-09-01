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

require_once ($CFG->libdir . '/questionlib.php');

define('ANSWERS_BLOCK_SIZE',8);
define('ANSWERS_MAX_QUESTIONS_PER_COLUMN',24);
//serialize arrays, as until php 7 arrays aren't supported in define
define('ANSWERS_COLUMNS_PER_PAGE_LIMITS' , serialize(array(1 => 13, 2 => 8, 3 => 6)));


define('ANSWERS_DISTANCE_X',96);
define('ANSWERS_DISTANCE_Y',981);
define('ANSWERS_BOX_DISTANCE_X_NORMAL',65);
//TODO
define('ANSWERS_BOX_DISTANCE_X_NEW_COLUMN',serialize(array(1 => 0, 2 => 0, 3 => 0, 4 => 0)));
define('ANSWERS_BOX_DISTANCE_Y_NORMAL',65);
define('ANSWERS_BOX_DISTANCE_Y_NEW_BLOCK',164);
define('ANSWERS_BOX_SIZE',35);
class offlinequiz_resultscanner {
    private $boxscanner;

    public function __construct($boxscanner) {
        $this->boxscanner = $boxscanner;
    }

    public function scanresults(offlinequiz_result_page $page) {
        $quba = \question_engine::load_questions_usage_by_activity($page->group->templateusageid);
        $max_answers = $this->get_max_answers($page);
        $columns = $this->get_number_of_columns($max_answers);
        $questionsperpage = $this->get_questions_per_page($columns);
        $startingnumber = ($page->pagenumber-1) * $questionsperpage + 1;
        $endnumber = $startingnumber + $questionsperpage-1;
        $slots = $quba->get_slots();
        for($i=$startingnumber;$i<=$endnumber;$i++) {
            if(array_key_exists($i, $slots)) {
                $position = $this->get_question_cell($startingnumber, $i);

                $slot = $slots[$i];
                $slotquestion = $quba->get_question($slot);
                $attempt = $quba->get_question_attempt($slot);

                $order = $slotquestion->get_order($attempt);
                $answercount = count($order);
                $this->calculate_result($page,$position,$i,$answercount);
            }
            else {
                break;
            }
        }

    }

    private function get_max_answers(offlinequiz_result_page $page) {
        global $DB;
    // Finds the maximal number of answers for a offlinequizgroup
        $sql = "SELECT MAX(co) as maxanswers
                  FROM (SELECT COUNT(1) AS co
    	                  FROM {question_answers} qa
    	                 WHERE EXISTS
    		                   (SELECT 1
                                  FROM {offlinequiz_group_questions} ogq
                                 WHERE ogq.offlinegroupid = :groupid
                                   AND qa.question = ogq.questionid)
    	              GROUP BY qa.question) AS answercount";
        $params['groupid'] = $page->group->id;
        return $DB->get_field_sql($sql, $params);

    }

    private function get_number_of_columns ($maxanswers) {
        $i = 1;
        $columnlimits = unserialize(ANSWERS_COLUMNS_PER_PAGE_LIMITS);
        while(array_key_exists($i,$columnlimits) && $columnlimits[$i] > $maxanswers) {
            $i++;
        }

        return $i;
    }

    private function get_questions_per_page($columns) {
        return $columns * ANSWERS_MAX_QUESTIONS_PER_COLUMN;
    }

    private function get_question_cell($startingnumber, $slotnumber) {
        $numberonsheet = $slotnumber - $startingnumber;
        $position['column'] = floor(($numberonsheet)/ANSWERS_MAX_QUESTIONS_PER_COLUMN);
        $numberincolumn = $numberonsheet - $position['column'] * ANSWERS_MAX_QUESTIONS_PER_COLUMN;
        $position['block'] = floor($numberincolumn/ANSWERS_BLOCK_SIZE);
        $position['blockposition'] = $numberincolumn - $position['block'] * ANSWERS_BLOCK_SIZE;
        return $position;
    }



    private function calculate_result(offlinequiz_result_page $page,$position,$questiononpage,$answercount) {
        //print("block:" . $cell['block'] . "\ncolumn: " . $cell['column'] . "\nblockposition:" . $cell['blockposition'] . "\n");
//         print_object($question);
        for($i=0;$i<$answercount;$i++) {
            $expectedx = ANSWERS_DISTANCE_X + ANSWERS_BOX_DISTANCE_X_NEW_COLUMN *$position['column'] +  ANSWERS_BOX_DISTANCE_X_NORMAL * $i + ANSWERS_BOX_SIZE/2;
            $expectedy = ANSWERS_DISTANCE_Y + ANSWERS_BOX_DISTANCE_Y_NEW_BLOCK * $position['block'] + ANSWERS_BOX_DISTANCE_Y_NORMAL * $position['blockposition'] + ANSWERS_BOX_SIZE/2;
            $page->answers[$questiononpage][$i]['position'] = calculate_point_relative_to_corner($page, new offlinequiz_point($expectedx, $expectedy, 1));
            $page->answers[$questiononpage][$i]['result'] = $this->boxscanner->scan_box($page,$page->answers[$questiononpage][$i]['position'],ANSWERS_BLOCK_SIZE);
        }
        //print_object($page->expectedanswerboxpositions[$question->slot]);
    }


}