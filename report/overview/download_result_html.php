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
 * Result review download page
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.7
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace offlinequiz_result_download;

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');

class html_download {

    private $offlinequiz;
    private $course;
    private $cm;
    private $context;

    public function __construct($offlinequizid) {
        global $DB;
        if (!$offlinequiz = $DB->get_record("offlinequiz", array("id" => $offlinequizid))) {
            print_error("The offlinequiz with id $offlinequizid belonging to result $result is missing");
        }
        $this->offlinequiz = $offlinequiz;        if (!$course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
            print_error(
             "The course with id $offlinequiz->course that the offlinequiz with id $offlinequiz->id belongs to is missing");
        }
        $this->course = $course;
        if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
            print_error("The course module for the offlinequiz with id $offlinequiz->id is missing");
        }
        $this->cm = $cm;
        $this->context = \context_module::instance($cm->id);
    }


    public function printhtml($userids = null) {
        global $DB, $PAGE, $OUTPUT, $CFG;
        if (!$userids) {
            $sql = 'SELECT u.id FROM {user} u, {offlinequiz_results} r
                     WHERE r.offlinequizid = :offlinequizid
                       AND r.userid = u.id';
            $DB->get_fieldset_sql($sql, ['offlinequizid' => $this->offlinequiz->id]);
        }
        $strscore  = get_string("marks", "offlinequiz");
        $strgrade  = get_string("grade");
        require_login($this->course->id, false, $this->cm);

        $isteacher = has_capability('mod/offlinequiz:viewreports', $this->context);
        if (!$isteacher) {
            // This view is only allowed for teachers who are allowed to see the review
            redirect('../view.php?q=' . $offlinequiz->id, get_string("noreview", "offlinequiz"));
            return;
        }
        $letterstr = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Setup the page and print the page header.

        $PAGE->set_title(format_string($this->offlinequiz->name));
        $PAGE->set_heading($this->course->fullname);
        $PAGE->set_pagelayout('print');
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($this->offlinequiz->name));
        // Load the module's global config.
        echo '<div id="page-mod-offlinequiz-print-html">';
        offlinequiz_load_useridentification();
        $offlinequizconfig = get_config('offlinequiz');

        $resultids = $this->get_result_ids($userids);
        foreach ($resultids as $resultid) {
            if (!$result = $DB->get_record("offlinequiz_results", array("id" => $resultid))) {
                print_error("The offlinequiz result with id $resultid is missing");

            }
            if (!$group = $DB->get_record("offlinequiz_groups", array('id' => $result->offlinegroupid))) {
                print_error("The offlinequiz group belonging to result $result is missing");
            }
            $grade = offlinequiz_rescale_grade($result->sumgrades, $this->offlinequiz, $group);
            $options = offlinequiz_get_review_options($this->offlinequiz, $result, $this->context);
            $options->manualcommentlink = null;
            echo '<div class="pagebreak">';
            echo $OUTPUT->heading(get_string('reviewofresult', 'offlinequiz'));
            // --------------------------------------
            // Print info table with user details.
            // --------------------------------------
            $timelimit = 0;

            $table = new \html_table();
            $table->attributes['class'] = 'generaltable offlinequizreviewsummary';
            $table->align  = array("right", "left");
            $student = $DB->get_record('user', array('id' => $result->userid));
            $picture = $OUTPUT->user_picture($student);
            $table->data[] = array($picture, '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $student->id .
              '&amp;course=' . $this->course->id . '">' . fullname($student, true) . ' ('.$student->username.')</a>');
            $table->data[] = array(get_string('group') . ':', $letterstr[$group->groupnumber]);
            if (!empty($this->offlinequiz->time)) {
                   $table->data[] = array(get_string('quizdate', 'offlinequiz').':', userdate($this->offlinequiz->time));
            }

            // If the student is allowed to see his score.
            if ($options->marks != \question_display_options::HIDDEN) {
                if ($this->offlinequiz->grade && $group->sumgrades) {

                    $resultmark = format_float($result->sumgrades, $this->offlinequiz->decimalpoints);
                    $maxmark = format_float($group->sumgrades, $this->offlinequiz->decimalpoints);
                    $percentage = format_float(($result->sumgrades * 100.0 / $group->sumgrades), $this->offlinequiz->decimalpoints);
                    $table->data[] = array($strscore . ':', $resultmark . '/' . $maxmark . ' (' . $percentage . '%)');

                    $a = new \stdClass;
                    $a->grade = format_float(preg_replace('/,/i', '.', $grade), $this->offlinequiz->decimalpoints);
                    $a->maxgrade = format_float($this->offlinequiz->grade, $this->offlinequiz->decimalpoints);
                    $table->data[] = array($strgrade . ':', get_string('outof', 'offlinequiz', $a));
                }
            }

            echo \html_writer::table($table);

            if ($options->sheetfeedback == \question_display_options::VISIBLE ||
              $options->gradedsheetfeedback == \question_display_options::VISIBLE) {

                     $user = $DB->get_record('user', array('id' => $result->userid));
                     $userkey = $user->{$offlinequizconfig->ID_field};

                     $scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('resultid' => $result->id), 'pagenumber ASC');
                     // Options for the popup_action.

                if ($options->attempt == \question_display_options::VISIBLE) {
                    // Load the questions needed by page.
                    if (!$quba = \question_engine::load_questions_usage_by_activity($result->usageid)) {
                        print_error('Could not load question usage');
                    }

                     $slots = $quba->get_slots();

                    foreach ($slots as $id => $slot) {
                         $questionnumber = $slot; // Descriptions make this more complex.
                         echo $quba->render_question($slot, $options, $questionnumber);
                    }
                }
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private function get_result_ids($userids) {
        global $DB;
        if ($userids) {
            list($insql, $inparams) = $DB->get_in_or_equal($userids);
            $sql = "SELECT id FROM {offlinequiz_results}WHERE offlinequizid = ? AND status='complete' AND userid IN $insql";
            return $DB->get_fieldset_sql($sql, [$this->offlinequiz->id, $inparams]);
        }
        return $DB->get_fieldset_sql("SELECT id FROM {offlinequiz_results} WHERE offlinequizid = ? AND status='complete'",
         [$this->offlinequiz->id]);
    }
}
