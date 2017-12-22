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
 * Defines the offlinequiz module settings form.
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Settings form for the offlinequiz module.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_offlinequiz_mod_form extends moodleform_mod {

    protected function definition() {
        global $COURSE, $CFG, $DB, $PAGE;

        $offlinequizconfig = get_config('offlinequiz');

        $offlinequiz = null;
        if (!empty($this->_instance)) {
            $offlinequiz = $DB->get_record('offlinequiz', array('id' => $this->_instance));
        }

        $mform = $this->_form;

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('html', '<center>' . get_string('pluginname', 'offlinequiz') . '</center>');

        if ($offlinequiz && $offlinequiz->docscreated) {
            $mform->addElement('html', "<center><a href=\"" . $CFG->wwwroot .
                    "/mod/offlinequiz/createquiz.php?mode=createpdfs&amp;q=$offlinequiz->id\">" .
                    get_string('formsexist', 'offlinequiz')."</a></center>");
        }

        // Name.
        $mform->addElement('text', 'name', get_string('name', 'offlinequiz'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        // Introduction.
        $this->standard_intro_elements();

        $mform->addElement('date_time_selector', 'time', get_string("quizdate", "offlinequiz"), array('optional' => true));

        if (!$offlinequiz || !$offlinequiz->docscreated) {
            for ($i = 1; $i <= 6; $i++) {
                $groupmenu[$i] = "$i";
            }
            $mform->addElement('select', 'numgroups', get_string('numbergroups', 'offlinequiz'), $groupmenu);
            $mform->setDefault('numgroups', 1);
        } else {
            $mform->addElement('static', 'numgroups', get_string('numbergroups', 'offlinequiz'));
        }

        // Only allow certain options if the PDF documents have not been created.
        if (!$offlinequiz || !$offlinequiz->docscreated) {
            $attribs = '';
        } else {
            $attribs = ' disabled="disabled"';
        }

        $mform->addElement('selectyesno', 'shufflequestions', get_string("shufflequestions", "offlinequiz"), $attribs);
        $mform->setDefault('shufflequestions', $offlinequizconfig->shufflequestions);

        $mform->addElement('selectyesno', 'shuffleanswers', get_string("shufflewithin", "offlinequiz"), $attribs);
        $mform->addHelpButton('shuffleanswers', 'shufflewithin', 'offlinequiz');
        $mform->setDefault('shuffleanswers', $offlinequizconfig->shuffleanswers);

        // Option for show tutorial.
        $mform->addElement('selectyesno', 'showtutorial', get_string("showtutorial", "offlinequiz"));
        $mform->addHelpButton('showtutorial', "showtutorial", "offlinequiz");
        $mform->addElement('static', 'showtutorialdescription', '', get_string("showtutorialdescription", "offlinequiz") .
                '<br/><a href="'.$CFG->wwwroot.'/mod/offlinequiz/tutorial/index.php">' . $CFG->wwwroot .
                '/mod/offlinequiz/tutorial/index.php</a>');

        // Timeopen and timeclose.
        $mform->addElement('date_time_selector', 'timeopen', get_string("reviewopens", "offlinequiz"),
                array('optional' => true, 'step' => 1));
        $mform->addHelpButton('timeopen', 'quizopenclose', 'offlinequiz');

        $mform->addElement('date_time_selector', 'timeclose', get_string("reviewcloses", "offlinequiz"),
                array('optional' => true, 'step' => 1));

        unset($options);
        $options = array();
        for ($i = 0; $i <= 3; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'decimalpoints', get_string('decimalplaces', 'offlinequiz'), $options);
        $mform->addHelpButton('decimalpoints', 'decimalplaces', 'offlinequiz');
        $mform->setDefault('decimalpoints', $offlinequizconfig->decimalpoints);

        // -------------------------------------------------------------------------
        $mform->addElement('header', 'layouthdr', get_string('formsheetsettings', 'offlinequiz'));

        unset($options);
        $options[610] = get_string("darkgray", "offlinequiz");
        $options[640] = get_string("lightgray", "offlinequiz");
        $options[670] = get_string("standard", "offlinequiz");
        $options[680] = get_string("white", "offlinequiz");
        $options[700] = get_string("pearlywhite", "offlinequiz");
        $mform->addElement('select', 'papergray', get_string('papergray', 'offlinequiz'), $options);
        $mform->addHelpButton('papergray', 'papergray', 'offlinequiz');
        $mform->setDefault('papergray', $offlinequizconfig->papergray);

        $mform->addElement('selectyesno', 'printstudycodefield', get_string('printstudycodefield', 'offlinequiz'), $attribs);
        $mform->addHelpButton('printstudycodefield', 'printstudycodefield', 'offlinequiz');
        $mform->setDefault('printstudycodefield', $offlinequizconfig->printstudycodefield);

        // ------------------------------------------------------------------------------

        if (!$offlinequiz || !$offlinequiz->docscreated) {
            $mform->addElement('editor', 'pdfintro', get_string('pdfintro', 'offlinequiz'), array('rows' => 20),
                     offlinequiz_get_editor_options($this->context));
        } else {
            $mform->addElement('static', 'pdfintro', get_string('pdfintro', 'offlinequiz'), $offlinequiz->pdfintro);
        }
        $mform->setType('pdfintro', PARAM_RAW);
        $mform->addHelpButton('pdfintro', 'pdfintro', 'offlinequiz');

        $options = array();
        $options[8] = 8;
        $options[9] = 9;
        $options[10] = 10;
        $options[12] = 12;
        $options[14] = 14;
        $mform->addElement('select', 'fontsize', get_string('fontsize', 'offlinequiz'), $options, $attribs);
        $mform->setDefault('fontsize', 10);

        $options = array();
        $options[OFFLINEQUIZ_PDF_FORMAT] = 'PDF';
        $options[OFFLINEQUIZ_DOCX_FORMAT] = 'DOCX';
        $options[OFFLINEQUIZ_LATEX_FORMAT] = 'LATEX';
        $mform->addElement('select', 'fileformat', get_string('fileformat', 'offlinequiz'), $options, $attribs);
        $mform->addHelpButton('fileformat', 'fileformat', 'offlinequiz');
        $mform->setDefault('fileformat', 0);

        $mform->addElement('selectyesno', 'showgrades', get_string("showgrades", "offlinequiz"), $attribs);
        $mform->addHelpButton('showgrades', "showgrades", "offlinequiz");

        $options = array();
        $options[OFFLINEQUIZ_QUESTIONINFO_NONE] = get_string("questioninfonone", "offlinequiz");
        $options[OFFLINEQUIZ_QUESTIONINFO_QTYPE] = get_string("questioninfoqtype", "offlinequiz");
        $options[OFFLINEQUIZ_QUESTIONINFO_ANSWERS] = get_string("questioninfoanswers", "offlinequiz");
        $mform->addElement('select', 'showquestioninfo', get_string("showquestioninfo", "offlinequiz"), $options, $attribs);
        $mform->addHelpButton('showquestioninfo', "showquestioninfo", "offlinequiz");

        $mform->addElement('selectyesno', 'disableimgnewlines', get_string("disableimgnewlines", "offlinequiz"), $attribs);
        $mform->addHelpButton('disableimgnewlines', 'disableimgnewlines', 'offlinequiz');
        $mform->setDefault('disableimgnewlines', $offlinequizconfig->disableimgnewlines);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'reviewoptionshdr', get_string("reviewoptions", "offlinequiz"));
        $mform->addHelpButton('reviewoptionshdr', 'reviewoptions', 'offlinequiz');

        $closedoptionsgrp = array();
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'attemptclosed', '', get_string('theattempt', 'offlinequiz'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'correctnessclosed', '', get_string('whethercorrect', 'question'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'marksclosed', '', get_string('marks', 'offlinequiz'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'specificfeedbackclosed', '',
                get_string('specificfeedback', 'question'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'generalfeedbackclosed', '',
                get_string('generalfeedback', 'question'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'rightanswerclosed', '',
                get_string('rightanswer', 'question'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'sheetclosed', '',
                get_string('scannedform', 'offlinequiz'));
        $closedoptionsgrp[] = &$mform->createElement('checkbox', 'gradedsheetclosed', '',
                get_string('gradedscannedform', 'offlinequiz'));
        $mform->addGroup($closedoptionsgrp, 'closedoptionsgrp', get_string("reviewincludes", "offlinequiz"), '<br />', false);
        $mform->setDefault('attemptclosed', $offlinequizconfig->reviewattempt );
        $mform->setDefault('correctnessclosed', $offlinequizconfig->reviewcorrectness );
        $mform->setDefault('marksclosed', $offlinequizconfig->reviewmarks );
        $mform->setDefault('specificfeedbackclosed', $offlinequizconfig->reviewspecificfeedback);
        $mform->setDefault('generalfeedbackclosed', $offlinequizconfig->reviewgeneralfeedback);
        $mform->setDefault('rightanswerclosed', $offlinequizconfig->reviewrightanswer );
        $mform->setDefault('sheetclosed', $offlinequizconfig->reviewsheet );
        $mform->setDefault('gradedsheetclosed', $offlinequizconfig->reviewgradedsheet);

        $mform->disabledIf('correctnessclosed', 'attemptclosed');
        $mform->disabledIf('specificfeedbackclosed', 'attemptclosed');
        $mform->disabledIf('generalfeedbackclosed', 'attemptclosed');
        $mform->disabledIf('rightanswerclosed', 'attemptclosed');
        $mform->setExpanded('reviewoptionshdr');
        // Try to insert student view for teachers.

        $language = current_language();

        $mform->addElement('html', '<input id="showviewbutton" type="button" class="btn btn-secondary" value="'.
                get_string('showstudentview', 'offlinequiz') . '" onClick="showStudentView(); return false;">');
        $mform->addElement('html', '<div class="Popup"><center><input type="button" class="closePopup"' .
                ' onClick="closePopup(); return false;" value="' . get_string('closestudentview', 'offlinequiz') .
                '"/></center><br/></div>');
        $mform->addElement('html', '<div id="overlay" class="closePopup"></div>');
        $mform->addElement('html', '<input id="basefilename" type="hidden" value="' . $CFG->wwwroot .
                '/mod/offlinequiz/pix/studentview/' . $language . '/img">');

        $module = array(
                'name'      => 'mod_offlinequiz_mod_form',
                'fullpath'  => '/mod/offlinequiz/mod_form.js',
                'requires'  => array(),
                'strings'   => array(),
                'async'     => false,
        );

        $PAGE->requires->jquery();
        $PAGE->requires->js('/mod/offlinequiz/mod_form.js');

        // -------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        // -------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     * (non-PHPdoc)
     * @see moodleform_mod::data_preprocessing()
     */
    public function data_preprocessing(&$toform) {
        if (!empty($this->_feedbacks)) {
            $key = 0;
            foreach ($this->_feedbacks as $feedback) {
                $toform['feedbacktext['.$key.']'] = $feedback->feedbacktext;
                if ($feedback->mingrade > 0) {
                    $toform['feedbackboundaries['.$key.']'] = (100.0 * $feedback->mingrade / $toform['grade']) . '%';
                }
                $key++;
            }

        }

        // Set the pdfintro text.
        if ($this->current->instance) {
            if (!$toform['docscreated']) {
                // Editing an existing pdfintro - let us prepare the added editor elements (intro done automatically).
                $draftitemid = file_get_submitted_draft_itemid('pdfintro');
                $text = file_prepare_draft_area($draftitemid, $this->context->id,
                                        'mod_offlinequiz', 'pdfintro', false,
                                        offlinequiz_get_editor_options($this->context),
                                        $toform['pdfintro']);
                $toform['pdfintro'] = array();
                $toform['pdfintro']['text'] = $text;
                $toform['pdfintro']['format'] = editors_get_preferred_format();
                $toform['pdfintro']['itemid'] = $draftitemid;
            }
        } else {
            // Adding a new feedback instance.
            $draftitemid = file_get_submitted_draft_itemid('pdfintro');

            // No context yet, itemid not used.
            file_prepare_draft_area($draftitemid, null, 'mod_offlinequiz', 'pdfintro', false);
            $toform['pdfintro'] = array();
            $toform['pdfintro']['text'] = get_string('pdfintrotext', 'offlinequiz');
            $toform['pdfintro']['format'] = editors_get_preferred_format();
            $toform['pdfintro']['itemid'] = $draftitemid;
        }

        if (empty($toform['timelimit'])) {
            $toform['timelimitenable'] = 0;
        } else {
            $toform['timelimitenable'] = 1;
        }

        if (isset($toform['review'])) {
            $review = (int) $toform['review'];
            unset($toform['review']);

            $toform['attemptclosed'] = $review & OFFLINEQUIZ_REVIEW_ATTEMPT;
            $toform['correctnessclosed'] = $review & OFFLINEQUIZ_REVIEW_CORRECTNESS;
            $toform['marksclosed'] = $review & OFFLINEQUIZ_REVIEW_MARKS;
            $toform['specificfeedbackclosed'] = $review & OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK;
            $toform['generalfeedbackclosed'] = $review & OFFLINEQUIZ_REVIEW_GENERALFEEDBACK;
            $toform['rightanswerclosed'] = $review & OFFLINEQUIZ_REVIEW_RIGHTANSWER;
            $toform['sheetclosed'] = $review & OFFLINEQUIZ_REVIEW_SHEET;
            $toform['gradedsheetclosed'] = $review & OFFLINEQUIZ_REVIEW_GRADEDSHEET;
        }
    }

    /**
     * (non-PHPdoc)
     * @see moodleform_mod::validation()
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeopen'] != 0 && $data['timeclose'] != 0 && $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'offlinequiz');
        }
        return $errors;
    }
}
