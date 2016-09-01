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

define("CORNER_AREA_HEIGHT", "100");// The height of the area where we search for the corner cross.
define("CORNER_AREA_WIDTH", "100"); // The width of the area where we search for the corner cross.

define("ONTOP_OFFSET",15);
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/positionslib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/crossscanner.php');
class offlinequiz_pagepositionscanner {

    private $expectedcrosspositions;
    private $page;

    public function __construct(offlinequiz_result_page $page) {
        $cornerdistancex = round((A4_WIDTH-LAYER_WIDTH)/2);
        $cornerdistancey = round((A4_HEIGHT-LAYER_HEIGHT)/2);
        $this->page = $page;
        if(!$this->page->scanproperties) {
            $this->page->scanproperties = new \stdClass();
        }
        $this->page->scanproperties->geometry = $this->page->image->getImageGeometry();
        $this->expectedcrosspositions = array(
                    "upperleft" => new offlinequiz_point(CORNER_SPACE_LEFT,CORNER_SPACE_TOP,false),
                    "upperright" => new offlinequiz_point(A4_WIDTH-CORNER_SPACE_RIGHT, CORNER_SPACE_TOP,false),
                    "lowerright" => new offlinequiz_point(A4_WIDTH-CORNER_SPACE_RIGHT, A4_HEIGHT-CORNER_SPACE_BOTTOM,false),
                    "lowerleft" => new offlinequiz_point(CORNER_SPACE_LEFT, A4_HEIGHT-CORNER_SPACE_BOTTOM,false)
        );


    }

    public function scanposition() {
        $geometry = $this->page->scanproperties->geometry;
        $this->page->scanproperties->zoomfactorx = $geometry['width']/A4_WIDTH;
        $this->page->scanproperties->zoomfactory = $geometry['height']/A4_HEIGHT;
        $lowerleft  = $this->findcross(calculatewithoutadjustment($geometry,$this->expectedcrosspositions["lowerleft"]));
        $upperright  = $this->findcross(calculatewithoutadjustment($geometry,$this->expectedcrosspositions["upperright"]));
        if($upperright->isfound() && $lowerleft->isfound()) {
            $this->calculatepositions($lowerleft, $upperright);
        }
        else {
            $lowerright = $this->findcross(calculatewithoutadjustment($geometry,$this->expectedcrosspositions["lowerright"]));
            $upperleft = $this->findcross(calculatewithoutadjustment($geometry,$this->expectedcrosspositions["upperleft"]));

            if($upperleft->isfound() && $lowerright->isfound()) {
               $this->calculatepositions($upperleft,$lowerright);
            }
            else {
                $this->page->positionproperties["upperright"] = calculatewithoutadjustment($geometry,$this->expectedcrosspositions["upperright"]);
                $this->page->positionproperties["lowerright"] = calculatewithoutadjustment($geometry,$this->expectedcrosspositions["lowerright"]);
                $this->page->positionproperties["upperleft"] = calculatewithoutadjustment($geometry,$this->expectedcrosspositions["upperleft"]);
                $this->page->positionproperties["lowerleft"] = calculatewithoutadjustment($geometry,$this->expectedcrosspositions["lowerleft"]);
                $this->page->status = PAGE_STATUS_ALIGNMENT_ERROR;
            }
        }
    }

    private function calculatepositions (offlinequiz_point $leftpoint, offlinequiz_point $rightpoint) {
        if ($leftpoint->gety() > $rightpoint->gety()) {
            $lowerleft = $leftpoint;
            $upperright = $rightpoint;
            $lowerright = calculatepoint( $lowerleft , $upperright , getdiagonalangle(), LAYER_WIDTH*$this->page->scanproperties->zoomfactorx);
            $upperleft =  calculatepoint( $upperright , $lowerleft, getdiagonalangle() , -LAYER_WIDTH*$this->page->scanproperties->zoomfactorx);

        } else {
            $lowerright = $rightpoint;
            $upperleft = $leftpoint;
            $lowerleft = calculatepoint($upperleft, $lowerright, getdiagonalangle() , LAYER_HEIGHT*$this->page->scanproperties->zoomfactory);
            $upperright = calculatepoint($lowerright, $upperleft , -getdiagonalangle() , LAYER_HEIGHT*$this->page->scanproperties->zoomfactory);
        }

        //TODO Find a good way for finding ontop

        $horizontaldiff = new offlinequiz_point($upperright->getx()-$upperleft->getx(),$upperright->gety() - $upperleft->gety(), 1);
        $this->page->positionproperties["upperright"] = $upperright;
        $this->page->positionproperties["lowerright"] = $lowerright;
        $this->page->positionproperties["upperleft"] = $upperleft;
        $this->page->positionproperties["lowerleft"] = $lowerleft;
        $this->page->positionproperties["pageangle"] = calculatepointangle($horizontaldiff);
        $this->page->status= PAGE_STATUS_OK;

    }

    private function findcross(offlinequiz_point $guessedcrosspoint) {
        $image = $this->page->image;
        if(!$image) {
            return $guessedcrosspoint;
        }
        $upperleftbound = add($guessedcrosspoint, round(-CORNER_AREA_WIDTH/2*$this->page->scanproperties->zoomfactorx), round(-CORNER_AREA_HEIGHT/2*$this->page->scanproperties->zoomfactory),false);
        $lowerrightbound = add($guessedcrosspoint, round(CORNER_AREA_WIDTH/2*$this->page->scanproperties->zoomfactorx), round(CORNER_AREA_HEIGHT/2*$this->page->scanproperties->zoomfactory),false);

        $crossfinder = new crossfinder();
        return $crossfinder->findcross($image, $upperleftbound, $lowerrightbound);

    }

}