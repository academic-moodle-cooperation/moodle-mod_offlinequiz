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
 * Define all the backup steps that will be used by the backup_offlinequiz_activity_task
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();


class backup_offlinequiz_activity_structure_step extends backup_questions_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separately.
        $offlinequiz = new backup_nested_element('offlinequiz', array('id'), array(
                'name', 'intro', 'pdfintro', 'timeopen',
                'timeclose', 'time', 'grade', 'numgroups', 'decimalpoints',
                'review', 'questionsperpage', 'docscreated', 'shufflequestions', 'shuffleanswers',
                'questions', 'sumgrades', 'papergray', 'fontsize', 'timecreated',
                'timemodified', 'fileformat', 'showgrades', 'showquestioninfo', 'disableimgnewlines', 'showtutorial',
                'printstudycodefield', 'id_digits'));

        $qinstances = new backup_nested_element('question_instances');

        $qinstance = new backup_nested_element('question_instance', array('id'), array(
                'questionid', 'grade'));

        $groups = new backup_nested_element('groups');
        $group = new backup_nested_element('group', array('id'), array(
                'number', 'sumgrades', 'numberofpages', 'templateusageid',
                'questionfilename', 'answerfilename', 'correctionfilename'));

        $groupquestions = new backup_nested_element('groupquestions');
        $groupquestion = new backup_nested_element('groupquestion', array('id'), array(
                'questionid', 'position', 'page', 'slot', 'maxmark'));

        $results = new backup_nested_element('results');

        $result = new backup_nested_element('result', array('id'), array(
                'offlinegroupid', 'userid', 'usageid', 'teacherid', 'sumgrades',
                'attendant', 'status', 'timestart', 'timefinish', 'timemodified',
                'preview'));

        $scannedpages = new backup_nested_element('scannedpages');

        $scannedpage = new backup_nested_element('scannedpage', array('id'), array(
                'resultid', 'filename' , 'warningfilename', 'groupnumber', 'userkey', 'pagenumber',
                'time', 'status', 'error', 'info'));

        $choices = new backup_nested_element('choices');

        $choice = new backup_nested_element('choice', array('id'), array(
                'slotnumber', 'choicenumber', 'value'));

        $corners = new backup_nested_element('corners');
        $corner = new backup_nested_element('corner', array('id'), array(
                'x', 'y', 'position'));

        $plists = new backup_nested_element('plists');
        $plist = new backup_nested_element('plist', array('id'), array(
                'name', 'number', 'filename'));

        $participants = new backup_nested_element('participants');
        $participant = new backup_nested_element('participant', array('id'), array(
                'userid', 'checked'));

        $scannedppages = new backup_nested_element('scannedppages');
        $scannedppage = new backup_nested_element('scannedppage', array('id'), array(
                'listnumber', 'filename', 'time', 'status', 'error'));

        $pchoices = new backup_nested_element('pchoices');

        $pchoice = new backup_nested_element('pchoice', array('id'), array(
                'userid', 'value'));

        // This module is using questions, so produce the related question states and sessions
        // attaching them to the $result element based in 'uniqueid' matching.

        // TODO once the Moodle bug is fixed.
        $this->add_question_usages($result, 'usageid', 'result_');
        $this->add_question_usages($group, 'templateusageid', 'group_');

        // Group questions are children of groups which are children of the offlinequiz.
        $groups->add_child($group);
        $group->add_child($groupquestions);
        $groupquestions->add_child($groupquestion);
        $offlinequiz->add_child($groups);

        // Results are children of the offlinequiz.
        $results->add_child($result);
        $offlinequiz->add_child($results);

        // Build the tree.
        // Question instances are children of the offlinequiz.
        $qinstances->add_child($qinstance);
        $offlinequiz->add_child($qinstances);

        // Choices and corners are children of scannedpages which are children of the offlinequiz.
        $scannedpage->add_child($choices);
        $choices->add_child($choice);
        $scannedpage->add_child($corners);
        $corners->add_child($corner);

        $offlinequiz->add_child($scannedpages);
        $scannedpages->add_child($scannedpage);

        // Lists of participants are children of the offlinequiz.
        $offlinequiz->add_child($plists);
        $plists->add_child($plist);
        // Participants are children of lists of participants.
        $plist->add_child($participants);
        $participants->add_child($participant);

        // Scanned participants pages are children of the offlinequiz.
        $offlinequiz->add_child($scannedppages);
        $scannedppages->add_child($scannedppage);
        $scannedppage->add_child($pchoices);
        $pchoices->add_child($pchoice);

        // Define sources.
        $offlinequiz->set_source_table('offlinequiz', array('id' => backup::VAR_ACTIVITYID));

        $group->set_source_table('offlinequiz_groups',
                array('offlinequizid' => backup::VAR_PARENTID));

        $groupquestion->set_source_table('offlinequiz_group_questions',
                array('offlinegroupid' => backup::VAR_PARENTID));

        $plist->set_source_table('offlinequiz_p_lists',
                array('offlinequizid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {

            $result->set_source_sql('
                    SELECT *
                    FROM {offlinequiz_results}
                    WHERE offlinequizid = :offlinequizid
                    ',
                    array('offlinequizid' => backup::VAR_PARENTID));

            $scannedpage->set_source_table('offlinequiz_scanned_pages',
                    array('offlinequizid' => backup::VAR_PARENTID));

            $choice->set_source_table('offlinequiz_choices',
                    array('scannedpageid' => backup::VAR_PARENTID));

            $corner->set_source_table('offlinequiz_page_corners',
                    array('scannedpageid' => backup::VAR_PARENTID));

            // Add participants info only when userinfo.
            $participant->set_source_table('offlinequiz_participants',
                    array('listid' => backup::VAR_PARENTID));

            $scannedppage->set_source_table('offlinequiz_scanned_p_pages',
                    array('offlinequizid' => backup::VAR_PARENTID));

            $pchoice->set_source_table('offlinequiz_p_choices',
                    array('scannedppageid' => backup::VAR_PARENTID));

        }

        // Define file annotations.
        $offlinequiz->annotate_files('mod_offlinequiz', 'intro', null);
        $offlinequiz->annotate_files('mod_offlinequiz', 'imagefiles', null); // This file area has no itemid.
        $offlinequiz->annotate_files('mod_offlinequiz', 'pdfs', null);

        // Define id annotations.
        $result->annotate_ids('user', 'userid');
        $result->annotate_ids('user', 'teacherid');

        // Return the root element (offlinequiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($offlinequiz);
    }
}
