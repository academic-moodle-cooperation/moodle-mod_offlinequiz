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
 * Administration settings definitions for the offlinequiz module.
 *
 * @package       offlinequiz
 * @subpackage    identified
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2025 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 5.0+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if ($ADMIN->fulltree) {
        global $DB;
        
        $settings->add(new admin_setting_heading('offlinequizidentifiedintro', '', get_string('offlinequizidentifiedintro', 'offlinequiz_identified')));
        $settings->add(new admin_setting_configcheckbox('offlinequiz_identified/enableidentified',
            get_string('enableidentified', 'offlinequiz_identified'), get_string('enableidentified_help', 'offlinequiz_identified'),
            0));
    }
}