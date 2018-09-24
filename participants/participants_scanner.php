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
 * Scanner for evaluating scanned participant forms
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/scanner.php');

/**
 * Class that contains all the functions to interpret scanned participant forms.
 * Overwrites some methods of the class offlinequiz_page_scanner.
 *
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 *
 */
class offlinequiz_participants_scanner extends offlinequiz_page_scanner {

    /**
     * (non-PHPdoc)
     * @see offlinequiz_page_scanner::init_hotspots()
     */
    public function init_hotspots() {
        $this->hotspots = array();
        // Load hotspots for the crosses.
        for ($i = 0; $i <= NUMBERS_PER_PAGE; $i++) {
            $point = new oq_point(116, 142 + 79.8 * $i);
            $this->hotspots["p$i"] = $point;
        }

        // Load hotspots for the barcodes.
        for ($i = 1; $i <= NUMBERS_PER_PAGE; $i++) {
            $point = new oq_point(1564, 142 + 79.8 * $i);
            $this->hotspots["b$i"] = $point;
        }
        $point = new oq_point(1564, 142 + 79.8 * (NUMBERS_PER_PAGE + 1));
        $this->hotspots["list"] = $point;

    }

    /**
     * Returns the list number recognised on the scanned page.
     *
     * @return int the bar code value
     */
    public function get_list() {

        $value = $this->hotspot_value($this->hotspots["p0"], true);
        $this->calibrate(0, $value);
        if ($value < 7) {
            return false;
        }
        return $this->get_barcode($this->hotspots["list"]);

    }

    /**
     * Returns the participants in an array.
     *
     * @return array The participants as recognised on the scanned page.
     */
    public function get_participants() {

        $participants = array();
        for ($i = 1; $i <= NUMBERS_PER_PAGE; $i++) {
            if (!$this->hotspots["p$i"]->blank) {
                $participant = new stdClass();
                $value = $this->hotspot_value($this->hotspots["p$i"]);
                if ($value == 1) {
                    $participant->value = 'marked';
                } else if ($value == 3) {
                    $this->insecure = true;
                    $participant->value = 'unknown';
                } else {
                    $participant->value = 'empty';
                }
                if (!$this->hotspots["b$i"]->blank) {
                    $participant->userid = $this->get_barcode($this->hotspots["b$i"]);
                }
                $participants[$i] = $participant;
            }
        }
        return $participants;
    }

    /**
     * Returns the participants hotspots of this scanner.
     *
     * @param int $width
     * @return array The hotspots
     */
    public function export_hotspots_participants($width) {
        global $CFG;

        $export = array();
        $factory = $width / imagesx($this->image);

        for ($i = 1; $i <= NUMBERS_PER_PAGE; $i++) {
            if (!$this->hotspots["p$i"]->blank) {
                $point = new oq_point(($this->hotspots["p$i"]->x + $this->offset->x) * $width / imagesx($this->image),
                          ($this->hotspots["p$i"]->y + $this->offset->y) * $factory);
                $export["p$i"] = $point;
            }
        }
        return $export;
    }

    /**
     * Returns the barcode hotspots for the user IDs.
     *
     * @param int $width
     * @return array The barcode hotspots
     */
    public function export_hotspots_barcodes($width) {
        global $CFG;

        $export = array();
        $factory = $width / imagesx($this->image);

        for ($i = 1; $i <= NUMBERS_PER_PAGE; $i++) {
            if (!$this->hotspots["b$i"]->blank) {
                $point = new oq_point(($this->hotspots["b$i"]->x + $this->offset->x) * $width / imagesx($this->image),
                        ($this->hotspots["b$i"]->y + $this->offset->y) * $factory);
                $export["b$i"] = $point;
            }
        }
        return $export;
    }

    /**
     * Returns the corners of the scanned page.
     *
     * @param int $width
     * @return array of oq_points (upperleft, upperright, lowerleft, lowerright).
     */
    public function export_corners($width) {
        global $CFG;

        $export = array();
        $factor = $width / imagesx($this->image);

        $point = new oq_point(($this->upperleft->x) * $factor - 2 * $this->zoomx,
                ($this->upperleft->y) * $factor - 2 * $this->zoomy);
        $export[0] = $point;
        $point = new oq_point(($this->upperright->x) * $factor - 2 * $this->zoomx,
                ($this->upperright->y) * $factor - 2 * $this->zoomy);
        $export[1] = $point;
        $point = new oq_point(($this->lowerleft->x) * $factor - 2 * $this->zoomx,
                ($this->lowerleft->y) * $factor - 2 * $this->zoomy);
        $export[2] = $point;
        $point = new oq_point(($this->lowerright->x) * $factor - 2 * $this->zoomx,
                ($this->lowerright->y) * $factor - 2 * $this->zoomy);
        $export[3] = $point;

        return $export;
    }
}
