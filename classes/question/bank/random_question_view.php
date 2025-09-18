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

use qbank_viewquestiontype\question_type_column;

/**
 * Subclass to customise the view of the question bank for the quiz editing screen.
 *
 * @package    mod_offlinequiz
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     2022 Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class random_question_view extends custom_view {
    #[\Override]
    protected function get_question_bank_plugins(): array {
        return [
            new question_type_column($this),
            new question_name_text_column($this),
            new preview_action_column($this),
        ];
    }

    #[\Override]
    protected function display_bottom_controls(\context $catcontext): void {
    }
}
