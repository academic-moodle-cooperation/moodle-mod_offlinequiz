<?php
// This file is for Moodle - http://moodle.org/
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
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
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

        $mform->addElement('hidden', 'mode');
        $mform->setDefault('mode', 'editlists');
        $mform->addElement('hidden', 'action');
        $mform->setDefault('action', 'savelist');
        $mform->addElement('hidden', 'listid');
        $mform->setDefault('listid', '0');
        $mform->addElement('hidden', 'list');
        $mform->setDefault('list', '1');
        $mform->addElement('hidden', 'q');
        $mform->setDefault('q', $this->offlinequiz);

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'40', 'maxlength'=>'255'));

        $this->add_action_buttons(false, get_string('submit'));
    }
}
