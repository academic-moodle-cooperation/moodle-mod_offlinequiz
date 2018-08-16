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
 *
 * Functions for checking and evaluting scanned answer forms and lists of participants.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/scanner.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Checks  groupnumber, userkey, and pagenumber of a scanned answer form
 *
 * @param unknown_type $offlinequiz
 * @param offlinequiz_page_scanner $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $teacherid
 * @param unknown_type $coursecontext
 */
function offlinequiz_check_scanned_page($offlinequiz, offlinequiz_page_scanner $scanner, $scannedpage,
         $teacherid, $coursecontext, $autorotate = false, $recheckresult = false, $ignoremaxanswers = false) {
    global $DB, $CFG;

    $offlinequizconfig = get_config('offlinequiz');

    if (!$scanner->check_deleted()) {
        $scannedpage->status = 'error';
        $scannedpage->error = 'notadjusted';
    }

    if ($scannedpage->status == 'error' && $scanner->ontop && $autorotate) {
        echo 'rotating...' . "\n";

        $oldfilename = $scannedpage->filename;
        if ($newfile = $scanner->rotate_180()) {
            $scannedpage->status = 'ok';
            $scannedpage->error = '';
            $scannedpage->userkey = null;
            $scannedpage->pagenumber = null;
            $scannedpage->groupnumber = null;
            $scannedpage->filename = $newfile->get_filename();
            $corners = $scanner->get_corners();
            $newcorners = array();

            // Create a completely new scanner.
            $scanner = new offlinequiz_page_scanner($offlinequiz, $scanner->contextid,
                                                    $scanner->maxquestions, $scanner->maxanswers);

            $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $newcorners);
            if (!$sheetloaded) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'fatalerror';
            } else if (!$scanner->check_deleted()) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'notadjusted';
            } else {
                $scannedpage->status = 'ok';
                $scannedpage->error = '';
            }
        }
    }

    // Check the group number.
    $groupnumber = $scanner->calibrate_and_get_group(); // Call group first for callibration, such a crap!
    if (!property_exists($scannedpage, 'groupnumber') || $scannedpage->groupnumber == 0) {
        $scannedpage->groupnumber = $groupnumber;
    }

    $group = null;
    if ($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') {
        if (!$group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id,
                                                                  'number' => $scannedpage->groupnumber))) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'grouperror';
        }
    }

    // Adjust the maxanswers of the scanner according to the offlinequiz group deterimined above.
    if ($group && !$ignoremaxanswers) {
        $maxanswers = offlinequiz_get_maxanswers($offlinequiz, array($group));
        if ( $maxanswers != $scanner->maxanswers ) {
            // Create a completely new scanner.
            $corners = $scanner->get_corners();
            $scanner = new offlinequiz_page_scanner($offlinequiz, $scanner->contextid, $scanner->maxquestions, $maxanswers);

            $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);

            // Recursively call this method this time ignoring the maxanswers change.
            return offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage,
                 $teacherid, $coursecontext, $autorotate, $recheckresult, true);
        }
    }

    // Check the user key (username, or userid, or other).
    $usernumber = $scanner->get_usernumber();
    if (empty($scannedpage->userkey)) {
        $scannedpage->userkey = $offlinequizconfig->ID_prefix . $usernumber . $offlinequizconfig->ID_postfix;
    }

    if ($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') {
        if (!$user = $DB->get_record('user', array($offlinequizconfig->ID_field => $scannedpage->userkey))) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'nonexistinguser';
        } else {
            $coursestudents = get_enrolled_users($coursecontext, 'mod/offlinequiz:attempt');
            if (empty($coursestudents[$user->id])) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'usernotincourse';
            }
        }
    }

    // Check the pagenumber.
    // Patch for old answer forms that did not have the page barcode.
    // With this patch the old forms can be uploaded in Moodle 2.x anyway.
    $pagenumber = $scanner->get_page();

    if ($group && ($group->numberofpages == 1)) {
        $scannedpage->pagenumber = 1;
        $scanner->set_page(1);
        $page = 1;
    } else {
        if (!property_exists($scannedpage, 'pagenumber') || $scannedpage->pagenumber == 0 || $scannedpage->pagenumber == null) {
            $scannedpage->pagenumber = $pagenumber;
        } else {
            // This is neede because otherwise the scanner doesn't return answers (questionsonpage not set).
            $scanner->set_page($scannedpage->pagenumber);
        }
        $page = $scannedpage->pagenumber;

        if ($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') {
            if ($page < 1 || $page > $group->numberofpages) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'invalidpagenumber';
            }
        }
    }

    // If we have a valid userkey, a group and a page number then we can
    // check whether there is already a scanned page or even a completed result with the same group, userid, etc.
    if (($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') && $user && $group && $page) {
        $resultexists = false;
        if (!property_exists($scannedpage, 'resultid') || !$scannedpage->resultid || $recheckresult) {
            $sql = "SELECT id
                      FROM {offlinequiz_results}
                     WHERE offlinequizid = :offlinequizid
                       AND userid = :userid
                       AND status = 'complete'";
            $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id, 'userid' => $user->id);

            if ($DB->get_record_sql($sql, $params)) {
                $resultexists = true;
            }
        }

        if ($resultexists) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'resultexists';
        } else {
            if (!property_exists($scannedpage, 'id') || !$scannedpage->id) {
                $otherpages = $DB->get_records('offlinequiz_scanned_pages',
                                               array('offlinequizid' => $offlinequiz->id,
                                                     'userkey' => $user->{$offlinequizconfig->ID_field},
                                                     'groupnumber' => $group->number, 'pagenumber' => $page));
            } else {
                $sql = "SELECT id
                          FROM {offlinequiz_scanned_pages}
                         WHERE offlinequizid = :offlinequizid
                           AND userkey = :userkey
                           AND groupnumber = :groupnumber
                           AND pagenumber = :pagenumber
                           AND (status = 'ok' OR status = 'submitted')
                           AND id <> :id";

                $params = array('offlinequizid' => $offlinequiz->id,
                                                'userkey' => $user->{$offlinequizconfig->ID_field},
                                                'groupnumber' => $group->number,
                                                'pagenumber' => $page,
                                                'id' => $scannedpage->id);

                $otherpages = $DB->get_records_sql($sql, $params);
            }
            if ($otherpages) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'doublepage';
            }
        }
    }

    // Still everything OK, so we have a user and a group. Thus we can get/create the associated result
    // we also do that if another result exists, s.t. we have the answers later.
    if (empty($scannedpage->resultid)) {
        if ($scannedpage->status == 'ok' || $scannedpage->status == 'suspended' ||
                ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') ||
                ($scannedpage->status == 'error' && $scannedpage->error == 'usernotincourse')) {
            // We have a group and a userid, so we can check if there is a matching partial result in the offlinequiz_results table.
            // The problem with this is that we could have several partial results with several pages.
            $sql = "SELECT *
                      FROM {offlinequiz_results}
                     WHERE offlinequizid = :offlinequizid
                       AND offlinegroupid = :offlinegroupid
                       AND userid = :userid
                       AND status = 'partial'
                  ORDER BY id ASC";
            $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id, 'userid' => $user->id);
            if (!$result = $DB->get_record_sql($sql, $params)) {

                // There is no result. First we have to clone the template question usage of the offline group.
                // We have to use our own loading function in order to get the right class.
                $templateusage = offlinequiz_load_questions_usage_by_activity($group->templateusageid);

                // Get the question instances for initial maxmarks.
                $sql = "SELECT questionid, maxmark
                          FROM {offlinequiz_group_questions}
                         WHERE offlinequizid = :offlinequizid
                           AND offlinegroupid = :offlinegroupid";

                $qinstances = $DB->get_records_sql($sql,
                        array('offlinequizid' => $offlinequiz->id,
                              'offlinegroupid' => $group->id));

                // Clone it...
                $quba = $templateusage->get_clone($qinstances);

                // And save it. The clone contains the same question in the same order and the same order of the answers.
                question_engine::save_questions_usage_by_activity($quba);

                $result = new stdClass();
                $result->offlinequizid = $offlinequiz->id;
                $result->offlinegroupid = $group->id;
                $result->userid = $user->id;
                $result->teacherid = $teacherid;
                $result->usageid = $quba->get_id();
                $result->attendant = 'scanonly';
                $result->status = 'partial';
                $result->timecreated = time();
                $result->timemodified = time();

                $newid = $DB->insert_record('offlinequiz_results', $result);

                if ($newid) {
                    $result->id = $newid;
                    $scannedpage->resultid = $result->id;
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'noresult';
                }
            } else {
                // There is a partial result, so we can just load the user's question usage.
                // From now on we can use the default question usage class.
                $scannedpage->resultid = $result->id;
            }
        }
    }

    // We insert the scanned page into the database in any case.
    $scannedpage->time = time();
    if (property_exists($scannedpage, 'id') && !empty($scannedpage->id)) {
        $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
    } else {
        $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
    }

    // Now the scanned page definitely has an ID, so we can store the corners.
    if (!$DB->get_records('offlinequiz_page_corners', array('scannedpageid' => $scannedpage->id))) {
        $corners = $scanner->get_corners();
        offlinequiz_save_page_corners($scannedpage, $corners);
    }

    if ($autorotate) {
        return array($scanner, $scannedpage);
    } else {
        return $scannedpage;
    }
}


/**
 * Stores the choices made on a scanned page in the table offlinequiz_choices. If there are no insecure markings
 * the page is also submitted, i.e. the answers are processed by the question usage by activiy (quba).
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $teacherid
 * @param unknown_type $coursecontext
 */
function offlinequiz_process_scanned_page($offlinequiz, offlinequiz_page_scanner $scanner, $scannedpage,
                                          $teacherid, $questionsperpage, $coursecontext, $submit = false) {
    global $DB;

    $offlinequizconfig = get_config('offlinequiz');

    if (property_exists($scannedpage, 'resultid') && $scannedpage->resultid) {
        $group = $DB->get_record('offlinequiz_groups',
                                 array('offlinequizid' => $offlinequiz->id, 'number' => $scannedpage->groupnumber));
        $user = $DB->get_record('user', array($offlinequizconfig->ID_field => $scannedpage->userkey));
        $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
        $quba = offlinequiz_load_questions_usage_by_activity($result->usageid);
        // Retrieve the answers. This initialises the answer hotspots.
        $answers = $scanner->get_answers();

        if (empty($answers)) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'notadjusted';
            return $scannedpage;
        }
        $slots = $quba->get_slots();

        // We start at the top of the page (e.g. 0, 96, etc).
        $startindex = ($scannedpage->pagenumber - 1) * $questionsperpage;
        // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
        $endindex = min( $scannedpage->pagenumber * $questionsperpage, count($slots) );

        $answerindex = 0;

        $insecuremarkings = false;
        $choicesdata = array();

        for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {

            $slot = $slots[$slotindex];
            $slotquestion = $quba->get_question($slot);
            $attempt = $quba->get_question_attempt($slot);
            $order = $slotquestion->get_order($attempt);  // Order of the answers.

            // Note: The array length of a row is $maxanswers, so probably bigger than the number of answers in the slot.
            $row = $answers[$answerindex++];

            $count = 0;
            $response = array();
            if (!isset($choicesdata[$slot]) || !is_array($choicesdata[$slot])) {
                $choicesdata[$slot] = array();
            }

            // Go through all answers of the slot question.
            foreach ($order as $key => $notused) {
                // Create the data structure for the offlinequiz_choices table.
                $choice = new stdClass();
                $choice->scannedpageid = $scannedpage->id;
                $choice->slotnumber = $slot;
                $choice->choicenumber = $key;

                // Check what the scanner recognised.
                if ($row[$key] == 'marked') {
                    $choice->value = 1;
                } else if ($row[$key] == 'empty') {
                    $choice->value = 0;
                } else {
                    $choice->value = -1;
                    $insecuremarkings = true;
                }
                // We really want to save every single cross  in the database.
                $choice->id = $DB->insert_record('offlinequiz_choices', $choice);
                $choicesdata[$slot][$key] = $choice;
            }
        } // End for (slot...

        if ((!$insecuremarkings) and $submit) {
            $scannedpage = offlinequiz_submit_scanned_page($offlinequiz, $scannedpage, $choicesdata, $startindex, $endindex);
            if ($scannedpage->status == 'submitted') {
                offlinequiz_check_result_completed($offlinequiz, $group, $result);
            }
        }

        // If insecure markings have been found, set the status appropriately.
        if (($scannedpage->status == 'ok' || $scannedpage->status == 'suspended') && $insecuremarkings) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'insecuremarkings';
            $scannedpage->time = time();
            $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
        }
    } // End if status ok.

    return $scannedpage;
}


/**
 *
 * We assume that choicesdata does not contain any insecure values (-1) anymore.
 *
 * @param mixed $offlinequiz
 * @param mixed $scannedpage
 * @param array $choicesdata
 * @param int $startindex
 * @param int $endindex
 */
function offlinequiz_submit_scanned_page($offlinequiz, $scannedpage, $choicesdata, $startindex, $endindex) {
    global $DB;

    $offlinequizconfig = get_config('offlinequiz');

    $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
    $quba = question_engine::load_questions_usage_by_activity($result->usageid);
    $slots = $quba->get_slots();

    for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {

        $slot = $slots[$slotindex];
        $slotquestion = $quba->get_question($slot);
        $attempt = $quba->get_question_attempt($slot);
        $order = $slotquestion->get_order($attempt);  // Order of the answers.

        $count = 0;
        $unknown = false;
        $response = array();

        // Go through all answers of the slot question.
        foreach ($order as $key => $notused) {
            // Check what the scanner recognised.
            if ($choicesdata[$slot][$key]->value == 1) {
                // Also fill the response array s.t. we can grade later if possible.
                if ($slotquestion instanceof qtype_multichoice_single_question) {
                    $response['answer'] = $key;
                    // In case of singlechoice we count the crosses.
                    // If more than 1 cross have been made, we don't submit the response.
                    $count++;
                } else if ($slotquestion instanceof qtype_multichoice_multi_question) {
                    $response['choice' . $key] = 1;
                }
            } else if ($choicesdata[$slot][$key]->value == 0) {
                if ($slotquestion instanceof qtype_multichoice_multi_question) {
                    $response['choice' . $key] = 0;
                }
            }
        }

        // We can submit the response and finish the attempt for this question.
        if ($slotquestion instanceof qtype_multichoice_single_question) {
            // We only submit the response of at most 1 cross has been made.
            if ($count <= 1) {
                $quba->process_action($slot, $response);
                $quba->finish_question($slot, time());
            }
        } else if ($slotquestion instanceof qtype_multichoice_multi_question) {
            $quba->process_action($slot, $response);
            $quba->finish_question($slot, time());
        }

    } // End for slotindex.

    question_engine::save_questions_usage_by_activity($quba);

    $scannedpage->status = 'submitted';
    $scannedpage->error = 'missingpages';
    $scannedpage->time = time();

    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    return $scannedpage;
}

/**
 * Check whether the group number of a scanned page has been changed wrt. the result.
 * If so, we have to create a new result using the new group's question usage template.
 * Also checks whether the new and the old result differ in terms of markings.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $coursecontext
 * @param unknown_type $questionsperpage
 * @param unknown_type $offlinequizconfig
 * @return object The updated scanned page
 */
function offlinequiz_check_for_changed_groupnumber($offlinequiz, $scanner, $scannedpage, $coursecontext,
                                                   $questionsperpage, $offlinequizconfig) {
    global $DB, $USER;

    if (property_exists($scannedpage, 'resultid') and $scannedpage->resultid) {
        if ($result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid))) {
            if ($oldgroup = $DB->get_record('offlinequiz_groups', array('id' => $result->offlinegroupid))) {
                if (intval($oldgroup->number) > 0 && (intval($oldgroup->number) != $scannedpage->groupnumber)) {
                    $oldresultid = $scannedpage->resultid;
                    // We have to disconnect this page and all other pages from this result
                    // because we have to create a new result
                    // using the new group's question usage template.
                    unset($scannedpage->resultid);
                    $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));
                    $DB->set_field('offlinequiz_scanned_pages', 'status', 'ok', array('id' => $scannedpage->id));
                    $DB->set_field('offlinequiz_scanned_pages', 'error', '', array('id' => $scannedpage->id));

                    // Disconnect all other pages from this result and set their status to 'suspended'.
                    $otherpages = $DB->get_records('offlinequiz_scanned_pages', array('resultid' => $oldresultid));
                    foreach ($otherpages as $otherpage) {
                        $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $otherpage->id));
                        if ($otherpage->status == 'submitted') {
                            $DB->set_field('offlinequiz_scanned_pages', 'status', 'suspended', array('id' => $otherpage->id));
                            $DB->set_field('offlinequiz_scanned_pages', 'error', '', array('id' => $otherpage->id));
                        }
                    }
                    // Delete the old result.
                    $DB->delete_records('offlinequiz_results', array('id' => $oldresultid));

                    // Now the old result cannot be found and we can check the page again which will produce a new result.
                    $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);
                    if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
                        // Already process the answers but don't submit them.
                        $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id,
                                                                        $questionsperpage, $coursecontext, false);

                        // Compare the old and the new result wrt. the choices.
                        $scannedpage = offlinequiz_check_different_result($scannedpage);
                    }
                }
            }
        } else {
            unset($scannedpage->resultid);
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));

            if (!$DB->get_record('offlinequiz_scanned_pages', array('resultid' => $oldresultid))) {

                // Delete the result.
                $DB->delete_records('offlinequiz_results', array('id' => $resultid));
            }
        }
    }
    return $scannedpage;
}


/**
 * Checks whether the userkey of a scannedpage has been changed. If so, we have to create a new result
 * using the new user ID.
 * Also checks whether the new and the old result differ in terms of markings.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $coursecontext
 * @param unknown_type $questionsperpage
 * @param unknown_type $offlinequizconfig
 * @return Ambigous <unknown_type, multitype:unknown_type offlinequiz_page_scanner >
 */
function offlinequiz_check_for_changed_user($offlinequiz, $scanner, $scannedpage,
                                            $coursecontext, $questionsperpage, $offlinequizconfig) {
    global $DB, $USER;

    if (property_exists($scannedpage, 'resultid') and $scannedpage->resultid) {
        if ($result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid))) {
            if ($newuser = $DB->get_record('user', array($offlinequizconfig->ID_field => $scannedpage->userkey))) {
                if ($newuser->id != $result->userid) {
                    $oldresultid = $scannedpage->resultid;
                    // We have to disconnect the page from its result because we have to create a new result for the new user.
                    unset($scannedpage->resultid);
                    $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));

                    // TODO what do we do with other pages that contributed to the old result?

                    if (!$DB->get_record('offlinequiz_scanned_pages', array('resultid' => $oldresultid))) {
                        // Delete the result if no other pages use this result.
                        $DB->delete_records('offlinequiz_results', array('id' => $oldresultid));
                    }
                    // Now the old result cannot be found and we can check the page again which will produce a new result.
                    $scannedpage = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);
                    if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
                        // Already process the answers but don't submit them.
                        $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage,
                                                                        $USER->id, $questionsperpage, $coursecontext, false);

                        // Compare the old and the new result wrt. the choices.
                        $scannedpage = offlinequiz_check_different_result($scannedpage);
                    }
                }
            }
        } else {
            unset($scannedpage->resultid);
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $scannedpage->id));

            if (!$DB->get_record('offlinequiz_scanned_pages', array('resultid' => $oldresultid))) {
                // Delete the result.
                $DB->delete_records('offlinequiz_results', array('id' => $resultid));
            }
        }
    }
    return $scannedpage;
}

/**
 * Checks whether a given result is complete, i.e. all contributing scanned pages have been submitted.
 * Updates the result in the DB if it is complete. Also updates the scanned pages that were duplicates from
 * 'doublepage' to 'resultexists'
 *
 * @param object $offlinequiz
 * @param object $group
 * @param object $result
 * @return boolean
 */
function offlinequiz_check_result_completed($offlinequiz, $group, $result) {
    global $DB;

    $resultpages = $DB->get_records_sql(
            "SELECT *
               FROM {offlinequiz_scanned_pages}
              WHERE resultid = :resultid
                AND status = 'submitted'",
            array('resultid' => $result->id));

    if (count($resultpages) == $group->numberofpages) {
        $transaction = $DB->start_delegated_transaction();

        $quba = question_engine::load_questions_usage_by_activity($result->usageid);
        $quba->finish_all_questions(time());
        $totalmark = $quba->get_total_mark();
        question_engine::save_questions_usage_by_activity($quba);

        $result->sumgrades = $totalmark;
        $result->status = 'complete';
        $result->timestart = time();
        $result->timefinish = time();
        $result->timemodified = time();
        $DB->update_record('offlinequiz_results', $result);

        $transaction->allow_commit();
        offlinequiz_update_grades($offlinequiz, $result->userid);

        // Change the error of all submitted pages of the result to '' (was 'missingpages' before).
        foreach ($resultpages as $page) {
            $DB->set_field('offlinequiz_scanned_pages', 'error', '', array('id' => $page->id));
        }

        // Change the status of all double pages of the user to 'resultexists'.
        $offlinequizconfig = get_config('offlinequiz');
        $user = $DB->get_record('user', array('id' => $result->userid));

        $sql = "SELECT id
                  FROM {offlinequiz_scanned_pages}
                 WHERE offlinequizid = :offlinequizid
                   AND userkey = :userkey
                   AND groupnumber = :groupnumber
                   AND error = 'doublepage'";

        $params = array('offlinequizid' => $offlinequiz->id,
                'userkey' => $user->{$offlinequizconfig->ID_field},
                'groupnumber' => $group->number);
        $doublepages = $DB->get_records_sql($sql, $params);
        foreach ($doublepages as $page) {
            $DB->set_field('offlinequiz_scanned_pages', 'error', 'resultexists', array('id' => $page->id));
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', 0, array('id' => $page->id));
        }

        return true;
    }
    return false;
}

/**
 * Checks whether the markings made on a scanned page are different from the markings on another scanned page
 * for the same user. Adjusts the error field of the scanned page accordingly.
 *
 * @param object $scannedpage
 * @return object The modified scanned page.
 */
function offlinequiz_check_different_result($scannedpage) {
    global $DB;

    if ($newchoices = $DB->get_records('offlinequiz_choices',
                                       array('scannedpageid' => $scannedpage->id), ' slotnumber, choicenumber ')) {

        $newresult = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));

        $newchoicesindexed = array();
        foreach ($newchoices as $newchoice) {
            if (!isset($newchoicesindexed[$newchoice->slotnumber])) {
                $newchoicesindexed[$newchoice->slotnumber] = array();
            }
            $newchoicesindexed[$newchoice->slotnumber][$newchoice->choicenumber] = $newchoice;
        }

        $sql = "SELECT id
                  FROM {offlinequiz_results}
                 WHERE offlinequizid = :offlinequizid
                   AND offlinegroupid = :offlinegroupid
                   AND userid = :userid
                   AND status = 'complete'";
        $params = array('offlinequizid' => $newresult->offlinequizid,
                'offlinegroupid' => $newresult->offlinegroupid,
                'userid' => $newresult->userid);

        $oldresult = $DB->get_record_sql($sql, $params);
        if ($oldpageid = $DB->get_field('offlinequiz_scanned_pages', 'id',
                                        array('resultid' => $oldresult->id, 'pagenumber' => $scannedpage->pagenumber))) {
            $oldchoices = $DB->get_records('offlinequiz_choices', array('scannedpageid' => $oldpageid), 'slotnumber, choicenumber');

            foreach ($oldchoices as $oldchoice) {
                if (isset($newchoicesindexed[$oldchoice->slotnumber]) &&
                    isset($newchoicesindexed[$oldchoice->slotnumber][$oldchoice->choicenumber])) {
                    $newchoice = $newchoicesindexed[$oldchoice->slotnumber][$oldchoice->choicenumber];
                    if ($oldchoice->value != $newchoice->value) {
                        $scannedpage->error = 'differentresultexists';
                        $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
                        break;
                    }
                }
            }
        }
    }
    return $scannedpage;
}


/**
 * Calculate the characteristic numbers for an offlinequiz, i.e. maximum number of questions, maximum number of answers,
 * number of columns on the answer form and the maximum number of questions on each page.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $groups
 */
function offlinequiz_get_question_numbers($offlinequiz, $groups) {
    $maxquestions = offlinequiz_get_maxquestions($offlinequiz, $groups);
    $maxanswers = offlinequiz_get_maxanswers($offlinequiz, $groups);

    // Determine the form type (number of columns).
    $formtype = 4;
    if ($maxanswers > 5) {
        $formtype = 3;
    }
    if ($maxanswers > 7) {
        $formtype = 2;
    }
    if ($maxanswers > 12) {
        $formtype = 1;
    }

    // Determine how many questions are on a full page.
    $questionsperpage = 96;
    if ($maxanswers > 5) {
        $questionsperpage = 72;
    }
    if ($maxanswers > 7) {
        $questionsperpage = 48;
    }
    if ($maxanswers > 12) {
        $questionsperpage = 24;
    }

    return array($maxquestions, $maxanswers, $formtype, $questionsperpage);

}

// O=======================================================================.
// O=======================================================================.
// Functions for lists of participants.
// O=======================================================================.
// O=======================================================================.
/**
 * Checks  groupnumber, userkey, and pagenumber of a scanned list of participants page
 *
 * @param unknown_type $offlinequiz
 * @param offlinequiz_page_scanner $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $teacherid
 * @param unknown_type $coursecontext
 */
function offlinequiz_check_scanned_participants_page($offlinequiz, offlinequiz_participants_scanner $scanner,
                                                     $scannedpage, $teacherid, $coursecontext, $autorotate = false) {
    global $DB;

    // Check the list number.
    if (!property_exists($scannedpage, 'listnumber') || $scannedpage->listnumber == 0) {
        $listnumber = $scanner->get_list();

        if (is_string($listnumber)) {
            $intln = intval($listnumber);
            if ($intln > 0) {
                $listnumber = $intln;
                $scannedpage->listnumber = $listnumber;
            }
        }
    }

    if ($scannedpage->status == 'ok') {
        $maxlistnumber = $DB->get_field_sql("
                SELECT MAX(number)
                  FROM {offlinequiz_p_lists}
                 WHERE offlinequizid = :offlinequizid",
                array('offlinequizid' => $offlinequiz->id));
        if (!property_exists($scannedpage, 'listnumber') ||
                (!is_int($scannedpage->listnumber)) ||
                $scannedpage->listnumber < 1 ||
                $scannedpage->listnumber > $maxlistnumber) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'invalidlistnumber';
        }
    }

    if ($scannedpage->status == 'error' && $scanner->ontop && $autorotate) {
        print_string('rotatingsheet', 'offlinequiz');

        $oldfilename = $scannedpage->filename;
        $corners = $scanner->get_corners();

        if ($newfile = $scanner->rotate_180()) {
            $scannedpage->status = 'ok';
            $scannedpage->error = '';
            $scannedpage->userkey = null;
            $scannedpage->pagenumber = null;
            $scannedpage->groupnumber = null;
            $scannedpage->filename = $newfile->get_filename();
            $corners = $scanner->get_corners();
            $newcorners = array();
            $newcorners[0] = new oq_point(853 - $corners[3]->x, 1208 - $corners[3]->y);
            $newcorners[1] = new oq_point(853 - $corners[2]->x, 1208 - $corners[2]->y);
            $newcorners[2] = new oq_point(853 - $corners[1]->x, 1208 - $corners[1]->y);
            $newcorners[3] = new oq_point(853 - $corners[0]->x, 1208 - $corners[0]->y);

            // Create a completely new scanner.
            $scanner = new offlinequiz_participants_scanner($offlinequiz, $scanner->contextid, 0, 0);

            $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $newcorners);
            if (!$sheetloaded) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'fatalerror';
            } else {
                $scannedpage->status = 'ok';
                $scannedpage->error = '';

                $listnumber = $scanner->get_list();

                if (is_string($listnumber)) {
                    $intln = intval($listnumber);
                    if ($intln > 0) {
                        $listnumber = $intln;
                    }
                }
                $scannedpage->listnumber = $listnumber;
                $maxlistnumber = $DB->get_field_sql("SELECT MAX(number)
                        FROM {offlinequiz_p_lists}
                        WHERE offlinequizid = :offlinequizid",
                        array('offlinequizid' => $offlinequiz->id));
                if ((!is_int($scannedpage->listnumber)) ||
                    $scannedpage->listnumber < 1 ||
                    $scannedpage->listnumber > $maxlistnumber) {

                    $scannedpage->status = 'error';
                    $scannedpage->error = 'invalidlistnumber';
                }
            }
        }
    }

    $scannedpage->time = time();
    if (property_exists($scannedpage, 'id') && !empty($scannedpage->id)) {
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
    } else {
        $scannedpage->id = $DB->insert_record('offlinequiz_scanned_p_pages', $scannedpage);
    }

    if ($autorotate) {
        return array($scanner, $scannedpage);
    } else {
        return $scannedpage;
    }
}

/**
 * Processes the markings on a scanned list of paritipants page
 *
 * @param unknown_type $offlinequiz
 * @param offlinequiz_participants_scanner $scanner
 * @param unknown_type $scannedpage
 * @param unknown_type $teacherid
 * @param unknown_type $coursecontext
 * @return unknown
 */
function offlinequiz_process_scanned_participants_page($offlinequiz, offlinequiz_participants_scanner $scanner,
                                                       $scannedpage, $teacherid, $coursecontext) {
    global $DB;

    // Check the participants entries.
    $participants = $scanner->get_participants();
    if (!property_exists($scannedpage, 'participants') || empty($scannedpage->participants)) {
        $scannedpage->participants = $participants;
    }

    $insecuremarkings = false;
    if (!empty($participants)) {
        foreach ($participants as $participant) {
            $choice = new StdClass();
            $choice->scannedppageid = $scannedpage->id;
            $choice->userid = $participant->userid;

            if ($participant->value == 'unknown'  || $participant->userid == 0) {
                $choice->value = -1;
                $insecuremarkings = true;
            } else if ($participant->value == 'marked') {
                $choice->value = 1;
            } else {
                $choice->value = 0;
            }

            if (is_string($participant->userid) && $intuserid = intval($participant->userid)) {
                $participant->userid = $intuserid;
            }

            if ($participant->userid != false) {
                $choice->userid = $participant->userid;
            } else {
                $choice->userid = 0;
            }
            // We really want to save every single choice in the database.
            if ($choice->userid) {
                if (!$oldchoice = $DB->get_record('offlinequiz_p_choices',
                                                  array('scannedppageid' => $scannedpage->id, 'userid' => $choice->userid))) {
                    $choice->id = $DB->insert_record('offlinequiz_p_choices', $choice);
                } else {
                    $DB->set_field('offlinequiz_p_choices', 'value', $choice->value, array('id' => $oldchoice->id));
                }
            }
        }
    }

    if ($scannedpage->status == 'ok' && $insecuremarkings) {
        $scannedpage->status = 'error';
        $scannedpage->error = 'insecuremarkings';
        $scannedpage->time = time();
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
    }

    // Check if all users are in the offlinequiz_p_list.
    if ($scannedpage->status == 'ok') {

        $list = $DB->get_record('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id,
                'number' => $scannedpage->listnumber));
        $userdata = $DB->get_records('offlinequiz_participants', array('listid' => $list->id));
        // Index the user data by userid.
        $users = array();
        foreach ($userdata as $user) {
            $users[$user->userid] = $user->userid;
        }
        foreach ($participants as $participant) {
            if ($participant->userid > 0 && empty($users[$participant->userid])) {
                $scannedpage->status = 'error';
                $scannedpage->error = 'usernotinlist';
                $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
            }
        }
    }
    return $scannedpage;
}

/**
 * Submits the markings on a scanned list of participants page, i.e. sets 'checked' field of the users
 * according to the markings.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $scannedpage
 * @param unknown_type $choicesdata
 * @return unknown
 */
function offlinequiz_submit_scanned_participants_page($offlinequiz, $scannedpage, $choicesdata) {
    global $DB;

    if (!$list = $DB->get_record('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id,
            'number' => $scannedpage->listnumber))) {
            print_error('missing list ' . $scannedpage->listnumber);
    }
    if (!$userdata = $DB->get_records('offlinequiz_participants', array('listid' => $list->id))) {
        print_error('missing userdata');
    }

    // Index the user data by userid.
    $users = array();
    foreach ($userdata as $user) {
        $users[$user->userid] = $user->userid;
    }

    foreach ($choicesdata as $choice) {
        if ($choice->value == 1) {
            $DB->set_field('offlinequiz_participants', 'checked', 1, array('userid' => $choice->userid, 'listid' => $list->id));
            // The following makes sure that the output is sent immediately.
            @flush();@ob_flush();
        }
    }
    $scannedpage->status = 'submitted';
    $scannedpage->error = '';
    $scannedpage->time = time();
    $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
    return $scannedpage;

}
