<?php
// This file is part of Moodle - http://moodle.org/
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

namespace mod_offlinequiz\document\create\pdf;

/**
 * creates the questions pdf
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_pdf extends \pdf {
    /**
     * Containing the current page buffer after checkpoint() was called.
     * @var string
     */
    private $checkpoint;

    /**
     * make a checkpoint to jump back later
     * @return void
     */
    public function checkpoint() {
        $this->checkpoint = $this->getPageBuffer($this->page);
    }

    /**
     * get back to checkpoint
     * @return void
     */
    public function backtrack() {
        $this->setPageBuffer($this->page, $this->checkpoint);
    }

    /**
     * if the stuff printed now fits on the page.
     * @return bool
     */
    public function is_overflowing() {
        return $this->y > $this->PageBreakTrigger;
    }

    /**
     * set title of the document
     * @param mixed $newtitle
     * @return void
     */
    public function set_title($newtitle) {
        $this->title = $newtitle;
    }
}
