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

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/point.php');
define('PIXELBOUNDRY', 15);

class crossfinder {

    public function findcross(\Imagick $image,offlinequiz_point $upperleft,offlinequiz_point $lowerright) {

        $sizex = $lowerright->getx()-$upperleft->getx();
        $sizey = $lowerright->gety()-$upperleft->gety();
        $middle = new offlinequiz_point($upperleft->getx() + $sizex/2, $upperleft->gety() + $sizey/2, false);
        $ylower=0;
        $yupper=0;
        $xleft=0;
        $xright=0;
        $count=0;


        for($j=$middle->gety();$j<$lowerright->gety();$j++) {
            for($i = $upperleft->getx(); $i < $lowerright->getx() ; $i++) {
                if(pixelisblack($image,$i,$j)) {
                    $count++;
                    if($count==PIXELBOUNDRY) {
                        $ylower = $j;
                        break;
                    }
                }
            }
            $count=0;
            if($ylower) {
                break;
            }
        }


        for($j=$middle->gety();$j>$upperleft->gety();$j--) {
            for($i = $upperleft->getx(); $i < $lowerright->getx() ; $i++) {
                if(pixelisblack($image,$i,$j)) {
                    $count++;
                    if($count==PIXELBOUNDRY) {
                        $yupper = $j;
                        break;
                    }
                }
            }
            $count=0;
            if($yupper) {
                break;
            }
        }

        if($yupper || $ylower) {
            $y = $this->findclosesest($middle->gety(),$ylower,$yupper);
        }



        for($j= $middle->getx();$j<$lowerright->getx();$j++) {
            for($i = $upperleft->gety(); $i < $lowerright->gety() ; $i++) {
                if(pixelisblack($image,$j,$i)) {
                    $count++;
                    if($count==PIXELBOUNDRY) {
                        $xright = $j;
                        break;
                    }
                }
            }
            $count = 0;
            if($xright) {
                break;
            }
        }

        for($j= $middle->getx();$j>$upperleft->getx();$j--) {
            for($i = $upperleft->gety(); $i < $lowerright->gety() ; $i++) {
                if(pixelisblack($image,$j,$i)) {
                    $count++;
                    if($count==PIXELBOUNDRY) {
                        $xleft = $j;
                        break;
                    }
                }
            }
            $count = 0;
            if($xleft) {
                break;
            }
        }
        $x=$this->findclosesest($middle->getx(),$xright,$xleft);

        if($x && $y) {
            //point found
            return new offlinequiz_point($x, $y, true);
        }
        else {
            //guess the point
            return $middle;
        }
    }

    private function findclosesest ($middle, $upper, $lower) {
        if($upper && $lower) {
            if( abs($upper-$middle) > abs($lower-$middle) ) {
                return $lower;
            }
            else {
                return $upper;
            }
        }
        else if($lower) {
            return $lower;
        }
        elseif ($upper) {
            return $upper;
        }
        return 0;
    }
}