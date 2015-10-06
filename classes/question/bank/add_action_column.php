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
 * A column type for the add this question to the offlinequiz action.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_offlinequiz\question\bank;
defined('MOODLE_INTERNAL') || die();


/**
 * A column type for the add this question to the offlinequiz action.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_action_column extends \core_question\bank\action_column_base {
    /** @var string caches a lang string used repeatedly. */
    protected $stradd;

    public function init() {
        parent::init();
        $this->stradd = get_string('addtoofflinequiz', 'offlinequiz');
    }

    public function get_name() {
        return 'addtoofflinequizaction';
    }

    protected function print_icon($icon, $title, $url, $disabled = false) {
        global $OUTPUT;
        if (!$disabled) {
            echo '<a title="' . $title . '" href="' . $url . '">';
        } else {
            echo '<span class="greyed">';
        }
        echo '<img src="' . $OUTPUT->pix_url($icon) . '" class="iconsmall" alt="' . $title . '" />';
        if (!$disabled) {
            echo '</a>';
        } else {
            echo '</span>';
        }
    }

    protected function display_content($question, $rowclasses) {
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        $disabled = false;
        if ($this->qbank->offlinequiz_contains($question->id)) {
            $disabled = true; 
        }
        $this->print_icon('t/add', $this->stradd, $this->qbank->add_to_offlinequiz_url($question->id), $disabled);
    }

    public function get_required_fields() {
        return array('q.id');
    }
}
