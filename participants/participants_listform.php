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
 * Defines the list form for participant lists
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class offlinequiz_participantslistform extends moodleform {

    private $offlinequiz;
    private $label;

    public function __construct($offlinequiz, $buttonlabel) {
        $this->offlinequiz = $offlinequiz;
        $this->label = $buttonlabel;
        parent::__construct('participants.php');
    }

    public function definition() {
        $mform =& $this->_form;

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'participantshdr', $this->label);

        $mform->addElement('hidden', 'mode', 'editlists');
        $mform->setType('mode', PARAM_ALPHA);
        $mform->addElement('hidden', 'action', 'savelist');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'listid', 0);
        $mform->setType('listid', PARAM_INT);
        $mform->addElement('hidden', 'list', 1);
        $mform->setType('list', PARAM_INT);
        $mform->addElement('hidden', 'q', $this->offlinequiz);
        $mform->setType('q', PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), ['size' => '40', 'maxlength' => '255']);
        $mform->setType('name', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('submit'));
    }
}
