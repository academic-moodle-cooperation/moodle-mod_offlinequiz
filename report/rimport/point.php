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
class offlinequiz_point {

    public $x;
    public $y;
    // Mode of the point:
    // Mode 0: guessed point in millimeter*10 (x = 10 and x=0 are 1 mm away from each other)
    // Mode 1: found pixel in an image;
    // Mode 2: guessed point relative to corner in millimeter/10.
    public $mode;

    public function __construct($x, $y , $mode) {
        $this->x = $x;
        $this->y = $y;
        $this->mode = $mode;
    }



    public function getx() {
        return $this->x;
    }

    public function gety() {
        return $this->y;
    }
    public function isfound() {
        return ($this->mode == 1 );
    }
    public function getdistance() {
        return sqrt(pow($this->x, 2) + pow($this->y, 2));
    }

}

function pixelisblack($image, $x, $y) {

    if ($x < 0 || $y < 0) {
        return false;
    }

    $color = $image->getImagePixelColor($x, $y)->getColor();

    if ($color['r'] == 0 && $color['g'] == 0 && $color['b'] == 0) {
        return true;
    } else {
        return false;
    }
}