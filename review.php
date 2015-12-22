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
 * Result review page
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');
require_once('lib.php');

$resultid = required_param('resultid', PARAM_INT);    // A particular result ID for review.
$page = optional_param('page', 0, PARAM_INT);         // The required page.
$showall = optional_param('showall', 0, PARAM_BOOL);  // Not used at the moment.

if (!$result = $DB->get_record("offlinequiz_results", array("id" => $resultid))) {
    print_error("No such result ID exists");
}
if (!$offlinequiz = $DB->get_record("offlinequiz", array("id" => $result->offlinequizid))) {
    print_error("The offlinequiz with id $result->offlinequiz belonging to result $result is missing");
}

$offlinequiz->optionflags = 0;
$offlinequiz->penaltyscheme = 0;

if (!$group = $DB->get_record("offlinequiz_groups", array('id' => $result->offlinegroupid))) {
    print_error("The offlinequiz group belonging to result $result is miss1ing");
}
if (!$course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
    print_error("The course with id $offlinequiz->course that the offlinequiz with id $offlinequiz->id belongs to is missing");
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error("The course module for the offlinequiz with id $offlinequiz->id is missing");
}

$grade = offlinequiz_rescale_grade($result->sumgrades, $offlinequiz, $group);

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);

$isteacher = has_capability('mod/offlinequiz:viewreports', $context);
$options = offlinequiz_get_review_options($offlinequiz, $result, $context);

if (!$isteacher) {
    if (!$result->timefinish) {
        redirect('view.php?q='.$offlinequiz->id);
    }
    // If not even responses or scanner feedback are to be shown in review then we
    // don't allow any review.
    if ($options->attempt == question_display_options::HIDDEN and
            $options->marks < question_display_options::MAX_ONLY and
            $options->sheetfeedback == question_display_options::HIDDEN and
            $options->gradedsheetfeedback == question_display_options::HIDDEN) {
        redirect('view.php?q=' . $offlinequiz->id);
    }

    if (!offlinequiz_results_open($offlinequiz)) {
        redirect('view.php?q=' . $offlinequiz->id, get_string("noreview", "offlinequiz"));
    }
    if ($result->userid != $USER->id) {
        print_error("This is not your result!", 'view.php?q=' . $offlinequiz->id);
    }
}

$strscore  = get_string("marks", "offlinequiz");
$strgrade  = get_string("grade");
$letterstr = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ';

// Setup the page and print the page header.
$url = new moodle_url('/mod/offlinequiz/review.php', array('resultid' => $resultid, 'page' => $page));
$PAGE->set_url($url);
$PAGE->set_title(format_string($offlinequiz->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
echo $OUTPUT->header();

// Print heading and tabs if this is part of a preview.
if ($isteacher) {
    if ($result->userid == $USER->id) { // This is the report on a preview.
        $currenttab = 'preview';
    } else {
        $currenttab = 'reports';
        $mode = 'review';
    }
    include('tabs.php');
}

echo $OUTPUT->heading(format_string($offlinequiz->name));

// Load the module's global config.
offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

echo $OUTPUT->heading(get_string('reviewofresult', 'offlinequiz'));

// --------------------------------------
// Print info table with user details.
// --------------------------------------
$timelimit = 0;

$table = new html_table();
$table->attributes['class'] = 'generaltable offlinequizreviewsummary';
$table->align  = array("right", "left");
if ($result->userid <> $USER->id) {
    $student = $DB->get_record('user', array('id' => $result->userid));
    $picture = $OUTPUT->user_picture($student);
    $table->data[] = array($picture, '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $student->id .
            '&amp;course=' . $course->id . '">' . fullname($student, true) . ' ('.$student->username.')</a>');
}

$table->data[] = array(get_string('group') . ':', $letterstr[$group->number]);
if (!empty($offlinequiz->time)) {
    $table->data[] = array(get_string('quizdate', 'offlinequiz').':', userdate($offlinequiz->time));
}

// If the student is allowed to see his score.
if ($options->marks != question_display_options::HIDDEN) {
    if ($offlinequiz->grade and $group->sumgrades) {

        $resultmark = format_float($result->sumgrades, $offlinequiz->decimalpoints);
        $maxmark = format_float($group->sumgrades, $offlinequiz->decimalpoints);
        $percentage = format_float(($result->sumgrades * 100.0 / $group->sumgrades), $offlinequiz->decimalpoints);
        $table->data[] = array($strscore . ':', $resultmark . '/' . $maxmark . ' (' . $percentage . '%)');

        $a = new stdClass;
        $a->grade = format_float(preg_replace('/,/i', '.', $grade), $offlinequiz->decimalpoints);
        $a->maxgrade = format_float($offlinequiz->grade, $offlinequiz->decimalpoints);
        $table->data[] = array($strgrade . ':', get_string('outof', 'offlinequiz', $a));
    }
}

echo html_writer::table($table);

// --------------------------------------
// Print buttons to the scanned pages.
// --------------------------------------
if ($isteacher or ($options->sheetfeedback == question_display_options::VISIBLE) or
        ($options->gradedsheetfeedback == question_display_options::VISIBLE)) {
    if ($result->userid == $USER->id) {
        $user = $USER;
    } else {
        $user = $DB->get_record('user', array('id' => $result->userid));
    }
    $userkey = $user->{$offlinequizconfig->ID_field};

    $scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('resultid' => $result->id), 'pagenumber ASC');

    // Options for the popup_action.
    $popupoptions = array();
    $popupoptions['height'] = 1200;
    $popupoptions['width'] = 1170;
    $popupoptions['resizable'] = false;

    // If the teacher saved some warning images, display them now.
    if (!$isteacher) {
        $found = false;
        foreach ($scannedpages as $scannedpage) {
            if ($scannedpage->warningfilename) {
                if (!$found) {
                    echo "<br />\n";
                    echo $OUTPUT->box_start('center');
                    echo '<div align="center">';
                    echo "<div class=\"noticebox\">";
                    echo $OUTPUT->notification(get_string('neededcorrection', 'offlinequiz'), 'notifyproblem');
                    echo "</div>";
                    $found = true;
                }
                $fs = get_file_storage();
                $imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/',
                        $scannedpage->warningfilename);
                echo '<br/>&nbsp;<br/><img name="formimage" src="' . $CFG->wwwroot .
                 "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" . $imagefile->get_filename() .
                 '" border="1" width="760" />';
            }
        }
        if ($found) {
            echo "</div><br/>";
            echo $OUTPUT->box_end();
        }
    }

    $i = 1;
    foreach ($scannedpages as $scannedpage) {
        if ($scannedpage->status == 'ok' || $scannedpage->status == 'submitted') {
            echo '<div class="linkbox">';
            if ($isteacher) {
                $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/correct.php',
                        array('pageid' => $scannedpage->id, 'overwrite' => 1));
                echo $OUTPUT->action_link($url,  get_string('editscannedform', 'offlinequiz') .
                        ' (' . get_string('page').' '.$i++ . ')',
                        new popup_action('click', $url, 'correct' . $scannedpage->id, $popupoptions));
                echo '<br/>';
                echo '<br/>';
            } else {
                $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/image.php',
                        array('resultid' => $result->id, 'pageid' => $scannedpage->id));
                echo $OUTPUT->action_link($url,  get_string('linktoscannedform', 'offlinequiz') .
                        ' (' . get_string('page').' '.$i++ . ')',
                        new popup_action('click', $url, 'image' . $scannedpage->id, $popupoptions));
            }
            echo "</div>";
        }
    }
}

if (!$isteacher and $options->attempt == question_display_options::HIDDEN) {
    echo $OUTPUT->footer();
    die();
}

// Print copyright warning.
if (!$isteacher) {
    echo "<br />\n<div class=\"noticebox warning\">";
    if ($offlinequizconfig->showcopyright) {
        echo $OUTPUT->notification(get_string('copyright', 'offlinequiz'));
    }
    echo "</div>";
}

if ($options->attempt == question_display_options::VISIBLE || $isteacher) {
    // Load the questions needed by page.
    if (!$quba = question_engine::load_questions_usage_by_activity($result->usageid)) {
        print_error('Could not load question usage');
    }

    $slots = $quba->get_slots();

    foreach ($slots as $id => $slot) {
        $questionnumber = $slot; // Descriptions make this more complex.
        echo $quba->render_question($slot, $options, $questionnumber);
    }
}

// Trigger an event for this review.
$params = array(
    'objectid' => $result->id,
    'relateduserid' => $result->userid,
    'courseid' => $course->id,
    'context' => context_module::instance($cm->id),
    'other' => array(
        'offlinequizid' => $offlinequiz->id
    )
);
$event = \mod_offlinequiz\event\attempt_reviewed::create($params);
$event->add_record_snapshot('offlinequiz_results', $result);
$event->trigger();

echo $OUTPUT->footer();
