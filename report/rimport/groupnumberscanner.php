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
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');
define('BOX_SIZE', '35');
define('BOX_DISTANCE_X', '95.7');
define('BOX_A_CORNER_X', 288);
define('BOX_A_CORNER_Y', 455);
define('GROUP_BOXES', 6);
class offlinequiz_groupnumberscanner {

    private $boxscanner;

    public function __construct($boxscanner) {
        $this->boxscanner = $boxscanner;
    }
    // Read the group number of the page.
    public function scan_group_number(offlinequiz_result_page $page) {
        global $DB;
        // Find the guessed middles of all group question boxes.
        $points = $this->calculate_group_number_middles($page);
        $amountofcrosses = 0;
        $number = -1;
        // Go through every box and find out if its crossed out.
        for ($i = 0, $size = count($points); $i < $size; ++$i) {
            $boxresult = $this->boxscanner->scan_box($page, $points[$i], BOX_SIZE);
            // If the result of the box is "crossed" or "uncertain" raise the number of crosses.
            if ($boxresult) {
                $amountofcrosses++;
                $number = $i;
            }
        }
        if ($amountofcrosses > 1 || $amountofcrosses == 0 ) {
            $page->status = PAGE_STATUS_GROUP_ERROR;
        } else {
            $number++;
            $group = $DB->get_record('offlinequiz_groups',
                array('offlinequizid' => $page->offlinequizid, 'groupnumber' => $number ));
            if ($group) {
                $page->group = $group;
            } else {
                $page->status = PAGE_STATUS_GROUP_ERROR;
            }
        }
    }


    private function calculate_group_number_middles(offlinequiz_result_page $page) {
        $grouppoints = array();

        for ($i = 0; $i < GROUP_BOXES; $i++) {
            $boxmiddlepoint = new offlinequiz_point(BOX_A_CORNER_X + BOX_SIZE / 2 + BOX_DISTANCE_X * $i,
                BOX_A_CORNER_Y + BOX_SIZE / 2, 2);
            $grouppoints[$i] = calculate_point_relative_to_corner($page, $boxmiddlepoint);
        }
        $page->expectedgroupnumberpositions = $grouppoints;
        return $grouppoints;
    }
}