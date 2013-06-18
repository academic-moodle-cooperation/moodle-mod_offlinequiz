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
 * The manual correction interface for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/default.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/scanner.php');

$scannedpageid = optional_param('pageid', 0, PARAM_INT);
$overwrite     = optional_param('overwrite', 0, PARAM_INT);
$action        = optional_param('action', 'load', PARAM_TEXT);

if (!$scannedpage = $DB->get_record('offlinequiz_scanned_pages', array('id' => $scannedpageid))) {
    print_error('noscannedpage', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpageid);
}

if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $scannedpage->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, array('course' => $offlinequiz->course,
         'offlinequiz' => $offlinequiz->id));
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error('cmmissing', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $offlinequiz->id);
}
if (!$groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
    print_error('nogroups', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
require_capability('mod/offlinequiz:viewreports', $context);

$url = new moodle_url('/mod/offlinequiz/correct.php', array('pageid' => $scannedpage->id));
$PAGE->set_url($url);
// $PAGE->layout_options = array('nonavbar' => true, 'nofooter' => true, 'noblocks' => true, 'nologininfo' => true, 'nocustommenu' => true);
$PAGE->set_pagelayout('report');

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

list($maxquestions, $maxanswers, $formtype, $questionsperpage) =  offlinequiz_get_question_numbers($offlinequiz, $groups);

// Get the corners either from the request parameters of from the offlinequiz_page_corners table.
$corners = array();
$nodbcorners = false;

if ($action == 'rotate') {
    $corners[0] = new oq_point(853 - required_param('c-3-x', PARAM_INT), 1208 - required_param('c-3-y', PARAM_INT));
    $corners[1] = new oq_point(853 - required_param('c-2-x', PARAM_INT), 1208 - required_param('c-2-y', PARAM_INT));
    $corners[2] = new oq_point(853 - required_param('c-1-x', PARAM_INT), 1208 - required_param('c-1-y', PARAM_INT));
    $corners[3] = new oq_point(853 - required_param('c-0-x', PARAM_INT), 1208 - required_param('c-0-y', PARAM_INT));
    offlinequiz_save_page_corners($scannedpage, $corners);
} else if ($action == 'readjust') {
    $corners[0] = new oq_point(required_param('c-0-x', PARAM_INT) + 7, required_param('c-0-y', PARAM_INT) + 7);
    $corners[1] = new oq_point(required_param('c-1-x', PARAM_INT) + 7, required_param('c-1-y', PARAM_INT) + 7);
    $corners[2] = new oq_point(required_param('c-2-x', PARAM_INT) + 7, required_param('c-2-y', PARAM_INT) + 7);
    $corners[3] = new oq_point(required_param('c-3-x', PARAM_INT) + 7, required_param('c-3-y', PARAM_INT) + 7);
    offlinequiz_save_page_corners($scannedpage, $corners);
} else if ($dbcorners = $DB->get_records('offlinequiz_page_corners', array('scannedpageid' => $scannedpage->id), 'position')) {
    foreach ($dbcorners as $corner) {
        $corners[] = new oq_point($corner->x, $corner->y);
    }
} else {
    // Define some default corners.
    $corners[0] = new oq_point(55, 39);
    $corners[1] = new oq_point(805, 49);
    $corners[2] = new oq_point(44, 1160);
    $corners[3] = new oq_point(805, 1160);
    $nodbcorners = true;
}

// Initialize a page scanner.
$scanner = new offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);

// Load the stored picture file.
$sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);

// Make a first check.
if (!$scanner->check_deleted()) {
    $scannedpage->status = 'error';
    $scannedpage->error = 'notadjusted';
}

// =======================================
// Step 1. Get the data from the stored scanned page.
// =======================================
if ($action == 'load') {
    $filename = $scannedpage->filename;
    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);
} else {
    if ($submitfilename = optional_param('filename', '', PARAM_RAW)) {
        $scannedpage->filename = $submitfilename;
        $filename = $submitfilename;
    }
}

// If we still don't have corners, get them from the scanner.
if ($nodbcorners) {
    $corners = $scanner->get_corners();
    offlinequiz_save_page_corners($scannedpage, $corners);
}

// =======================================
// Step 2. The user might have submitted data.
// =======================================
// =============================================
//   Action checkuser.
// =============================================
if ($action == 'checkuser') {

    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    // Maybe old errors have been fixed.
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    $groupnumber = required_param('groupnumber', PARAM_TEXT);
    $groupnumber = intval($groupnumber);
    //  if (!property_exists($scannedpage, 'groupnumber') || $scannedpage->groupnumber == 0) {
    $scanner->set_group($groupnumber);
    $scannedpage->groupnumber = $groupnumber;
    // }

    $usernumber = required_param('usernumber', PARAM_TEXT);

    $xes = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    // if we have only X's then we ignore the input
    $userkey = $offlinequizconfig->ID_prefix . $usernumber . $offlinequizconfig->ID_postfix;
    if ($usernumber == substr($xes, 0, $offlinequizconfig->ID_digits)) {
        $scannedpage->userkey = null;
        $DB->set_field('offlinequiz_scanned_pages', 'userkey', null, array('id' => $scannedpage->id));
    } else {
        $scannedpage->userkey = $userkey;
    }

    /* $pagenumber = optional_param('page', 0, PARAM_INT); */
    /* $scannedpage->pagenumber = $pagenumber; */

    // Now we check the scanned page with potentially updated information.
    //  $scannedpage = offlinequiz_check_for_changed_groupnumber($offlinequiz, $scanner, $scannedpage, $coursecontext, $questionsperpage, $offlinequizconfig);

    $scannedpage = offlinequiz_check_for_changed_user($offlinequiz, $scanner, $scannedpage, $coursecontext, $questionsperpage, $offlinequizconfig);
    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
            // Already process the answers but don't submit them.
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $questionsperpage, $coursecontext, false);

            // Compare the old and the new result wrt. the choices.
            $scannedpage = offlinequiz_check_different_result($scannedpage);
        }
    }


    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);
    $scanner->set_page($scannedpage->pagenumber);

    //  $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    // =============================================
    // Action update.
    // =============================================
} else if ($action == 'update') {

    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    // Maybe old errors have been fixed.
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    $itemdata = required_param('item', PARAM_RAW);
    $groupnumber = required_param('groupnumber', PARAM_TEXT);
    $scannedpage->groupnumber = $groupnumber;
    $scanner->set_group($groupnumber);

    $usernumber = required_param('usernumber', PARAM_TEXT);
    // If we have only X's then we ignore the input.
    if (!empty($usernumber)) {
        $userkey = $offlinequizconfig->ID_prefix . $usernumber . $offlinequizconfig->ID_postfix;
        $scannedpage->userkey = $userkey;
    }

    if ($overwrite) {
        // We want to overwrite an old result, so we have to create a new one.
        // Don't delete the choices stored in the DB.
        // $DB->delete_records('offlinequiz_choices', array('scannedpageid' => $scannedpage->id));

        // Delete the old result and create a new one.
        if ($scannedpage->resultid && $oldresult = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid))) {
            $oldresultid = $scannedpage->resultid;
            $scannedpage->resultid = 0;
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));
        }

        // This should create a new result and set the resultid field of the scannedpage.
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->resultid) {
            $DB->delete_records('offlinequiz_results', array('id' => $oldresultid));
        } else {
            $scannedpage->resultid = $oldresultid;
            $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
        }
        // $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $questionsperpage, $coursecontext);

        // TODO we have to figure out what to do with the other pages in case of a multipage test.
    }

    //  $scannedpage = offlinequiz_check_for_changed_groupnumber($offlinequiz, $scanner, $scannedpage, $coursecontext, $questionsperpage, $offlinequizconfig);
    //  $scannedpage = offlinequiz_check_for_changed_user($offlinequiz, $scanner, $scannedpage, $coursecontext, $questionsperpage, $offlinequizconfig);

    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
            // Already process the answers but don't submit them.
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $questionsperpage, $coursecontext, false);

            // Compare the old and the new result wrt. the choices.
            $scannedpage = offlinequiz_check_different_result($scannedpage);
        }
    }

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    // The updated item information (crosses), will be processed later.
    $rawitemdata = required_param('item', PARAM_RAW);

    // =============================================
    // Action rotate.
    // =============================================
} else if ($action == 'rotate') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    if ($newfile = $scanner->rotate_180()) {

        // Maybe old errors have been fixed.
        $scannedpage->status = 'ok';
        $scannedpage->error = '';
        $scannedpage->userkey = null;
        $scannedpage->pagenumber = null;
        $scannedpage->groupnumber = null;

        $scannedpage->filename = $newfile->get_filename();
        $filename = $newfile->get_filename();

        // Create a completely new scanner.
        $scanner = new offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);

        $sheetloaded = $scanner->load_stored_image($scannedpage->filename, array());

        if ($sheetloaded && !$overwrite) {
            $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

            if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
                // Already process the answers but don't submit them.
                $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $job->importuserid, $questionsperpage, $coursecontext, false);

                // Compare the old and the new result wrt. the choices.
                $scannedpage = offlinequiz_check_different_result($scannedpage);
            }
        }

        $userkey = $scannedpage->userkey;
        $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
        $groupnumber = intval($scannedpage->groupnumber);
        $pagenumber = intval($scannedpage->pagenumber);

        $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
    }

    // =============================================
    // Action setpage.
    // =============================================
} else if ($action == 'setpage') {

    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
        onClick=\"self.close(); return false;\"><br />";
        die;
    }

    // If the page number was invalid and the user selected a pagenumber, take that and hope that page is OK now.
    $scannedpage->pagenumber = optional_param('page', 0, PARAM_INT);
    $pagenumber = $scannedpage->pagenumber;
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
            // Already process the answers but don't submit them.
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $job->importuserid, $questionsperpage, $coursecontext, false);

            // Compare the old and the new result wrt. the choices.
            $scannedpage = offlinequiz_check_different_result($scannedpage);
        }
    }

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    //  $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    // =============================================
    // Action readjust.
    // =============================================

} else if ($action == 'readjust') {

    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    $scannedpage->status = 'ok';
    $scannedpage->error = '';
    // We reset all user information s.t. it is retrieved again from the scanner.
    $scannedpage->userkey = null;
    $scannedpage->pagenumber = null;
    $scannedpage->groupnumber = null;
    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);
    }

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
}


// If we correct an OK page or a suspended page we can first process it and store the choices in the database.
if ($action == 'load' ) {
    $oldchoices = $DB->count_records('offlinequiz_choices', array('scannedpageid' => $scannedpage->id));
}

// If we have an OK page and the action was checkuser, setpage, or rotate we should process the page.
if (($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') && ($action == 'readjust' ||
        $action == 'checkuser' || $action == 'setpage' || $action == 'rotate' || ($action == 'load' && !$oldchoices))) {
    // Process the scanned page and write the answers in the offlinequiz_choices table.
    $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $questionsperpage, $coursecontext);
}

// Load the choices made before from the database. This might be empty.
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


// Retrieve the offlinequiz group.
if (is_numeric($groupnumber) && $groupnumber > 0 && $groupnumber <= $offlinequiz->numgroups) {
    if (!$group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'number' => $groupnumber))) {
        print_error('nogroup', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $offlinequiz->id);
    }
} else {
    $group = null;
}
// Check whether the user exists in Moodle.
$user = $DB->get_record('user', array($offlinequizconfig->ID_field => $userkey));


// Check whether the user is enrolled in the current course.
$notincourse = false;
$coursestudents = get_role_users(5, $coursecontext);
if ($user && empty($coursestudents[$user->id])) {
    $scannedpage->status = 'error';
    $scannedpage->error = 'usernotincourse';
}

// Retrieve the result from the database.
$result = null;

if ($group && $user && $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid))) {
    $quba = question_engine::load_questions_usage_by_activity($result->usageid);
    $slots = $quba->get_slots();

    // Determine the slice of slots we are interested in.
    // We start at the top of the page (e.g. 0, 96, etc).
    $startindex = min(($pagenumber - 1) * $questionsperpage, count($slots));
    // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
    $endindex = min( $pagenumber * $questionsperpage, count($slots) );

    if ($action == 'update') {
        echo $OUTPUT->heading(get_string('resultimport', 'offlinequiz'));

        $changed = array();
        $questioncounter = 0;
        $unknown = false;
        // If we have the result slots, we update the choicesdata with the data sent by the user.
        if (!empty($rawitemdata) && !empty($slots)) {
            for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {
                $slot = $slots[$slotindex];
                $slotquestion = $quba->get_question($slot);
                $attempt = $quba->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order of the answers.
                foreach ($order as $key => $notused) {
                    if ($rawitemdata[$slot . '-' . $key] == -1) {
                        $unknown = true;
                    }
                    if ($choicesdata[$slot][$key]->value != $rawitemdata[$slot . '-' . $key]) {
                        $changed[] = array('question' => $questioncounter,
                                'answer' => $key);
                    }
                    if (is_array($choicesdata[$slot]) && is_object($choicesdata[$slot][$key])) {
                        // Remember the changed fields in case we want to display them to the user.
                        $choicesdata[$slot][$key]->value = $rawitemdata[$slot . '-' . $key];
                        $DB->set_field('offlinequiz_choices', 'value', $choicesdata[$slot][$key]->value,
                                array('id' => $choicesdata[$slot][$key]->id));
                    } else {
                        $choice = new stdClass();
                        $choice->scannedpageid = $scannedpage->id;
                        $choice->slotnumber = $slot;
                        $choice->choicenumber = $key;
                        $choice->value = $rawitemdata[$slot . '-' . $key];
                        $choice->id = $DB->insert_record('offlinequiz_choices', $choice);

                        if (!isset($choicesdata[$choice->slotnumber]) || !is_array($choicesdata[$choice->slotnumber])) {
                            $choicesdata[$choice->slotnumber] = array();
                        }
                        $choicesdata[$slot][$key] = $choice;
                    }
                }
                $questioncounter++;
            }
        }
        if ($show = optional_param('show', false, PARAM_BOOL)) {

            $scanner->create_warning_image($scanner->get_usernumber(),
                    substr($user->{$offlinequizconfig->ID_field}, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits),
                    $scanner->calibrate_and_get_group(),
                    $group->number,
                    $changed);

            $file_record = array(
                    'contextid' => $context->id,
                    'component' => 'mod_offlinequiz',
                    'filearea'  => 'imagefiles',
                    'itemid'    => 0,
                    'filepath'  => '/',
                    'filename'  => $scanner->filename . '_warning');

            // Create a unique temp dir.
            $dirname = "{$CFG->tempdir}/offlinequiz/import/$unique";
            check_dir_exists($dirname, true, true);

            $warningpathname = $dirname . '/' . $file_record['filename'];

            imagepng($scanner->image, $warningpathname);
            $newfile = $scanner->save_image($file_record, $warningpathname);
            $scannedpage->warningfilename = $newfile->get_filename();

            $DB->set_field('offlinequiz_scanned_pages', 'warningfilename', $newfile->get_filename(), array('id' => $scannedpage->id));

            unlink($warningpathname);
            remove_dir($dirname);
        }

        if (!$unknown) {
            $scannedpage = offlinequiz_submit_scanned_page($offlinequiz, $scannedpage, $choicesdata, $startindex, $endindex);

            if ($scannedpage->status == 'submitted') {
                $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
                if (offlinequiz_check_result_completed($offlinequiz, $group, $result)) {
                    echo $OUTPUT->notification(get_string('userimported', 'offlinequiz', fullname($user) . " (" .
                            $user->{$offlinequizconfig->ID_field}.")"), 'notifysuccess');
                } else {
                    echo $OUTPUT->notification(get_string('userpageimported', 'offlinequiz', fullname($user) . " (" .
                            $user->{$offlinequizconfig->ID_field}.")"), 'notifysuccess');
                }
                if ($overwrite) {
                    echo "<input type=\"button\" value=\"".get_string('closewindow')."\" onClick=\"window.opener.location.replace('" .
                            $CFG->wwwroot . '/mod/offlinequiz/review.php?q=' . $offlinequiz->id . '&resultid=' .
                            $scannedpage->resultid . "'); window.close(); return false;\">";
                } else {
                    echo "<input type=\"button\" value=\"".get_string('closewindow')."\" onClick=\"window.opener.location.reload(1);
                    self.close(); return false;\">";
                }
                return;
            }
        } else {
            // We found some insecure markings, which is weird, because they should have been found earlier...
            $scannedpage->status = 'error';
            $scannedpage->error = 'insecuremarkings';
            $scannedpage->time = time();
            $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
        }
    }
}

$itemdata = array();
// Itemdata contains the values of the HTML inputs indexed by slotnumber and choicenumber.
// Itemdata is always set, even if we don't have any choices data in the DB.
if (!empty($slots)) {

    // We start at the top of the page (e.g. 0, 96, etc).
    $startindex = min( ($pagenumber - 1) * $questionsperpage, count($slots));
    // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
    $endindex = min( $pagenumber * $questionsperpage, count($slots) );
    for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {
        $slot = $slots[$slotindex];
        if (!isset($itemdata[$slot]) || !is_array($itemdata[$slot])) {
            $itemdata[$slot] = array();
        }
        $slotquestion = $quba->get_question($slot);
        $attempt = $quba->get_question_attempt($slot);
        $order = $slotquestion->get_order($attempt);  // Order of the answers.
        foreach ($order as $key => $notused) {
            if (isset($choicesdata[$slot]) and is_object($choicesdata[$slot][$key])) {
                // If we have choices in the DB, take them.
                $itemdata[$slot][$key] = $choicesdata[$slot][$key]->value;
            } else {
                // Otherwise the choice is undetermined.
                $itemdata[$slot][$key] = -1;
            }
        }
    }
}

if ($group && $pagenumber > 0 and $pagenumber <= $group->numberofpages) {
    $scanner->set_page($pagenumber);
}

// =======================================================================
// OUTPUT THE PAGE HTML.
// =======================================================================
// echo $OUTPUT->header('','','','','',false,'','',false,'');
echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo "</style>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";

echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/jquery-1.4.3.min.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.core.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.widget.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.mouse.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.draggable.js');

$javascript = "<script language=\"JavaScript\">
function checkinput() {
 // Get all item elements. We have jquery!
 items = $('input[name^=\"item\"]');
 console.log(items);
 for (i=0; i < items.length; i++) {
  if (items[i].value == '-1') {
    parts = items[i].name.split('[');
    numbers = parts[1].split('-');
    alert(\"" . get_string('insecuremarkingsforquestion', 'offlinequiz') . " \" + numbers[0]);
    return false;
  }
 }
 if (document.forms.cform.usernumber.value.indexOf('X') >= 0) {
  alert(\"" . get_string('insecuremarkings', 'offlinequiz') . ": \"+document.forms.cform.usernumber.value+\" (userid)\");
  return false;
 }
 if (document.forms.cform.groupnumber.value.indexOf('X') >= 0) {
  alert(\"" .  get_string('insecuremarkings', 'offlinequiz') . ": \"+document.forms.cform.groupnumber.value+\" (group)\");
  return false;
 }
}

function set_userid(image, x, y) {
  for (i=0; i<=9; i++) {
    key = 'u'+x+i;
    document.images[key].src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"
  }
  image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"
  document.forms.cform.usernumber.value = document.forms.cform.usernumber.value.substr(0,x) + y + document.forms.cform.usernumber.value.substr(x+1);
}

function set_group(image, x) {
 for (i=0; i<=5; i++) {
  document.images['g'+i].src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"
 }
 image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"
 document.forms.cform.groupnumber.value = x+1;
 document.forms.cform.elements['action'].value='checkuser';
 document.forms.cform.submit();
}

function set_item(image, x, y) {
  key = 'item['+x+'-'+y+']';
  if (document.forms.cform.elements[key].value == '1') {
    image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"
    document.forms.cform.elements[key].value = 0;
  } else {
    image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"
    document.forms.cform.elements[key].value = 1;
  }
}

function submitReadjust() {
 changed = false;
 for (i=0; i<=3; i++) {
   corner = document.getElementById('c-'+i);
   document.forms.cform.elements['c-'+i+'-x'].value = corner.style.left.replace('px','');
   document.forms.cform.elements['c-'+i+'-y'].value = corner.style.top.replace('px','');
   if (document.forms.cform.elements['c-'+i+'-x'].value != document.forms.cform.elements['c-old-'+i+'-x'].value) {
     changed = true;
   }
   if (document.forms.cform.elements['c-'+i+'-y'].value != document.forms.cform.elements['c-old-'+i+'-y'].value) {
     changed = true;
   }
 }
 if (!changed) {
   alert('" . get_string('movecorners', 'offlinequiz') . "')
 } else {
  document.forms.cform.elements['action'].value='readjust'
  document.forms.cform.submit();
 }
}

function submitPage() {
  for (i=0; i<=3; i++) {
    corner = document.getElementById('c-'+i);
    document.forms.cform.elements['c-'+i+'-x'].value = corner.style.left.replace('px','');
    document.forms.cform.elements['c-'+i+'-y'].value = corner.style.top.replace('px','');
  }
  document.forms.cform.elements['action'].value='setpage'
  document.forms.cform.submit();
}

function submitCheckuser() {
  for (i=0; i<=3; i++) {
    document.forms.cform.elements['c-'+i+'-x'].value = document.forms.cform.elements['c-old-'+i+'-x'].value;  // Reset possible readjustment.
    document.forms.cform.elements['c-'+i+'-y'].value = document.forms.cform.elements['c-old-'+i+'-y'].value;
  }
  document.forms.cform.elements['action'].value='checkuser'
  document.forms.cform.submit();
}

function submitRotated() {
  document.forms.cform.elements['action'].value='rotate'
  document.forms.cform.submit();
}

</script>";

echo $javascript;

$fs = get_file_storage();
$imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename);
// ==================================================
// Print image of the form sheet.
echo '<img name="formimage" src="' . $CFG->wwwroot . "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" .
   $imagefile->get_filename() .'" border="1" width="' . OQ_IMAGE_WIDTH . '" style="position:absolute; top:0px; left:0px; display: block;">';

// ==================================================
// Print user name, number, and the page number.
echo "<div style=\"position:absolute; top: 20px; left: 130px\">\n";

if ($user && $user->firstname != '' and $user->lastname != '') {
    echo "<strong style=\"color: green\">" . fullname($user) . " (" . $userkey . ")</strong>\n";
} else if (!empty($userkey)) {
    echo "<strong style=\"color: red\">" . get_string('userdoesnotexist', 'offlinequiz', $userkey) . "</strong>\n";
}

if (!empty($pagenumber)) {
    echo  "&nbsp;&nbsp;" . get_string('page') . ": " . $pagenumber;
    if ($group && $group->numberofpages) {
        echo "/" . $group->numberofpages . "\n";
    }
}
if ($scannedpage->status == 'error') {
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong style=\"color: red\">(" . get_string('error' . $scannedpage->error, 'offlinequiz_rimport') . ")</strong>\n";
}
echo "</div>\n";


// ==================================================
// Print action buttons and form.
echo "<form method=\"post\" action=\"correct.php?pageid=$scannedpage->id\" id=\"cform\">\n";

echo "<div style=\"position:absolute; top:10px; left:" . (OQ_IMAGE_WIDTH + 10) . "px; width:280px\">\n";
echo "<div style=\"margin:4px;margin-bottom:8px\"><u>";
print_string('actions');
echo ":</u></div>\n";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"window.opener.location.reload(1); self.close(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('rotate', 'offlinequiz')."\" name=\"submitbutton5\"
onClick=\"submitRotated(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('readjust', 'offlinequiz')."\" name=\"submitbutton3\"
onClick=\"submitReadjust(); return false;\"><br />";

if ($scannedpage->status == 'ok' ||
        $scannedpage->status == 'submitted' ||
        $scannedpage->status == 'suspended' ||
        $scannedpage->error == 'insecuremarkings' ||
        $scannedpage->error == 'nonexistinguser' ||
        $scannedpage->error == 'usernotincourse' ||
        $scannedpage->error == 'resultexists' ||
        $scannedpage->error == 'doublepage' ||
                $scannedpage->error == 'differentresultexists' ||
        $scannedpage->error == 'grouperror') {

    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('checkuserid', 'offlinequiz').
    "\" name=\"submitbutton4\" onClick=\"submitCheckuser(); return false;\"><br />";

    // Show enabled save button if the error state allows it.
    if ($scannedpage->error != 'doublepage' &&
            $scannedpage->error != 'nonexistinguser' &&
            $scannedpage->error != 'usernotincourse' &&
            $scannedpage->error != 'grouperror') {
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('saveandshow', 'offlinequiz').
        "\" name=\"submitbutton2\" onClick=\"document.forms.cform.show.value=1; return checkinput()\"><br />";
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz')."\" name=\"submitbutton1\" onClick=\"return checkinput()\">";
    } else {
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('saveandshow', 'offlinequiz')."\" name=\"submitbutton2\" disabled=\"disabled\"><br />";
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz')."\" name=\"submitbutton1\" disabled=\"disabled\">";
    }
} else {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('checkuserid', 'offlinequiz')."\" name=\"submitbutton4\" disabled=\"disabled\"><br />";
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('saveandshow', 'offlinequiz')."\" name=\"submitbutton2\" disabled=\"disabled\"><br />";
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz')."\" name=\"submitbutton1\" disabled=\"disabled\">";
}
echo "</div>\n";

echo "<input type=\"hidden\" name=\"usernumber\" value=\"$usernumber\">\n";
echo "<input type=\"hidden\" name=\"groupnumber\" value=\"$groupnumber\">\n";
echo "<input type=\"hidden\" name=\"overwrite\" value=\"$overwrite\">\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"". sesskey() . "\">\n";
echo "<input type=\"hidden\" name=\"filename\" value=\"$filename\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
echo "<input type=\"hidden\" name=\"show\" value=\"0\">\n";

foreach ($itemdata as $dkey => $items) {
    foreach ($items as $key => $item) {
        echo "<input type=\"hidden\" name=\"item[$dkey-$key]\" value=\"$item\">\n";
    }
}

foreach ($corners as $key => $hotspot) {
    echo "<input type=\"hidden\" name=\"c-old-$key-x\" value=\"".($hotspot->x - 7)."\">\n";
    echo "<input type=\"hidden\" name=\"c-old-$key-y\" value=\"".($hotspot->y - 7)."\">\n";
    echo "<input type=\"hidden\" name=\"c-$key-x\" value=\"".($hotspot->x - 7)."\">\n";
    echo "<input type=\"hidden\" name=\"c-$key-y\" value=\"".($hotspot->y - 7)."\">\n";
}

foreach ($corners as $key => $hotspot) {
    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/corner.gif\" border=\"0\" name=\"c-$key\" id=\"c-$key\"
    style=\"position:absolute; top:".($hotspot->y - 7)."px; left:".($hotspot->x - 7)."px; cursor:move;\">";
}

// ==================================================
// Print select box for pagenumber.
if ($scannedpage->error == 'invalidpagenumber') {
    if (empty($pagenumber)) {
        echo "<div style=\"position:absolute; top:250px; left:" . (OQ_IMAGE_WIDTH + 10) .
        "px; width:240px; background-color:#eee; padding:5px; border:2px red solid; text-align: center\">";
        print_string('errorinvalidpagenumber', 'offlinequiz_rimport');
        echo "<br />";
        print_string('selectpage', 'offlinequiz');
        echo "<br />";
        echo "<select name=\"page\" onChange=\"submitPage();\">\n";
    } else {
        echo "<div style=\"position:absolute; top:270px; left:" . (OQ_IMAGE_WIDTH + 10) . "px; width:240px;\">";
        echo "<select name=\"page\">\n";
    }
    echo '<option value="0">'.get_string('choose').'...</option>';
    $maxpage = 10;
    if ($group) {
        $maxpage = $group->numberofpages;
    }
    for ($i = 1; $i <= $maxpage; $i++) {
        echo '<option value="'.$i.'"';
        if ($pagenumber == $i) {
            echo ' selected="selected"';
        }
        echo '>'.get_string('page').' '.$i.'</option>';
    }
    echo "</select>\n";
    echo "</div>";
} else {
    echo "<input type=\"hidden\" name=\"page\" value=\"$pagenumber\">\n";
}
echo "</form>\n";


// ==================================================
// Print hotspots.
if ($sheetloaded) {
    // Print hotspots for userkey.
    $userkeyhotspots = $scanner->export_hotspots_userid(OQ_IMAGE_WIDTH);
    foreach ($userkeyhotspots as $key => $hotspot) {
        $x = substr($key, 1, 1);
        $y = substr($key, 2, 1);
        if (substr($usernumber, $x, 1) == 'X') {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" border=\"0\" id=\"u$x$y\" title=\"" . $y .
            "\" style=\"position:absolute; top:".$hotspot->y."px; left:".
            $hotspot->x."px; cursor:pointer\" onClick=\"set_userid(this, $x, $y)\">";
        } else if (!empty($usernumber) and substr($usernumber, $x, 1) == $y) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\" id=\"u$x$y\" title=\"" . $y .
            "\" style=\"position:absolute; top:".$hotspot->y."px; left:".
            $hotspot->x."px; cursor:pointer\" onClick=\"set_userid(this, $x, $y)\">";
        } else {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" id=\"u$x$y\" title=\"" . $y .
            "\" style=\"position:absolute; top:".$hotspot->y."px; left:".
            $hotspot->x."px; cursor:pointer\" onClick=\"set_userid(this, $x, $y)\">";
        }
    }

    // Print hotspots for group.
    foreach ($scanner->export_hotspots_group(OQ_IMAGE_WIDTH) as $key => $hotspot) {
        $x = substr($key, 1, 1);
        if (!$groupnumber || $groupnumber > $offlinequiz->numgroups) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" border=\"0\" id=\"g$x\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                    $hotspot->x."px; cursor:pointer\" onClick=\"set_group(this, $x)\">";
        } else if ($groupnumber == $x+1) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\" id=\"g$x\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                    $hotspot->x."px; cursor:pointer\" onClick=\"set_group(this, $x)\">";
        } else {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" id=\"g$x\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                    $hotspot->x."px; cursor:pointer\" onClick=\"set_group(this, $x)\">";
        }
    }

    // If we have a group and valid pagenumber and question usage slots, then we can display the answers.
    if (!empty($group) && ($pagenumber > 0) && ($pagenumber <= $group->numberofpages) && !empty($slots)) {
        $letterstr = 'abcdefghijklmnop';
        // We start at the top of the page (e.g. 0, 96, etc).
        $startindex = min( ($pagenumber - 1) * $questionsperpage, count($slots));
        // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
        $endindex = min( $pagenumber * $questionsperpage, count($slots) );

        $answerspots = $scanner->export_hotspots_answer(OQ_IMAGE_WIDTH);

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

                if ($itemdata[$slot][$key] == -1) {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" title=\"" . $slot . ' ' .
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer;\" onClick=\"set_item(this, $slot, $key)\">";
                } else if ($itemdata[$slot][$key] == 1) {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"  title=\"" . $slot . ' ' .
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer;\" onClick=\"set_item(this, $slot, $key)\">";
                } else {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"  title=\"" . $slot . ' ' .
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\" style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer;\" onClick=\"set_item(this, $slot, $key)\">";
                }
            }
            $questioncounter++;
        }
    } // end if (!empty($group...
}
?>

<script>
$(function() {
    $( "#c-0" ).draggable();
    $( "#c-1" ).draggable();
    $( "#c-2" ).draggable();
    $( "#c-3" ).draggable();
});
</script>
