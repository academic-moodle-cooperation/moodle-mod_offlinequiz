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
 * The results import report for offlinequizzes
 *
 * @package       offlinequiz_rimport
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind
 * @copyright     2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.4
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace offlinequiz_result_import;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/point.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');

define("A4_WIDTH", "2100");                     // Paper size.
define("A4_HEIGHT", "2970");
define("CORNER_SPACE_LEFT", "124.5");            // The space, between the left side of paper and the cross of the left side.
define("CORNER_SPACE_RIGHT", "154.5");          // The space, between the right side of paper and the cross of the right side.
define("CORNER_SPACE_TOP", "119.5");            // The space between the top of the side and the crosses on top.
define("CORNER_SPACE_BOTTOM", "120.5");            // The space between the bottom of the side and the crosses on the bottom.

/* These two lines can be added again, once the old algorithm is excluded.
 * define("LAYER_WIDTH", A4_WIDTH - CORNER_SPACE_LEFT-CORNER_SPACE_RIGHT);
 * define("LAYER_HEIGHT", A4_HEIGHT - CORNER_SPACE_TOP - CORNER_SPACE_BOTTOM);
 *
 */
define("DIAGONAL_LENGTH", sqrt(pow(LAYER_WIDTH, 2) + pow(LAYER_HEIGHT, 2)));
/**
 * calculate without adjustment
 * @param mixed $imagegeometry
 * @param \offlinequiz_result_import\offlinequiz_point $point
 * @return offlinequiz_point
 */
function calculatewithoutadjustment($imagegeometry, offlinequiz_point $point) {

     $xpercentage = $point->getx() / A4_WIDTH;
     $ypercentage = $point->gety() / A4_HEIGHT;
     return new offlinequiz_point($xpercentage * $imagegeometry['width'], $ypercentage * $imagegeometry['height'], false);
}
/**
 * add with adjustment
 * @param \offlinequiz_result_import\offlinequiz_result_page $page
 * @param \offlinequiz_result_import\offlinequiz_point $initialpoint
 * @param \offlinequiz_result_import\offlinequiz_point $toadd
 * @return offlinequiz_point
 */
function add_with_adjustment(offlinequiz_result_page $page, offlinequiz_point $initialpoint, offlinequiz_point $toadd) {
    $pointangle = calculatepointangle($toadd);
    $pageangle = $page->positionproperties["pageangle"];
    $zoomfactorx = $page->scanproperties->zoomfactorx;
    $zoomfactory = $page->scanproperties->zoomfactory;
    $pointangle = calculatepointangle($toadd);
    $pointlength = calculatepointlength($toadd);
    $x = $initialpoint->getx() + cos(-$pageangle + $pointangle) * $pointlength * $zoomfactorx;
    $y = $initialpoint->gety() + sin($pageangle + $pointangle) * $pointlength * $zoomfactory;
    return new offlinequiz_point($x, $y, 1);
}
/**
 * add without adjustment
 * @param \offlinequiz_result_import\offlinequiz_point $point
 * @param mixed $addx
 * @param mixed $addy
 * @param mixed $mode
 * @return offlinequiz_point
 */
function add(offlinequiz_point $point, $addx, $addy, $mode) {
    return new offlinequiz_point($point->getx() + $addx, $point->gety() + $addy, $mode);
}
/**
 * Returns the angle between the horizontal and the diagonal of the layer-rectangle.
 * @return float
 */
function getdiagonalangle() {
    return asin(LAYER_HEIGHT / DIAGONAL_LENGTH);
}
/**
 * calculate the point of an with a vector
 * @param \offlinequiz_result_import\offlinequiz_point $initialpoint
 * @param \offlinequiz_result_import\offlinequiz_point $directionpoint
 * @param mixed $anglechange
 * @param mixed $length
 * @return offlinequiz_point
 */
function calculatepoint(offlinequiz_point $initialpoint, offlinequiz_point $directionpoint, $anglechange, $length) {

    $realdirection = new offlinequiz_point(
        $directionpoint->getx() - $initialpoint->getx(),
        $directionpoint->gety() - $initialpoint->gety(),
        false
    );

    $directionangle = calculatepointangle($realdirection);
    $addeddirectionangle = $directionangle + $anglechange;
    $resultx = $initialpoint->getx() + cos($addeddirectionangle * $length);
    $resulty = $initialpoint->gety() + sin($addeddirectionangle * $length);
    return new offlinequiz_point($resultx, $resulty, $initialpoint->isfound() && $directionpoint->isfound());
}
/**
 * calculate the angle of a point
 * @param \offlinequiz_result_import\offlinequiz_point $point
 * @return float
 */
function calculatepointangle(offlinequiz_point $point) {
    if ($point->gety() > 0 || $point->getx() > 0) {
        return atan($point->gety() / $point->getx());
    } else {
        return M_PI + atan($point->gety() / $point->getx());
    }
}
/**
 * calculate the length of a point
 * @param \offlinequiz_result_import\offlinequiz_point $point
 * @return float
 */
function calculatepointlength(offlinequiz_point $point) {
    return sqrt(pow($point->getx(), 2) + pow($point->gety(), 2));
}
/**
 * calculate a point relative to a corner
 * @param \offlinequiz_result_import\offlinequiz_result_page $page
 * @param \offlinequiz_result_import\offlinequiz_point $point
 * @return offlinequiz_point
 */
function calculate_point_relative_to_corner(offlinequiz_result_page $page, offlinequiz_point $point) {

    $upperleft = $page->positionproperties["upperleft"];
    return add_with_adjustment($page, $upperleft, $point);
}
