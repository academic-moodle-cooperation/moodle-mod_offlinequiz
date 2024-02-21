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

namespace mod_offlinequiz\question\bank;

use core\output\checkbox_toggleall;

/**
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @package   mod_offlinequiz
 * @author    2024 Adrian Czermak <adrian.czermak@univie.ac.at>
 * @copyright 2024 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_column extends \core_question\local\bank\checkbox_column {
    protected function display_content($question, $rowclasses): void {
        if ($this->qbank->offlinequiz_contains($question->id)) {
            echo '<input type="checkbox" disabled="disabled" class="select-multiple-checkbox" checked="true" />';
        } else {
            parent::display_content($question, $rowclasses);
        }
    }
}