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
 * The results overview report for offlinequizzes
 *
 * @package offlinequiz_overview
 * @subpackage offlinequiz
 * @author Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright 2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since Moodle 2.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 */

namespace offlinequiz_overview;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');

/**
 * the html download
 */
class html_download {
    /**
     * offlinequiz db entry
     * @var \stdClass
     */
    private $offlinequiz;
    /**
     * Course
     * @var \stdClass
     */
    private $course;
    /**
     * course module
     * @var \stdClass
     */
    private $cm;
    /**
     * context of this offlinequiz
     * @var \context_module
     */
    private $context;

    /**
     * construct the offlinequiz
     * @param mixed $offlinequizid
     * @throws \moodle_exception
     */
    public function __construct($offlinequizid) {
        global $DB;
        if (!$offlinequiz = $DB->get_record("offlinequiz", ["id" => $offlinequizid])) {
            throw new \moodle_exception("The offlinequiz with id $offlinequizid belonging to result is missing");
        }
        $this->offlinequiz = $offlinequiz;        if (!$course = $DB->get_record("course", ['id' => $offlinequiz->course])) {
            throw new \moodle_exception(
             "The course with id $offlinequiz->course that the offlinequiz with id $offlinequiz->id belongs to is missing");
        }
        $this->course = $course;
        if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
            throw new \moodle_exception("The course module for the offlinequiz with id $offlinequiz->id is missing");
        }
        $this->cm = $cm;
        $this->context = \context_module::instance($cm->id);
    }

    /**
     * print the html
     * @param mixed $userids
     * @throws \moodle_exception
     * @return void
     */
    public function printhtml($userids = null) {
        global $DB, $PAGE, $OUTPUT, $CFG;
        if (!$userids) {
            $sql = 'SELECT u.id FROM {user} u, {offlinequiz_results} r
                     WHERE r.offlinequizid = :offlinequizid
                       AND r.userid = u.id';
            $DB->get_fieldset_sql($sql, ['offlinequizid' => $this->offlinequiz->id]);
        }
        $strscore  = get_string('marks', 'offlinequiz');
        $strgrade  = get_string('grade', 'offlinequiz');
        require_login($this->course->id, false, $this->cm);

        $isteacher = has_capability('mod/offlinequiz:viewreports', $this->context);
        if (!$isteacher) {
            // This view is only allowed for teachers who are allowed to see the review
            redirect('../view.php?q=' . $this->offlinequiz->id, get_string("noreview", "offlinequiz"));
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
        $offlinequizconfig = get_config('offlinequiz');

        $resultids = $this->get_result_ids($userids);
        foreach ($resultids as $resultid) {
            if (!$result = $DB->get_record("offlinequiz_results", ["id" => $resultid])) {
                throw new \moodle_exception("The offlinequiz result with id $resultid is missing");

            }
            if (!$group = $DB->get_record("offlinequiz_groups", ['id' => $result->offlinegroupid])) {
                throw new \moodle_exception("The offlinequiz group belonging to result $result is missing");
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
            $table->align  = ["right", "left"];
            $student = $DB->get_record('user', ['id' => $result->userid]);
            $picture = $OUTPUT->user_picture($student);
            $table->data[] = [$picture, '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $student->id .
              '&amp;course=' . $this->course->id . '">' . fullname($student, true) . ' ('.$student->username.')</a>'];
            $table->data[] = [get_string('group') . ':', $letterstr[$group->groupnumber]];
            if (!empty($this->offlinequiz->time)) {
                   $table->data[] = [get_string('quizdate', 'offlinequiz').':', userdate($this->offlinequiz->time)];
            }

            // If the student is allowed to see his score.
            if ($options->marks != \question_display_options::HIDDEN) {
                if ($this->offlinequiz->grade && $group->sumgrades) {

                    $resultmark = format_float($result->sumgrades, $this->offlinequiz->decimalpoints);
                    $maxmark = format_float($group->sumgrades, $this->offlinequiz->decimalpoints);
                    $percentage = format_float(($result->sumgrades * 100.0 / $group->sumgrades), $this->offlinequiz->decimalpoints);
                    $table->data[] = [$strscore . ':', $resultmark . '/' . $maxmark . ' (' . $percentage . '%)'];

                    $a = new \stdClass;
                    $a->grade = format_float(preg_replace('/,/i', '.', $grade), $this->offlinequiz->decimalpoints);
                    $a->maxgrade = format_float($this->offlinequiz->grade, $this->offlinequiz->decimalpoints);
                    $table->data[] = [$strgrade . ':', get_string('outof', 'offlinequiz', $a)];
                }
            }

            echo \html_writer::table($table);

            if ($options->sheetfeedback == \question_display_options::VISIBLE ||
              $options->gradedsheetfeedback == \question_display_options::VISIBLE) {

                // Options for the popup_action.
                if ($options->attempt == \question_display_options::VISIBLE) {
                    // Load the questions needed by page.
                    if (!$quba = \question_engine::load_questions_usage_by_activity($result->usageid)) {
                        throw new \moodle_exception('Could not load question usage');
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
    /**
     * get the result ids for given users of this offlinequiz
     * @param mixed $userids
     * @return array
     */
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
