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
 * Define the steps used by the restore_offlinequiz_activity_task
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one offlinequiz activity
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_offlinequiz_activity_structure_step extends restore_questions_activity_structure_step {

    private $currentofflinequizresult = null;
    private $currentofflinegroup = null;

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $offlinequiz = new restore_path_element('offlinequiz', '/activity/offlinequiz');
        $paths[] = $offlinequiz;

        // Scanned pages and their choices and corners.
        $paths[] = new restore_path_element('offlinequiz_scannedpage', '/activity/offlinequiz/scannedpages/scannedpage');
        $paths[] = new restore_path_element('offlinequiz_choice', '/activity/offlinequiz/scannedpages/scannedpage/choices/choice');
        $paths[] = new restore_path_element('offlinequiz_corner', '/activity/offlinequiz/scannedpages/scannedpage/corners/corner');

        // Lists of participants and their scanned pages.
        $paths[] = new restore_path_element('offlinequiz_plist',
                 '/activity/offlinequiz/plists/plist');
        $paths[] = new restore_path_element('offlinequiz_participant',
                 '/activity/offlinequiz/plists/plist/participants/participant');
        $paths[] = new restore_path_element('offlinequiz_scannedppage',
                 '/activity/offlinequiz/scannedppages/scannedppage');
        $paths[] = new restore_path_element('offlinequiz_pchoice',
                 '/activity/offlinequiz/scannedppages/scannedppage/pchoices/pchoice');

        // Handle offlinequiz groups.
        // We need to identify this path to add the question usages.
        $offlinequizgroup = new restore_path_element('offlinequiz_group',
                '/activity/offlinequiz/groups/group');
        $paths[] = $offlinequizgroup;

        // Add template question usages for offline groups.
        $this->add_question_usages($offlinequizgroup, $paths, 'group_');

        $paths[] = new restore_path_element('offlinequiz_groupquestion',
                 '/activity/offlinequiz/groups/group/groupquestions/groupquestion');

        // We only add the results if userinfo was activated.
        if ($userinfo) {
            $offlinequizresult = new restore_path_element('offlinequiz_result',
                    '/activity/offlinequiz/results/result');
            $paths[] = $offlinequizresult;

            // Add the results' question usages.
            $this->add_question_usages($offlinequizresult, $paths, 'result_');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    // Dummy methods for group question usages.
    public function process_group_question_usage($data) {
        $this->restore_question_usage_worker($data, 'group_');
    }

    public function process_group_question_attempt($data) {
        $this->restore_question_attempt_worker($data, 'group_');
    }

    public function process_group_question_attempt_step($data) {
        $this->restore_question_attempt_step_worker($data, 'group_');
    }

    public function process_result_question_usage($data) {
        $this->restore_question_usage_worker($data, 'result_');
    }

    public function process_result_question_attempt($data) {
        $this->restore_question_attempt_worker($data, 'result_');
    }

    public function process_result_question_attempt_step($data) {
        $this->restore_question_attempt_step_worker($data, 'result_');
    }

    // Restore method for the activity.
    protected function process_offlinequiz($data) {
        global $CFG, $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->questions = '';

        // The offlinequiz->results can come both in data->results and
        // data->results_number, handle both. MDL-26229.
        if (isset($data->results_number)) {
            $data->results = $data->results_number;
            unset($data->results_number);
        }

        // Insert the offlinequiz record.
        $newitemid = $DB->insert_record('offlinequiz', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    // Restore method for offline groups.
    protected function process_offlinequiz_group($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');

        if (empty($data->templateusageid)) {

            $newitemid = $DB->insert_record('offlinequiz_groups', $data);
            // Save offlinequiz_group->id mapping, because logs use it.
            $this->set_mapping('offlinequiz_group', $oldid, $newitemid, false);
        } else {
            // The data is actually inserted into the database later in inform_new_usage_id.
            $this->currentofflinegroup = clone($data);
        }
    }


    // Restore method for offlinequiz group questions.
    protected function process_offlinequiz_groupquestion($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Backward compatibility for old field names prior to Moodle 2.8.5.
        if (isset($data->usageslot) && !isset($data->slot)) {
            $data->slot = $data->usageslot;
        }
        if (isset($data->pagenumber) && !isset($data->page)) {
            $data->page = $data->pagenumber;
        }

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');
        $data->offlinegroupid = $this->get_new_parentid('offlinequiz_group');
        if ($newid = $this->get_mappingid('question', $data->questionid)) {
            $data->questionid = $newid;
        }
        $newitemid = $DB->insert_record('offlinequiz_group_questions', $data);
    }

    // Restore method for scanned pages.
    protected function process_offlinequiz_scannedpage($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');
        $data->resultid = $this->get_mappingid('offlinequiz_result', $data->resultid);

        $newitemid = $DB->insert_record('offlinequiz_scanned_pages', $data);
        $this->set_mapping('offlinequiz_scannedpage', $oldid, $newitemid, true);
    }

    // Restore method for choices on scanned pages.
    protected function process_offlinequiz_choice($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->scannedpageid = $this->get_new_parentid('offlinequiz_scannedpage');

        $newitemid = $DB->insert_record('offlinequiz_choices', $data);
    }

    // Restore method for corners of scanned pages.
    protected function process_offlinequiz_corner($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->scannedpageid = $this->get_new_parentid('offlinequiz_scannedpage');

        $newitemid = $DB->insert_record('offlinequiz_page_corners', $data);
    }

    // Restore method for scanned participants pages.
    protected function process_offlinequiz_scannedppage($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');

        $newitemid = $DB->insert_record('offlinequiz_scanned_p_pages', $data);
        $this->set_mapping('offlinequiz_scannedppage', $oldid, $newitemid, true);
    }

    // Restore method for choices on scanned participants pages.
    protected function process_offlinequiz_pchoice($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->scannedppageid = $this->get_new_parentid('offlinequiz_scannedppage');

        $newitemid = $DB->insert_record('offlinequiz_p_choices', $data);
    }

    // Restore method for lists of participants.
    protected function process_offlinequiz_plist($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');

        $newitemid = $DB->insert_record('offlinequiz_p_lists', $data);
        $this->set_mapping('offlinequiz_plist', $oldid, $newitemid, true);
    }

    // Restore method for a participant.
    protected function process_offlinequiz_participant($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->listid = $this->get_new_parentid('offlinequiz_plist');

        $newitemid = $DB->insert_record('offlinequiz_participants', $data);
    }

    // Restore method for offlinequiz results (attempts).
    protected function process_offlinequiz_result($data) {
        global $DB;

        $data = (object) $data;

        $data->offlinequizid = $this->get_new_parentid('offlinequiz');

        $data->offlinegroupid = $this->get_mappingid('offlinequiz_group', $data->offlinegroupid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->teacherid = $this->get_mappingid('user', $data->teacherid);
        // The usageid is set in the function below.

        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timefinish = $this->apply_date_offset($data->timefinish);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $this->currentofflinequizresult = clone($data);
    }

    // Restore the usage id after it has been created.
    protected function inform_new_usage_id($newusageid) {
        global $DB;

        // We might be dealing with a result.
        $data = $this->currentofflinequizresult;
        if ($data) {
            $this->currentofflinequizresult = null;
            $oldid = $data->id;
            $data->usageid = $newusageid;

            $newitemid = $DB->insert_record('offlinequiz_results', $data);

            // Save offlinequiz_result->id mapping, because scanned pages use it.
            $this->set_mapping('offlinequiz_result', $oldid, $newitemid, false);
        } else {
            // Or we might be dealing with an offlinequiz group.
            $data = $this->currentofflinegroup;
            if ($data) {
                $this->currentofflinegroup = null;
                $oldid = $data->id;
                $data->templateusageid = $newusageid;

                $newitemid = $DB->insert_record('offlinequiz_groups', $data);

                // Save offlinequiz_group->id mapping, because offlinequiz_results use it.
                $this->set_mapping('offlinequiz_group', $oldid, $newitemid, false);
            }
        }
    }

    protected function after_execute() {
        parent::after_execute();
        // Add offlinequiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_offlinequiz', 'intro', null);
        $this->add_related_files('mod_offlinequiz', 'imagefiles', null);
        $this->add_related_files('mod_offlinequiz', 'pdfs', null);
    }
}
