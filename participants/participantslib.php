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
 * Internal library of functions for participants_corner
 *
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 *
 * @author Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 *
 */
class mod_offlinequiz_corners {
    private $_upperleft;
    private $_upperright;
    private $_lowerleft;
    private $_lowerright;

    public function __construct($upperleft, $upperright, $lowerleft, $lowerright) {
        $this->_upperleft = $upperleft;
        $this->_upperright = $upperright;
        $this->_lowerleft = $lowerleft;
        $this->_lowerright = $lowerright;
    }
    /**
     *
     * @param string $cornerid id of the form uly (upperleft y value)
     */
    public function get_corner_value($cornerid) {
        $position = substr($cornerid, 0, 2);
        if ($position == 'ul') {
            $corner = $this->_upperleft;
        } else if ($position == 'ur' ) {
            $corner = $this->_upperright;
        } else if ($position == 'll') {
            $corner = $this->_lowerleft;
        } else {
            $corner = $this->_lowerright;
        }
        if (substr($cornerid, 2, 1) === 'y') {
            return $corner->y;
        } else {
            return $corner->x;
        }
    }
    /**
     *
     * @return array of all offlinequiz corners
     */
    public function all() {
        $result = [];
        $result[] = $this->_upperleft;
        $result[] = $this->_upperright;
        $result[] = $this->_lowerleft;
        $result[] = $this->_lowerright;
        return $result;
    }
}
