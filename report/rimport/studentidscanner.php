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

define('STUDENT_BOX_SIZE', '35');
define('STUDENT_BOX_DISTANCE_X', '65');
define('STUDENT_BOX_DISTANCE_Y', '60');
define('STUDENT_BOX_STUDENTID_CORNER_X', 1266);
define('STUDENT_BOX_STUDENTID_CORNER_Y', 321);
class offlinequiz_studentid_scanner {

    private $boxscanner;

    public function  __construct($boxscanner) {
        $this->boxscanner = $boxscanner;
    }

    public function scan_studentid(offlinequiz_result_page $page) {
        $boxes = $this->calculate_student_id_middles($page);
        $iddigits = get_config('offlinequiz', 'ID_digits');
        for ($i = 0; $i < $iddigits; $i++) {
            $numbers[$i] = $this->scannumber($page, $boxes[$i]);
            if ($numbers[$i] < 0) {
                $page->status = PAGE_STUDENT_ID_ERROR;
            }
        }
        $page->studentidziphers = $numbers;
        if ($page->status == PAGE_STATUS_OK) {
            $page->studentid = $this->extract_number($numbers);
        }

        return $page;
    }

    private function extract_number($numbers) {

        return implode('', $numbers);
    }

    private function scannumber($page, $boxes) {
        $number = -2;
        for ($i = 0; $i <= 9; $i++) {
            $result = $this->boxscanner->scan_box($page, $boxes[$i], BOX_SIZE);

            if ($result) {
                if ($number == -2) {
                    $number = $i;
                } else {
                    $number = -1;
                }
            }
        }
        return $number;
    }

    private function calculate_student_id_middles(offlinequiz_result_page $page) {
        $iddigits = get_config('offlinequiz', 'ID_digits');
        $studentidpoints = array();
        for ($j = 0; $j <= 9; $j++) {
            for ($i = 0; $i < $iddigits; $i++) {
                $boxmiddlepoint = new offlinequiz_point(STUDENT_BOX_STUDENTID_CORNER_X + STUDENT_BOX_SIZE / 2 + STUDENT_BOX_DISTANCE_X * $i,
                    STUDENT_BOX_STUDENTID_CORNER_Y + STUDENT_BOX_SIZE / 2 + STUDENT_BOX_DISTANCE_Y * $j, 2);
                $studentidpoints[$i][$j] = calculate_point_relative_to_corner($page, $boxmiddlepoint);
            }
        }
        $page->expectedstudentidpositions = $studentidpoints;
        return $studentidpoints;
    }
}