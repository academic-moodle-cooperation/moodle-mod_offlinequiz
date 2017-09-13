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

namespace mod_offlinequiz\question\bank;
defined('MOODLE_INTERNAL') || die();

/**
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_column extends \core_question\bank\checkbox_column {
    protected $strselect;

    protected function display_content($question, $rowclasses) {
        global $PAGE;
        $disabled = '';
        if ($this->qbank->offlinequiz_contains($question->id)) {
            $disabled = 'disabled="disabled"';
        }
        echo '<input title="' . $this->strselect . '" type="checkbox" name="q' .
                $question->id . '" id="checkq' . $question->id . '" value="1" ' .
                $disabled . ' class="select-multiple-checkbox" />';
    }
}
