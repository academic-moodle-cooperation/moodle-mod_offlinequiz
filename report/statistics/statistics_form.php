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
 * Offlinequiz statistics settings form definition.
 *
 * @package   offlinequiz_statistics
 * @copyright 2013 The University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * This is the settings form for the offlinequiz statistics report.
 *
 * @copyright 2013 The University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_statistics_settings_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'preferencespage',
                get_string('preferencespage', 'offlinequiz_overview'));

//         $options = array();
//         $options[0] = get_string('attemptsfirst', 'offlinequiz_statistics');
//         $options[1] = get_string('attemptsall', 'offlinequiz_statistics');
//         $mform->addElement('select', 'useallattempts',
//                 get_string('calculatefrom', 'offlinequiz_statistics'), $options);
//         $mform->setDefault('useallattempts', 0);

        $mform->addElement('submit', 'submitbutton',
                get_string('preferencessave', 'offlinequiz_overview'));
    }
}
