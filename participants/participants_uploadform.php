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
 * Form for uploading scanned documents in the participants_report
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

require_once($CFG->libdir.'/formslib.php');

class offlinequiz_participants_upload_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'importfileupload', get_string('importpforms', 'offlinequiz_rimport'));

        $mform->addElement('filepicker', 'newfile', get_string('ziporimagefile', 'offlinequiz_rimport'), null,
                array('subdirs' => 0, 'accepted_types' => array('web_image', '.zip', '.ZIP', '.tif', '.TIF', '.tiff', '.TIFF')));

        $mform->addRule('newfile', null, 'required', null, 'client');

        // Submit button.
        $mform->addElement('submit', 'submitbutton', get_string('import', 'offlinequiz_rimport'));

    }
}
