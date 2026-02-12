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

namespace offlinequiz_correct\output;

use offlinequiz_correct\model\correct_page_data;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
/**
 * Class correct_renderer
 *
 * @package    offlinequiz_correct
 * @copyright  2026 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class correct_renderer extends \plugin_renderer_base {
    /**
     * render the correction page
     * @return void
     */
    public function render_correct_page($offlinequiz, $cm, correct_page_data $data) {
        global $CFG;
        // Now we echo the tabs.
        offlinequiz_print_tabs($offlinequiz, 'tabparticipantscorrect', $cm);
        if ($data->previousurl) {
            $this->output->single_button($data->nexturl, get_string('previouserror', 'offlinequiz_correct'));
        }
        if ($data->nexturl) {
            $this->output->single_button($data->nexturl, get_string('nexterror', 'offlinequiz_correct'));
        }
    }
}
