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
 * Page for viewing scanned answer forms to students
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/default.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/scanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');

$resultid = required_param('resultid', PARAM_INT);
$pageid      = optional_param('pageid', 0, PARAM_INT);

if (!$result = $DB->get_record('offlinequiz_results', array('id' => $resultid))) {
    print_error('noresult', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}
if (!$scannedpage = $DB->get_record('offlinequiz_scanned_pages', array('id' => $pageid))) {
    print_error('nopage', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}
if (!$offlinequiz = $DB->get_record("offlinequiz", array('id' => $result->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}
if (!$course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error('nocm', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}
if (!$groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0,
        $offlinequiz->numgroups)) {
    print_error('nogroups', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);

if (!has_capability('mod/offlinequiz:viewreports', $context) and !has_capability('mod/offlinequiz:attempt', $context)) {
    print_error('noaccess', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

if (!has_capability('mod/offlinequiz:viewreports', $context) and $result->userid != $USER->id) {
    print_error('noaccess', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

$options = offlinequiz_get_review_options($offlinequiz, $result, $context);

if (!$options->sheetfeedback and !$options->gradedsheetfeedback) {
    print_error('noaccess', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

$url = new moodle_url('/mod/offlinequiz/image.php', array('pageid' => $scannedpage->id, 'resultid' => $result->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');

echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo "</style>\n";
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');
$group = $groups[$result->offlinegroupid];
$offlinequiz->groupid = - $group->id;

list($maxquestions, $maxanswers, $formtype, $questionsperpage) = offlinequiz_get_question_numbers($offlinequiz, array($group));

$offlinequizconfig->papergray = $offlinequiz->papergray;

// Load corners from DB.
$dbcorners = $DB->get_records('offlinequiz_page_corners', array('scannedpageid' => $scannedpage->id));
$corners = array();
foreach ($dbcorners as $corner) {
    $corners[] = new oq_point($corner->x, $corner->y);
}

// Initialize a page scanner.
$scanner = new offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);

// Load the stored picture file.
$sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);

$pagenumber = $scannedpage->pagenumber;

// Make a first check.
$scanner->check_deleted();
$scanner->calibrate_and_get_group();
$scanner->get_usernumber();
$scanner->get_page();
// Necessary s.t. we can get the answer hotspots from the scanner.
$scanner->set_page($pagenumber);

$quba = question_engine::load_questions_usage_by_activity($result->usageid);
$slots = $quba->get_slots();

// Determine the slice of slots we are interested in.
// We start at the top of the page (e.g. 0, 96, etc).
$startindex = min(($pagenumber - 1) * $questionsperpage, count($slots));
// We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
$endindex = min( $pagenumber * $questionsperpage, count($slots) );

// Load the choices made before from the database. There might not be any.
$choices = $DB->get_records('offlinequiz_choices', array('scannedpageid' => $scannedpage->id), 'slotnumber, choicenumber');

// Choicesdata contains the choices data from the DB indexed by slotnumber and choicenumber.
$choicesdata = array();
if (!empty($choices)) {
    foreach ($choices as $choice) {
        if (!isset($choicesdata[$choice->slotnumber]) || !is_array($choicesdata[$choice->slotnumber])) {
            $choicesdata[$choice->slotnumber] = array();
        }
        $choicesdata[$choice->slotnumber][$choice->choicenumber] = $choice;
    }
}

if ($sheetloaded) {
    // Print image of the form sheet.
    $fs = get_file_storage();
    $imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename);

    // E.g. http://131.130.103.117/mod_offlinequiz/pluginfile.php/65/mod_offlinequiz/imagefiles/0/zimmer.png_1.
    echo '<img name="formimage" src="' . $CFG->wwwroot . "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" .
    $imagefile->get_filename() .'" border="1" width="' . OQ_IMAGE_WIDTH .
        '" style="position:absolute; top:0px; left:0px; display: block;">';

    $answerspots = $scanner->export_hotspots_answer(OQ_IMAGE_WIDTH);

    if ($options->gradedsheetfeedback) {

        $questionids = offlinequiz_get_group_question_ids($offlinequiz, $group->id);

        list($qsql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
        $params['offlinequizid'] = $offlinequiz->id;
        $params['offlinegroupid'] = $group->id;

        $sql = "SELECT q.*, ogq.maxmark
                  FROM {question} q,
                       {offlinequiz_group_questions} ogq
                 WHERE ogq.offlinequizid = :offlinequizid
                   AND ogq.offlinegroupid = :offlinegroupid
                   AND q.id = ogq.questionid
                   AND q.id $qsql";

        // Load the questions.
        if (!$questions = $DB->get_records_sql($sql, $params)) {
            $viewurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/view.php',
                    array('q' => $offlinequiz->id));
            print_error('noquestionsfound', 'offlinequiz', $viewurl);
        }
        // Load the question type specific information.
        if (!get_question_options($questions)) {
            print_error('Could not load question options');
        }

        $questioncounter = 0;
        for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {

            $slot = $slots[$slotindex];
            $slotquestion = $quba->get_question($slot);
            $attempt = $quba->get_question_attempt($slot);
            $order = $slotquestion->get_order($attempt);  // Order of the answers.
            $answers = $slotquestion->answers;

            // Go through all answers of the slot question.
            foreach ($order as $key => $answerid) {
                $hotspot = $answerspots['a-' . $questioncounter . '-' . $key];
                $index = explode('-', $key);
                $questionid = $slotquestion->id;

                if ($answers[$answerid]->fraction > 0 and $choicesdata[$slot][$key]->value == 1) {
                    // The student crossed a correct answer.
                    echo "<img title=\"".get_string('question') . ' ' .
                            ($slotindex + 1) . ' ' . get_string('answer') . ' ' . ($key + 1) .
                            "\" src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\"" .
                            " id=\"a-$slotindex-$key\" style=\"position:absolute; top:" .
                            $hotspot->y."px; left:".$hotspot->x."px;\">";
                } else if ($answers[$answerid]->fraction > 0 and $choicesdata[$slot][$key]->value == 0) {
                    // The student did not cross a correct answer.
                    echo "<img title=\"".get_string('question') . ' ' .
                            ($slotindex + 1) . ' ' . get_string('answer') . ' ' . ($key + 1) .
                            "\" src=\"$CFG->wwwroot/mod/offlinequiz/pix/missing.png\" border=\"0\"" .
                            " id=\"a-$slotindex-$key\" style=\"position:absolute; top:".
                            ($hotspot->y - 2) . "px; left:".($hotspot->x - 2)."px;\">";
                } else if ($answers[$answerid]->fraction <= 0 and $choicesdata[$slot][$key]->value == 1) {
                    // The student crossed an answer that is wrong.
                    echo "<img title=\"".get_string('question') . ' ' .
                            ($slotindex + 1) . ' ' . get_string('answer') . ' ' . ($key + 1) .
                            "\" src=\"$CFG->wwwroot/mod/offlinequiz/pix/wrong.png\" border=\"0\"" .
                            " id=\"a-$slotindex-$key\" style=\"position:absolute; top:" .
                            ($hotspot->y - 2)."px; left:".($hotspot->x - 2)."px;\">";
                }
            }
            $questioncounter++;
        }

        // Print info about markings.
        echo "<div style=\"font-size:80%; position:absolute; top:10px; left:" . (OQ_IMAGE_WIDTH + 40) . "px; width:280px\">\n";
        echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"" .
             " style=\"vertical-align: text-bottom; width: 14px; height:14px;\" border=\"0\" id=\"legend-green\"> ";
        echo get_string('greeniscross', 'offlinequiz').'<br/>';
        echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/missing.png\"" .
             " style=\"vertical-align: text-bottom; width: 14px; height:14px;\" border=\"0\" id=\"legend-green\"> ";
        echo get_string('rediswrong', 'offlinequiz').'<br/>';
        echo '</div>';
        echo '<div style="position: absolute; top: 80px; width:280px; left: ' . (OQ_IMAGE_WIDTH + 40) . 'px;">';

        echo '<table callpadding="4" border="1"><tr><td style="vertical-align: top;">'; // Outer table.
        echo '<table cellpadding="3" style=" font-size: 80%;"><tr><td><strong>' .
             get_string('question').'&nbsp;&nbsp;&nbsp;</strong></td><td><strong>' .
             get_string('points', 'grades') . '</strong></td></tr>';

        // Print questions and points in a two column table.
        $middle = $startindex + round(($endindex - $startindex) / 2);

        for ($slotindex = $startindex; $slotindex < $middle; $slotindex++) {
            $slot = $slots[$slotindex];
            $slotquestion = $quba->get_question($slot);
            $attempt = $quba->get_question_attempt($slot);
            $question = $questions[$slotquestion->id];
            echo "<tr><td align=\"right\"><strong>" . ($slotindex + 1) . ":&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>";
            $mark = $attempt->get_mark();
            if ($mark) {
                $grade = round($mark, $offlinequiz->decimalpoints);
            } else {
                $grade = 0;
            }
            $maxgrade = $question->maxmark + 0;
            echo "<td>$grade / $maxgrade</td></tr>";
        }
        echo "</table>"; // Inner table 1.
        echo "</td><td style=\"vertical-align: top;\">";
        echo '<table cellpadding="3" style=" font-size: 80%;"><tr><td><strong>' .
              get_string('question').'&nbsp;&nbsp;&nbsp;</strong></td><td><strong>' .
              get_string('points', 'grades').'</strong></td></tr>';

        for ($slotindex = $middle; $slotindex < $endindex; $slotindex++) {
            $slot = $slots[$slotindex];
            $slotquestion = $quba->get_question($slot);
            $attempt = $quba->get_question_attempt($slot);
            $question = $questions[$slotquestion->id];
            echo "<tr><td align=\"right\"><strong>" . ($slotindex + 1) . ":&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>";
            $mark = $attempt->get_mark();
            if ($mark) {
                $grade = round($mark, $offlinequiz->decimalpoints);
            } else {
                $grade = 0;
            }
            $maxgrade = $question->maxmark + 0;
            echo "<td>$grade / $maxgrade</td></tr>";
        }
        if (($endindex - $middle) == 1) {
            echo "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
        }

        echo "</table>";  // Inner table 2.
        echo '</td></tr></table>';  // Outer table.
        echo "\n<br /></div>\n";
    } else {

        // Print hotspots for answers.
        $questioncounter = 0;
        for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {

            $slot = $slots[$slotindex];
            $slotquestion = $quba->get_question($slot);
            $attempt = $quba->get_question_attempt($slot);
            $order = $slotquestion->get_order($attempt);  // Order of the answers.

            // Go through all answers of the slot question.
            foreach ($order as $key => $notused) {
                $hotspot = $answerspots['a-' . $questioncounter . '-' . $key];

                if ($choicesdata[$slot][$key]->value == 1) {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\"" .
                         " id=\"a-$slot-$key\" style=\"position:absolute; top:".$hotspot->y."px; left:" .
                         $hotspot->x."px; cursor:pointer;\" onClick=\"set_item(this, $slot, $key)\">";
                }
            }
            $questioncounter++;
        }
    }
}
