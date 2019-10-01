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

define("CORNER_AREA_HEIGHT", "200");// The height of the area where we search for the corner cross.
define("CORNER_AREA_WIDTH", "200"); // The width of the area where we search for the corner cross.
define("CORNER_SEARCH_AREA_START_SIZE", 50);

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/positionslib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/crossscanner.php');
class offlinequiz_pagepositionscanner {

    private $expectedcrosspositions;
    private $page;

    public function __construct(offlinequiz_result_page $page) {
        $this->page = $page;
        if (!$this->page->scanproperties) {
            $this->page->scanproperties = new \stdClass();
        }
        $this->page->scanproperties->geometry = $this->page->image->getImageGeometry();

    }

    public function scanposition() {
        $geometry = $this->page->scanproperties->geometry;
        $this->page->scanproperties->zoomfactorx = $geometry['width'] / A4_WIDTH;
        $this->page->scanproperties->zoomfactory = $geometry['height'] / A4_HEIGHT;
        $zoomfactorx = $this->page->scanproperties->zoomfactorx;
        $zoomfactory = $this->page->scanproperties->zoomfactory;
        $this->expectedcrosspositions = [
        "upperleft" => new offlinequiz_point(CORNER_SPACE_LEFT * $zoomfactorx, CORNER_SPACE_TOP * $zoomfactory, false),
        "upperright" => new offlinequiz_point((A4_WIDTH - CORNER_SPACE_RIGHT) * $zoomfactorx, CORNER_SPACE_TOP * $zoomfactory, false),
        "lowerright" => new offlinequiz_point((A4_WIDTH - CORNER_SPACE_RIGHT) * $zoomfactorx, (A4_HEIGHT - CORNER_SPACE_BOTTOM) * $zoomfactory, false),
        "lowerleft" => new offlinequiz_point(CORNER_SPACE_LEFT * $zoomfactorx, (A4_HEIGHT - CORNER_SPACE_BOTTOM) * $zoomfactory, false)
        ];

        $this->page->positionproperties["upperright"] = $this->findcross("upperright");
        $this->page->positionproperties["lowerright"] = $this->findcross("lowerright");
        $this->page->positionproperties["upperleft"] = $this->findcross("upperleft");
        $this->page->positionproperties["lowerleft"] = $this->findcross("lowerleft");

        $upperright = $this->page->positionproperties["upperright"];
        $upperleft = $this->page->positionproperties["upperleft"];
        $lowerleft = $this->page->positionproperties["lowerleft"];
        $lowerright = $this->page->positionproperties["lowerright"];

        $horizontaldiff = new offlinequiz_point($upperright->getx() - $upperleft->getx(), $upperright->gety() - $upperleft->gety(), 1);
        $verticaldiff = new offlinequiz_point($upperright->getx() - $lowerright->getx(), $upperright->gety() - $lowerright->gety(), 1);
        $this->page->scanproperties->zoomfactorx = $horizontaldiff->getdistance() / LAYER_WIDTH;
        $this->page->scanproperties->zoomfactory = $verticaldiff->getdistance() / LAYER_HEIGHT;

        $this->page->positionproperties["pageangle"] = calculatepointangle($horizontaldiff);
        $this->page->status = PAGE_STATUS_OK;
    }

    private function calculatepositions (offlinequiz_point $leftpoint, offlinequiz_point $rightpoint) {
        $diagvector = new offlinequiz_point($rightpoint->getx() - $leftpoint->getx(), $rightpoint->gety() - $leftpoint->gety(), 0);
        $diagzoomfactor = $diagvector->getdistance() / DIAGONAL_LENGTH;
        $this->page->scanproperties->zoomfactorx = $diagzoomfactor * $this->page->scanproperties->zoomfactorx * $this->page->scanproperties->zoomfactory;
        $this->page->scanproperties->zoomfactory = $diagzoomfactor * $this->page->scanproperties->zoomfactory * $this->page->scanproperties->zoomfactorx;
        if ($leftpoint->gety() > $rightpoint->gety()) {
            $lowerleft = $leftpoint;
            $upperright = $rightpoint;
            $lowerright = calculatepoint( $lowerleft , $upperright , getdiagonalangle(), LAYER_WIDTH * $this->page->scanproperties->zoomfactorx);
            $upperleft = calculatepoint( $upperright , $lowerleft, getdiagonalangle() , -LAYER_WIDTH * $this->page->scanproperties->zoomfactorx);

        } else {
            $lowerright = $rightpoint;
            $upperleft = $leftpoint;
            $lowerleft = calculatepoint($upperleft, $lowerright, getdiagonalangle() , LAYER_HEIGHT * $this->page->scanproperties->zoomfactory);
            $upperright = calculatepoint($lowerright, $upperleft , -getdiagonalangle() , LAYER_HEIGHT * $this->page->scanproperties->zoomfactory);
        }

        // TODO Find a good way for finding ontop.

        $horizontaldiff = new offlinequiz_point($upperright->getx() - $upperleft->getx(), $upperright->gety() - $upperleft->gety(), 1);
        $this->page->positionproperties["upperright"] = $upperright;
        $this->page->positionproperties["lowerright"] = $lowerright;
        $this->page->positionproperties["upperleft"] = $upperleft;
        $this->page->positionproperties["lowerleft"] = $lowerleft;
        $this->page->positionproperties["pageangle"] = calculatepointangle($horizontaldiff);
        $this->page->status = PAGE_STATUS_OK;

    }

    private function findcross($cornername) {
        $image = $this->page->image;
        if (!$image) {
            throw new \coding_exception('Image should not be empty');
        }
        $crossscanner = new simple_cross_scanner($this->page);
        $startpoint = null;
        $geometry = $this->page->scanproperties->geometry;
        $zoomfactor = $this->page->scanproperties->zoomfactory;

        if ($cornername == "upperleft") {
            $startpoint = new offlinequiz_point(CORNER_SEARCH_AREA_START_SIZE * $zoomfactor, $zoomfactor * CORNER_SEARCH_AREA_START_SIZE, 0);
        } else if ($cornername == "lowerleft") {
            $startpoint = new offlinequiz_point($zoomfactor * CORNER_SEARCH_AREA_START_SIZE, $geometry['height'] - $zoomfactor * CORNER_SEARCH_AREA_START_SIZE, 0);
        } else if ($cornername == "lowerright") {
            $startpoint = new offlinequiz_point($geometry['width'] - $zoomfactor * CORNER_SEARCH_AREA_START_SIZE,
                $geometry['height'] - $zoomfactor * CORNER_SEARCH_AREA_START_SIZE, 0);
        } else if ($cornername == "upperright") {
            $startpoint = new offlinequiz_point($geometry['width'] - $zoomfactor * CORNER_SEARCH_AREA_START_SIZE, $zoomfactor * CORNER_SEARCH_AREA_START_SIZE, 0);
        }
        $result = $crossscanner->findcross($this->page->image, $startpoint, $startpoint);
        return $result;

    }

}