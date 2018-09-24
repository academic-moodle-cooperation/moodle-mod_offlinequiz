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
 * The manual correction interface for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.4
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
$userchanged   = optional_param('userchanged', 0, PARAM_INT);

if (!$scannedpage = $DB->get_record('offlinequiz_scanned_pages', array('id' => $scannedpageid))) {
    print_error('noscannedpage', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpageid);
}

if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $scannedpage->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id,
        array('course' => $offlinequiz->course,
         'offlinequiz' => $offlinequiz->id));
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error('cmmissing', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $offlinequiz->id);
}
if (!$groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number',
        '*', 0, $offlinequiz->numgroups)) {
    print_error('nogroups', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $COURSE->id, $scannedpage->offlinequizid);
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
require_capability('mod/offlinequiz:viewreports', $context);

$url = new moodle_url('/mod/offlinequiz/correct.php', array('pageid' => $scannedpage->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

// Determine the maxanswers and maxquestions for the scannedpage.
$selectedgroups = $groups;
if ($overwrite && $scannedpage->resultid) {
    $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
    $resultgroup = $DB->get_record('offlinequiz_groups', array('id' => $result->offlinegroupid));
    $selectedgroups = array($resultgroup);
}
list($maxquestions, $maxanswers, $formtype, $questionsperpage) = offlinequiz_get_question_numbers($offlinequiz, $selectedgroups);

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

// Load the stored image file.
if (property_exists($scannedpage, 'id')) {
    // If we re-adjust, rotate, or changed the user we have to delete the stored hotspots.
    if ($action == 'readjust' || $action == 'rotate' || $action == 'checkuser') {
        $DB->delete_records('offlinequiz_hotspots', array('scannedpageid' => $scannedpage->id));
    }
    // Load the stored image and the hotspots from the DB if they have not been deleted.
    $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners, $scannedpage->id);
} else {
    // Load the stored image and adjust the hotspots from scratch.
    $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);
}

// Make a first check.
if (!$scanner->check_deleted()) {
    $scannedpage->status = 'error';
    $scannedpage->error = 'notadjusted';
}

// O=======================================.
// O Step 1. Get the data from the stored scanned page.
// O=======================================.
if ($action == 'load') {
    $filename = $scannedpage->filename;
    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    // Remember initial data for cancel action.
    $origfilename = $filename;
    $origuserkey = $userkey;
    $origgroupnumber = $groupnumber;
    $origpagenumber = $pagenumber;
    $origstatus = $scannedpage->status;
    $origerror = $scannedpage->error;
    $origtime = $scannedpage->time;

} else {
    if ($submitfilename = optional_param('filename', '', PARAM_RAW)) {
        $scannedpage->filename = $submitfilename;
        $filename = $submitfilename;
    }

    $origfilename = required_param('origfilename', PARAM_FILE);
    $origuserkey = required_param('origuserkey', PARAM_ALPHANUM);
    $origgroupnumber = required_param('origgroupnumber', PARAM_INT);
    $origpagenumber = required_param('origpagenumber', PARAM_INT);
    $origstatus = required_param('origstatus', PARAM_ALPHA);
    $origerror = required_param('origerror', PARAM_ALPHA);
    $origtime = required_param('origtime', PARAM_INT);
}

// If we still don't have corners, get them from the scanner.
if ($nodbcorners) {
    $corners = $scanner->get_corners();
    offlinequiz_save_page_corners($scannedpage, $corners);
}

// O=======================================.
// O Step 2. The user might have submitted data.
// O=======================================.
// O=============================================.
// O  Action cancel.
// O=============================================.
if ($action == 'cancel') {
    $scannedpage->filename = $origfilename;
    $scannedpage->userkey = $origuserkey;
    $scannedpage->groupnumber = $origgroupnumber;
    $scannedpage->pagenumber = $origpagenumber;
    $scannedpage->status = $origstatus;
    $scannedpage->error = $origerror;
    $scannedpage->time = $origtime;
    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    // Display a button to close the window and die.
    echo '<html>';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
    echo "<center><input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('closewindow', 'offlinequiz').
        "\" name=\"submitbutton4\" onClick=\"self.close(); return false;\"></center>";
    echo '</html>';
    die;

    // O=============================================.
    // O  Action checkuser.
    // O=============================================.
} else if ($action == 'checkuser') {

    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    // Maybe old errors have been fixed.
    $scannedpage->status = 'ok';
    $DB->set_field('offlinequiz_scanned_pages', 'status', 'ok', array('id' => $scannedpage->id));
    $scannedpage->error = '';
    $DB->set_field('offlinequiz_scanned_pages', 'error', '', array('id' => $scannedpage->id));

    $groupnumber = required_param('groupnumber', PARAM_TEXT);
    $groupnumber = intval($groupnumber);
    $scanner->set_group($groupnumber);
    $scannedpage->groupnumber = $groupnumber;

    // O=======================================================.
    // O Adjust the maxanswers of the scanner according to the offlinequiz group
    // O=======================================================.
    if ($newgroup = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'number' => $groupnumber))) {
        $maxanswers = offlinequiz_get_maxanswers($offlinequiz, array($newgroup));
        $scannedpage = $scanner->set_maxanswers($maxanswers, $scannedpage);
    }

    $usernumber = required_param('usernumber', PARAM_TEXT);

    $xes = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    // If we have only X's then we ignore the input.
    $userkey = $offlinequizconfig->ID_prefix . $usernumber . $offlinequizconfig->ID_postfix;
    if ($usernumber == substr($xes, 0, $offlinequizconfig->ID_digits)) {
        $scannedpage->userkey = null;
        $DB->set_field('offlinequiz_scanned_pages', 'userkey', null, array('id' => $scannedpage->id));
    } else {
        $scannedpage->userkey = $userkey;
    }

    // Now we check the scanned page with potentially updated information.

    $oldresultid = $scannedpage->resultid;
    $scannedpage = offlinequiz_check_for_changed_user($offlinequiz, $scanner, $scannedpage, $coursecontext,
                $questionsperpage, $offlinequizconfig);

    if ($oldresultid != $scannedpage->resultid) {
        // A new result has been linked to the scanned page.
        // Already process the answers but don't submit them yet.
        $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id,
                $questionsperpage, $coursecontext, false);
        $userchanged = 1;
    }

    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
            // Already process the answers but don't submit them.
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id,
                    $questionsperpage, $coursecontext, false);

            // Compare the old and the new result wrt. the choices.
            $scannedpage = offlinequiz_check_different_result($scannedpage);
        }
    }

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);
    $scanner->set_page($scannedpage->pagenumber);

    // O=============================================.
    // O Action update.
    // O=============================================.
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
    $scannedpage->warningfilename = '';

    $groupnumber = required_param('groupnumber', PARAM_TEXT);
    $scannedpage->groupnumber = $groupnumber;
    $scanner->set_group($groupnumber);

    $usernumber = required_param('usernumber', PARAM_TEXT);
    // If we have only X's then we ignore the input.
    if (!empty($usernumber)) {
        $userkey = $offlinequizconfig->ID_prefix . $usernumber . $offlinequizconfig->ID_postfix;
        $scannedpage->userkey = $userkey;
    }

    if ($overwrite && !$userchanged) {
        // We want to overwrite an old result, so we have to create a new one.
        // Don't delete the choices stored in the DB.

        // Delete the old result and create a new one.
        if ($scannedpage->resultid && $oldresult = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid))) {
            $oldresultid = $scannedpage->resultid;
            $scannedpage->resultid = 0;
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));
        }

        // This should create a new result and set the resultid field of the scannedpage.
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->resultid) {

            // Get all other pages of the old result and set their resultid number to the new one.
            $sql = "SELECT *
                      FROM {offlinequiz_scanned_pages}
                     WHERE offlinequizid = :offlinequizid
                       AND resultid = :resultid
                       AND status = 'submitted'
                       AND id <> :currentpageid";
            $params = array('offlinequizid' => $scannedpage->offlinequizid, 'resultid' => $oldresultid,
                     'currentpageid' => $scannedpage->id);

            if ($oldpages = $DB->get_records_sql($sql, $params)) {

                // Load the new result and the quba slots.
                $newresult = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
                $quba = question_engine::load_questions_usage_by_activity($newresult->usageid);
                $pageslots = $quba->get_slots();

                foreach ($oldpages as $page) {
                    $page->resultid = $scannedpage->resultid;
                    $DB->set_field('offlinequiz_scanned_pages', 'resultid', $scannedpage->resultid, array('id' => $page->id));

                    // Load the choices made before from the database. This might be empty.
                    $pagechoices = $DB->get_records('offlinequiz_choices', array('scannedpageid' => $page->id),
                            'slotnumber, choicenumber');

                    // Choicesdata contains the choices data from the DB indexed by slotnumber and choicenumber.
                    $pagechoicesdata = array();
                    if (!empty($pagechoices)) {
                        foreach ($pagechoices as $pagechoice) {
                            if (!isset($pagechoicesdata[$pagechoice->slotnumber]) ||
                                !is_array($pagechoicesdata[$pagechoice->slotnumber])) {
                                $pagechoicesdata[$pagechoice->slotnumber] = array();
                            }
                            $pagechoicesdata[$pagechoice->slotnumber][$pagechoice->choicenumber] = $pagechoice;
                        }
                    }
                    // Determine the slice of slots we are interested in.
                    // We start at the top of the page (e.g. 0, 96, etc).
                    $pagestartindex = min(($page->pagenumber - 1) * $questionsperpage, count($pageslots));
                    // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
                    $pageendindex = min($page->pagenumber * $questionsperpage, count($pageslots));
                    // Submit the choices of the other pages to the new result.
                    $page = offlinequiz_submit_scanned_page($offlinequiz, $page, $pagechoicesdata, $pagestartindex, $pageendindex);
                }
            }

            // Finally, delete the old result.
            offlinequiz_delete_result($oldresultid, $context);
        } else {
            // Otherwise keep the old resultid.
            $scannedpage->resultid = $oldresultid;
            $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
        }
        // TODO we have to figure out what to do with the other pages in case of a multipage test.
    }

    if (!$overwrite) {
        $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

        if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
            // Already process the answers but don't submit them.
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id,
                    $questionsperpage, $coursecontext, false);

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
    $rawitemdata = required_param_array('item', PARAM_RAW);

    // O=============================================.
    // O Action rotate.
    // O=============================================.
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
                $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $job->importuserid,
                        $questionsperpage, $coursecontext, false);

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

    // O=============================================.
    // O Action setpage.
    // O=============================================.
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
            $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $job->importuserid,
                    $questionsperpage, $coursecontext, false);

            // Compare the old and the new result wrt. the choices.
            $scannedpage = offlinequiz_check_different_result($scannedpage);
        }
    }

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    // O=============================================.
    // O Action readjust.
    // O=============================================.

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

    $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);

    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);


} else if ($action == 'enrol' && $offlinequizconfig->oneclickenrol) {
    // O=============================================.
    // O Action enrol.
    // O=============================================.
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    require_once($CFG->libdir . '/enrollib.php');
    require_once($CFG->dirroot.'/enrol/manual/locallib.php');

    // Check that the user has the permission to manual enrol.
    require_capability('enrol/manual:enrol', $coursecontext);

    $userid = $DB->get_field('user', 'id', array($offlinequizconfig->ID_field => $scannedpage->userkey), MUST_EXIST);

    // Get the manual enrolment plugin.
    $enrol = enrol_get_plugin('manual');
    if (empty($enrol)) {
        throw new moodle_exception('manualpluginnotinstalled', 'enrol_manual');
    }

    if (!is_enrolled($coursecontext, $userid)) {
        // Now we need the correct instance of the manual enrolment plugin.
        if (!$instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', IGNORE_MISSING)) {
            if ($instanceid = $enrol->add_default_instance($course)) {
                $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'), '*', MUST_EXIST);
            }
        }

        if ($instance != false) {
            $enrol->enrol_user($instance, $userid, $offlinequizconfig->oneclickrole);
        }
    }

    // Now we look for other pages with that user and reset their status.
    $sql = "SELECT *
              FROM {offlinequiz_scanned_pages}
             WHERE offlinequizid = :offlinequizid
               AND status = 'error'
               AND error = 'usernotincourse'
               AND userkey = :currentuserkey
               AND id <> :currentpageid";
    $params = array('offlinequizid' => $offlinequiz->id,
            'currentuserkey' => $scannedpage->userkey,
            'currentpageid' => $scannedpage->id);

    $otherpages = $DB->get_records_sql($sql, $params);
    foreach ($otherpages as $otherpage) {
        $otherpage->status = 'ok';
        $otherpage->error = '';
        $tempscanner = new offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);
        $tempcorners = array();
        if ($dbcorners = $DB->get_records('offlinequiz_page_corners', array('scannedpageid' => $otherpage->id), 'position')) {
            foreach ($dbcorners as $corner) {
                $tempcorners[] = new oq_point($corner->x, $corner->y);
            }
        } else {
            $tempcorners[0] = new oq_point(55, 39);
            $tempcorners[1] = new oq_point(805, 49);
            $tempcorners[2] = new oq_point(44, 1160);
            $tempcorners[3] = new oq_point(805, 1160);
        }
        $tempscanner->load_stored_image($otherpage->filename, $tempcorners);
        $otherpage = offlinequiz_check_scanned_page($offlinequiz, $tempscanner, $otherpage, $USER->id, $coursecontext);
        if ($otherpage->status == 'ok') {
            $otherpage = offlinequiz_process_scanned_page($offlinequiz, $tempscanner, $otherpage, $USER->id,
                  $questionsperpage, $coursecontext, true);
        }

        $DB->update_record('offlinequiz_scanned_pages', $otherpage);
    }

    // Now reset the status of the original page and check it again.
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    // Now check the scanned page again. The user should be enrolled now.
    $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

    $userkey = $scannedpage->userkey;
    $usernumber = substr($userkey, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
    $groupnumber = intval($scannedpage->groupnumber);
    $pagenumber = intval($scannedpage->pagenumber);
}

// We count the old choices to decide whether to process the page again.
$oldchoices = $DB->count_records('offlinequiz_choices', array('scannedpageid' => $scannedpage->id));

// If we have an OK page and the action was checkuser, setpage, etc. we should process the page.
if (($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') && ($action == 'readjust' ||
        $action == 'checkuser' || $action == 'enrol' || $action == 'setpage' || $action == 'rotate' ||
       ($action == 'load' && !$oldchoices))) {
    // Process the scanned page and write the answers in the offlinequiz_choices table.
    $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id,
            $questionsperpage, $coursecontext);
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
            $scanner->create_warning_image(substr($origuserkey, strlen($offlinequizconfig->ID_prefix),
                    $offlinequizconfig->ID_digits),
                    substr($user->{$offlinequizconfig->ID_field},
                    strlen($offlinequizconfig->ID_prefix),
                    $offlinequizconfig->ID_digits),
                    $origgroupnumber,
                    $group->number,
                    $changed);

            $filerecord = array(
                    'contextid' => $context->id,
                    'component' => 'mod_offlinequiz',
                    'filearea'  => 'imagefiles',
                    'itemid'    => 0,
                    'filepath'  => '/',
                    'filename'  => $scanner->filename . '_warning');

            // Create a unique temp dir.
            $unique = str_replace('.', '', microtime(true) . rand(0, 100000));
            $dirname = "{$CFG->tempdir}/offlinequiz/import/$unique";
            check_dir_exists($dirname, true, true);

            $warningpathname = $dirname . '/' . $filerecord['filename'];

            imagepng($scanner->image, $warningpathname);
            $newfile = $scanner->save_image($filerecord, $warningpathname);
            $scannedpage->warningfilename = $newfile->get_filename();

            $DB->set_field('offlinequiz_scanned_pages', 'warningfilename', $newfile->get_filename(),
                    array('id' => $scannedpage->id));

            unlink($warningpathname);
            remove_dir($dirname);
        }

        if (!$unknown) {
            $scannedpage = offlinequiz_submit_scanned_page($offlinequiz, $scannedpage, $choicesdata, $startindex, $endindex);
            if ($scannedpage->status == 'submitted') {
                $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
                if ($overwrite) {
                    echo '<script type="text/javascript">function closePage() {
                          window.opener.location.replace(\'' .
                            $CFG->wwwroot . '/mod/offlinequiz/review.php?q=' . $offlinequiz->id . '&resultid=' .
                            $scannedpage->resultid . '\'); window.close(); return false;
                          }
                          window.onload = closePage;
                          </script>';
                } else {
                    echo '<script type="text/javascript">function closePage() {
                          window.opener.location.reload(1); self.close(); return false;
                          }
                          window.onload = closePage;
                          </script>';
                }
                echo '</html>';
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
                $itemdata[$slot][$key] = 0;
            }
        }
    }
}

if ($group && $pagenumber > 0 and $pagenumber <= $group->numberofpages) {
    $scanner->set_page($pagenumber);
}

// O=======================================================================.
// O OUTPUT THE PAGE HTML.
// O=======================================================================.
echo '<html>';
echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo "</style>\n";
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';

echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/jquery-1.4.3.min.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.core.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.widget.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.mouse.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.draggable.js');

$javascript = "<script language=\"JavaScript\">
function checkinput() {
 // Get all item elements. We have jquery!
 items = $('input[name^=\"item\"]');

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
    document.images[key].src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\";
  }
  image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\";
  document.forms.cform.usernumber.value = document.forms.cform.usernumber.value.substr(0,x) + y +
     document.forms.cform.usernumber.value.substr(x+1);
}

function set_group(image, x) {
 for (i=0; i<=5; i++) {
  document.images['g'+i].src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\";
 }
 image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"
 document.forms.cform.groupnumber.value = x+1;
 document.forms.cform.elements['action'].value='checkuser';
 document.forms.cform.submit();
}

function set_item(image, x, y) {
  key = 'item['+x+'-'+y+']';
  if (document.forms.cform.elements[key].value == '1') {
    image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\";
    document.forms.cform.elements[key].value = 0;
  } else {
    image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\";
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
   alert('" . get_string('movecorners', 'offlinequiz') . "');
 } else {
  document.forms.cform.elements['action'].value='readjust';
  document.forms.cform.submit();
 }
}

function submitCancel() {
  document.forms.cform.elements['action'].value='cancel';
  document.forms.cform.submit();
}

function submitPage() {
  for (i=0; i<=3; i++) {
    corner = document.getElementById('c-'+i);
    document.forms.cform.elements['c-'+i+'-x'].value = corner.style.left.replace('px','');
    document.forms.cform.elements['c-'+i+'-y'].value = corner.style.top.replace('px','');
  }
  document.forms.cform.elements['action'].value='setpage';
  document.forms.cform.submit();
}

function submitCheckuser() {
  for (i=0; i<=3; i++) {
    // Reset possible readjustment.
    document.forms.cform.elements['c-'+i+'-x'].value = document.forms.cform.elements['c-old-'+i+'-x'].value;
    document.forms.cform.elements['c-'+i+'-y'].value = document.forms.cform.elements['c-old-'+i+'-y'].value;
  }
  document.forms.cform.elements['action'].value='checkuser';
  document.forms.cform.submit();
}

function submitEnrol() {
  document.forms.cform.elements['action'].value='enrol';
  document.forms.cform.submit();
}

function submitRotated() {
  document.forms.cform.elements['action'].value='rotate';
  document.forms.cform.submit();
}

function toggleImage() {
    img = $('#scannedimage').toggle();
}
</script>";

echo $javascript;

$fs = get_file_storage();
$imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename);

// Print image of the form sheet.
echo '<img id="scannedimage" name="formimage" src="' . $CFG->wwwroot .
   "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" .
   $imagefile->get_filename() .'" border="1" width="' . OQ_IMAGE_WIDTH .
   '" style="position:absolute; top:0px; left:0px; display: block;">';


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
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong style=\"color: red\">(" . get_string('error' .
            $scannedpage->error, 'offlinequiz_rimport') . ")</strong>\n";
}
echo "</div>\n";

// Print action buttons and form.
echo "<form method=\"post\" action=\"correct.php?pageid=$scannedpage->id\" id=\"cform\">\n";

echo "<div style=\"position:absolute; top:10px; left:" . (OQ_IMAGE_WIDTH + 10) . "px; width:280px\">\n";
echo "<div style=\"margin:4px;margin-bottom:8px\"><u>";
print_string('actions');
echo ":</u></div>\n";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"submitCancel(); return false;\"><br />";
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

    echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('checkuserid', 'offlinequiz') .
    "\" name=\"submitbutton4\" onClick=\"submitCheckuser(); return false;\"><br />";

    if ($scannedpage->error == 'usernotincourse' && $offlinequizconfig->oneclickenrol) {
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('enroluser', 'offlinequiz') .
        "\" name=\"submitbutton6\" onClick=\"submitEnrol(); return false;\"><br />";

    }

    // Show enabled save button if the error state allows it.
    if ($scannedpage->error != 'doublepage' &&
            $scannedpage->error != 'resultexists' &&
            $scannedpage->error != 'nonexistinguser' &&
            $scannedpage->error != 'usernotincourse' &&
            $scannedpage->error != 'grouperror' &&
            $scannedpage->error != 'differentresultexists') {
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('saveandshow', 'offlinequiz') .
          "\" name=\"submitbutton2\" onClick=\"document.forms.cform.show.value=1; return checkinput()\"><br />";
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('save', 'offlinequiz') .
          "\" name=\"submitbutton1\" onClick=\"return checkinput()\">";
    } else {
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('saveandshow', 'offlinequiz') .
          "\" name=\"submitbutton2\" disabled=\"disabled\"><br />";
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('save', 'offlinequiz') .
          "\" name=\"submitbutton1\" disabled=\"disabled\">";
    }
} else {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('checkuserid', 'offlinequiz') .
      "\" name=\"submitbutton4\" disabled=\"disabled\"><br />";
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('saveandshow', 'offlinequiz') .
      "\" name=\"submitbutton2\" disabled=\"disabled\"><br />";
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('save', 'offlinequiz') .
      "\" name=\"submitbutton1\" disabled=\"disabled\">";
}
echo "</div>\n";

echo "<input type=\"hidden\" name=\"usernumber\" value=\"$usernumber\">\n";
echo "<input type=\"hidden\" name=\"groupnumber\" value=\"$groupnumber\">\n";
echo "<input type=\"hidden\" name=\"overwrite\" value=\"$overwrite\">\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"". sesskey() . "\">\n";
echo "<input type=\"hidden\" name=\"filename\" value=\"$filename\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
echo "<input type=\"hidden\" name=\"show\" value=\"0\">\n";

echo "<input type=\"hidden\" name=\"origfilename\" value=\"$origfilename\">\n";
echo "<input type=\"hidden\" name=\"origuserkey\" value=\"$origuserkey\">\n";
echo "<input type=\"hidden\" name=\"origgroupnumber\" value=\"$origgroupnumber\">\n";
echo "<input type=\"hidden\" name=\"origpagenumber\" value=\"$origpagenumber\">\n";
echo "<input type=\"hidden\" name=\"origstatus\" value=\"$origstatus\">\n";
echo "<input type=\"hidden\" name=\"origerror\" value=\"$origerror\">\n";
echo "<input type=\"hidden\" name=\"origtime\" value=\"$origtime\">\n";

if ($userchanged) {
    echo "<input type=\"hidden\" name=\"userchanged\" value=\"1\">\n";
}

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
    style=\"position:absolute; top:".($hotspot->y - 7)."px; left:".($hotspot->x - 7)."px; cursor:move; z-index:100;\">";
}


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
            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_userid(this, $x, $y)\">";
        } else if (!empty($usernumber) and substr($usernumber, $x, 1) == $y) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\" id=\"u$x$y\" title=\"" . $y .
            "\" style=\"position:absolute; top:".$hotspot->y."px; left:".
            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_userid(this, $x, $y)\">";
        } else {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" id=\"u$x$y\" title=\"" . $y .
            "\" style=\"position:absolute; top:".$hotspot->y."px; left:".
            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_userid(this, $x, $y)\">";
        }
    }

    // Print hotspots for group.
    foreach ($scanner->export_hotspots_group(OQ_IMAGE_WIDTH) as $key => $hotspot) {
        $x = substr($key, 1, 1);
        if (!$groupnumber || $groupnumber > $offlinequiz->numgroups) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" border=\"0\" id=\"g$x\"" .
              " style=\"position:absolute; top:" .
              $hotspot->y . "px; left:" . $hotspot->x . "px; cursor:pointer;  z-index: 100;\" onClick=\"set_group(this, $x)\">";
        } else if ($groupnumber == $x + 1) {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\" id=\"g$x\"" .
              " style=\"position:absolute; top:" .
              $hotspot->y."px; left:" . $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_group(this, $x)\">";
        } else {
            echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" id=\"g$x\"" .
              " style=\"position:absolute; top:" .
              $hotspot->y."px; left:" . $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_group(this, $x)\">";
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
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\"" .
                            " style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_item(this, $slot, $key)\">";
                } else if ($itemdata[$slot][$key] == 1) {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"  title=\"" . $slot . ' ' .
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\"" .
                            " style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_item(this, $slot, $key)\">";
                } else {
                    echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"  title=\"" . $slot . ' ' .
                            $letterstr[$key] . "\" border=\"0\" id=\"a-$slot-$key\"" .
                            " style=\"position:absolute; top:".$hotspot->y."px; left:".
                            $hotspot->x."px; cursor:pointer; z-index: 100;\" onClick=\"set_item(this, $slot, $key)\">";
                }
            }
            $questioncounter++;
        } // End if.
    }
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
