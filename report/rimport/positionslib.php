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

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/point.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');

define("A4_WIDTH", "2100");                     // Paper size.
define("A4_HEIGHT", "2970");
define("CORNER_SPACE_LEFT", "124.5");            // The space, between the left side of paper and the cross of the left side.
define("CORNER_SPACE_RIGHT", "154.5");          // The space, between the right side of paper and the cross of the right side.
define("CORNER_SPACE_TOP", "119.5");            // The space between the top of the side and the crosses on top.
define("CORNER_SPACE_BOTTOM", "120.5");            // The space between the bottom of the side and the crosses on the bottom.

// define("LAYER_WIDTH", A4_WIDTH - CORNER_SPACE_LEFT-CORNER_SPACE_RIGHT);
// define("LAYER_HEIGHT", A4_HEIGHT - CORNER_SPACE_TOP - CORNER_SPACE_BOTTOM); these two lines can be added again, once the old algorithm is excluded
define("DIAGONAL_LENGTH", sqrt(pow(LAYER_WIDTH, 2) + pow(LAYER_HEIGHT, 2)) );

function calculatewithoutadjustment($imagegeometry, offlinequiz_point $point ) {

     $xpercentage = $point->getx() / A4_WIDTH;
     $ypercentage = $point->gety() / A4_HEIGHT;
     return new offlinequiz_point($xpercentage * $imagegeometry['width'], $ypercentage * $imagegeometry['height'], false);
}

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

function add(offlinequiz_point $point, $addx, $addy, $mode) {
    return new offlinequiz_point($point->getx() + $addx, $point->gety() + $addy, $mode);
}
// returns the angle between the horizontal and the diagonal of the layer-rectangle
function getdiagonalangle() {
    return asin(LAYER_HEIGHT / DIAGONAL_LENGTH);

}

function calculatepoint(offlinequiz_point $initialpoint, offlinequiz_point $directionpoint, $anglechange, $length) {

    $realdirection = new offlinequiz_point($directionpoint->getx() - $initialpoint->getx(), $directionpoint->gety() - $initialpoint->gety(), false);

    $directionangle = calculatepointangle($realdirection);
    $addeddirectionangle = $directionangle + $anglechange;
    $resultx = $initialpoint->getx() + cos($addeddirectionangle * $length);
    $resulty = $initialpoint->gety() + sin($addeddirectionangle * $length);
    return new offlinequiz_point( $resultx, $resulty, $initialpoint->isfound() && $directionpoint->isfound());
}

function calculatepointangle(offlinequiz_point $point) {
    if ($point->gety() > 0 || $point->getx() > 0) {
        return atan($point->gety() / $point->getx());
    } else {
        return M_PI + atan($point->gety() / $point->getx());
    }
}

function calculatepointlength(offlinequiz_point $point) {
    return sqrt(pow($point->getx(), 2) + pow($point->gety(), 2));
}
function calculate_point_relative_to_corner(offlinequiz_result_page $page, offlinequiz_point $point) {

    $upperleft = $page->positionproperties["upperleft"];
    return add_with_adjustment($page, $upperleft, $point);
}
