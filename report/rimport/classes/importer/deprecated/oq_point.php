<?php
// This file is part of offlinequiz_rimport - http://moodle.org/
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

namespace offlinequiz_rimport\importer\deprecated;

/**
 * A class for points in cartesian system that can rotate and zoom.
 *
 * @package    offlinequiz_rimport
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oq_point {
    /**
     * x value of this oq_point
     * @var int
     */
    public $x;
    /**
     * y value of this oq_point
     * @var int
     */
    public $y;
    /**
     * if this point is blank or not
     * @var bool
     */
    public $blank;
    /**
     * scannedpageid this oq_point belongs to
     * @var \stdClass
     */
    public $scannedpageid;
    /**
     * position of this point
     * @var \stdClass
     */
    public $position;

    /**
     * Constructor
     *
     * @param int $x
     * @param int $y
     * @param bool $blank
     */
    public function __construct($x = 0, $y = 0, $blank = true) {
        $this->x = round($x);
        $this->y = round($y);
        $this->blank = $blank;
    }


    /**
     * Rotates the point with center 0/0
     *
     * @param bool $alpha
     */
    public function rotate($alpha) {
        if ($alpha == 0) {
            return;
        }
        $sin = sin(deg2rad($alpha));
        $cos = cos(deg2rad($alpha));
        $x = $this->x * $cos - $this->y * $sin;
        $this->y = round($this->y * $cos + $this->x * $sin);
        $this->x = round($x);
    }

    /**
     * Zooms the point's distance from center 0/0
     *
     * @param int $x
     * @param int $y
     */
    public function zoom($x, $y) {
        $this->x = round($this->x * $x);
        $this->y = round($this->y * $y);
    }
}
