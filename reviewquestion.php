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
 * This page prints a review of a particular question result.
 * This page is expected to only be used in a popup window.
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

$resultid = required_param('resultid', PARAM_INT); // Result id.
$slot = required_param('slot', PARAM_INT);         // Question number in usage.
$seq = optional_param('step', null, PARAM_INT);    // Sequence number.

$baseurl = new moodle_url('/mod/offlinequiz/reviewquestion.php',
        array('resultid' => $resultid, 'slot' => $slot));
$currenturl = new moodle_url($baseurl);
if ($seq !== 0) {
    $currenturl->param('step', $seq);
}
$PAGE->set_url($currenturl);

if (!$result = $DB->get_record('offlinequiz_results', array('id' => $resultid))) {
    print_error('result does not exist');
}
if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $result->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' .
    $COURSE->id, $scannedpage->offlinequizid);
}
if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' .
    $COURSE->id, array('course' => $offlinequiz->course,
         'offlinequiz' => $offlinequiz->id));
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error('cmmissing', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' .
    $COURSE->id, $offlinequiz->id);
}
if (!$groups = $DB->get_records('offlinequiz_groups',
        array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
    print_error('nogroups', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' .
    $COURSE->id, $scannedpage->offlinequizid);
}

// Check login.
require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/offlinequiz:attempt', $context);

if (!has_capability('mod/offlinequiz:viewreports', $context) && ($USER->id != $result->userid)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('cannotreview', 'offlinequiz'));
    echo $OUTPUT->close_window_button();
    echo $OUTPUT->footer();
    die();
}

if ($result->status != 'complete') {
    print_error('resultnotcomplete', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $offlinequiz->id);
}

$options = offlinequiz_get_review_options($offlinequiz, $result, $context);

$PAGE->set_pagelayout('popup');

$quba = question_engine::load_questions_usage_by_activity($result->usageid);

echo $OUTPUT->header();
echo $quba->render_question_at_step($slot, $seq, $options);
echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();
