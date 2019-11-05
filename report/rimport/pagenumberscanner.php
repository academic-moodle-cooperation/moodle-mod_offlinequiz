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

require_once($CFG->dirroot .  '/mod/offlinequiz/report/rimport/page.php');
require_once($CFG->dirroot  . '/mod/offlinequiz/report/rimport/positionslib.php');
// Horizontal distance between the upper left corner and the beginning of the page number box.
define('PAGE_NUMBER_DISTANCE_X', 1570);
// Vertical distance between the upper left corner and the beginning of the page number box.
define('PAGE_NUMBER_DISTANCE_Y', 2651);
// Height of the page number box.
define('PAGE_NUMBER_HEIGHT', 36);
// Width of the page number box.
define('PAGE_NUMBER_WIDTH', 181);
define('PAGE_NUMBER_CELLS', 26);
define('PAGE_NUMBER_CELL_WIDTH', PAGE_NUMBER_WIDTH / PAGE_NUMBER_CELLS);
define('PAGE_NUMBER_MEASURING_POINT_COUNT', 5);
class offlinequiz_pagenumberscanner {

    // The Page number box is a binary encoded page number.
    public function scan_page_number(offlinequiz_result_page $page) {
        // TODO
        $page->pagenumber = 1;
        return;
        $result = 0;
        $positions = $this->find_positions($page);
        $image = $page->image;
        // For every line in the cell we measure a uneven amount of times from top to bottom.
        for ($i = 0; $i < PAGE_NUMBER_CELLS; $i++) {
            $count = 0;
            for ($j = 0; $j < PAGE_NUMBER_MEASURING_POINT_COUNT; $j++) {
                if (pixelisblack($image, $positions[$i][$j]->getx(), $positions[$i][$j]->gety())) {
                    $count++;
                }

            }
            // And if we find more black pixels than white, we consider it black.
            if ($count > PAGE_NUMBER_MEASURING_POINT_COUNT / 2) {
                $result = $result + pow(2, PAGE_NUMBER_CELLS - ($i + 1));
            }
        }
        $page->pagenumber = $result;
    }

    private function find_positions(offlinequiz_result_page $page) {
        $positions = array();
        for ($i = 0; $i < PAGE_NUMBER_CELLS; $i++) {
            for ($j = 0; $j < PAGE_NUMBER_MEASURING_POINT_COUNT; $j++) {
                // We measure points in the middle of the bar.
                $x = PAGE_NUMBER_DISTANCE_X + ($i + 0.5) * PAGE_NUMBER_CELL_WIDTH;
                // We measure in as many points in between top and bottom as configured in equal distance.
                $y = PAGE_NUMBER_DISTANCE_Y + ($j + 1) * PAGE_NUMBER_HEIGHT / (PAGE_NUMBER_MEASURING_POINT_COUNT + 1);
                $positions[$i][$j] = calculate_point_relative_to_corner($page, new offlinequiz_point($x, $y, 2));
            }
        }
        return $positions;

    }

}