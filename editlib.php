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
 * This contains functions that are called from within the offlinequiz module only
 * Functions that are also called by core Moodle are in {@link lib.php}
 * This script also loads the code in {@link questionlib.php} which holds
 * the module-independent code for handling questions and which in turn
 * initialises all the questiontype classes.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

define('NUM_QS_TO_SHOW_IN_RANDOM', 3);

/**
 * Add a question to a offlinequiz group
 *
 * Adds a question to a offlinequiz by updating $offlinequiz as well as the
 * offlinequiz and offlinequiz_question_instances tables. It also adds a page break
 * if required.
 * @param int $id The id of the question to be added
 * @param object $offlinequiz The extended offlinequiz object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in offlinequiz to add the question on. If 0 (default),
 *      add at the end
 * @return bool false if the question was already in the offlinequiz
 */
function offlinequiz_add_question_to_group($id, $offlinequiz, $page = 0) {
    global $DB;

    $questions = explode(',', offlinequiz_clean_layout($offlinequiz->questions));

    // Don't add the same question twice
    if (in_array($id, $questions)) {
        return false;
    }

    // Remove ending page break if it is not needed
    if ($breaks = array_keys($questions, 0)) {
        // Determine location of the last two page breaks
        $end = end($breaks);
        $last = prev($breaks);
        $last = $last ? $last : -1;
        if (!$offlinequiz->questionsperpage || (($end - $last - 1) < $offlinequiz->questionsperpage)) {
            array_pop($questions);
        }
    }
    if (is_int($page) && $page >= 1) {
        $numofpages = offlinequiz_number_of_pages($offlinequiz->questions);
        if ($numofpages<$page) {
            // The page specified does not exist in offlinequiz
            $page = 0;
        } else {
            // Add ending page break - the following logic requires doing this at this point
            $questions[] = 0;
            $currentpage = 1;
            $addnow = false;
            foreach ($questions as $question) {
                if ($question == 0) {
                    $currentpage++;
                    // The current page is the one after the one we want to add on,
                    // so we add the question before adding the current page.
                    if ($currentpage == $page + 1) {
                        $questions_new[] = $id;
                    }
                }
                $questions_new[] = $question;
            }
            $questions = $questions_new;
        }
    }
    if ($page == 0) {
        // add question
        $questions[] = $id;
        // add ending page break
        $questions[] = 0;
    }

    // Save new questionslist in database
    $offlinequiz->questions = implode(',', $questions);

    offlinequiz_save_questions($offlinequiz);

    // Only add a new question instance if there isn't already one.
    if (!$instance = $DB->get_record('offlinequiz_q_instances', array('offlinequizid' => $offlinequiz->id, 'questionid' => $id))) {
        // Add the new question instance if it doesn't already exist
        $instance = new stdClass();
        $instance->offlinequizid = $offlinequiz->id;
        $instance->questionid = $id;
        $instance->grade = $DB->get_field('question', 'defaultmark', array('id' => $id));

        $DB->insert_record('offlinequiz_q_instances', $instance);
    }
}

/**
 * Add a question to a offlinequiz group
 *
 * Adds a question to a offlinequiz by updating $offlinequiz as well as the
 * offlinequiz and offlinequiz_question_instances tables. It also adds a page break
 * if required.
 * @param int $id The id of the question to be added
 * @param object $offlinequiz The extended offlinequiz object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in offlinequiz to add the question on. If 0 (default),
 *      add at the end
 * @return bool false if the question was already in the offlinequiz
 */
function offlinequiz_add_questionlist_to_group($questionids, $offlinequiz, $page = 0) {
    global $DB;

    $questions = explode(',', offlinequiz_clean_layout($offlinequiz->questions));

    // don't add the same question twice
    foreach ($questionids as $id) {
        if (in_array($id, $questions)) {
            continue;
        }

        // remove ending page break if it is not needed
        if ($breaks = array_keys($questions, 0)) {
            // determine location of the last two page breaks
            $end = end($breaks);
            $last = prev($breaks);
            $last = $last ? $last : -1;
            if (!$offlinequiz->questionsperpage || (($end - $last - 1) < $offlinequiz->questionsperpage)) {
                array_pop($questions);
            }
        }
        if (is_int($page) && $page >= 1) {
            $numofpages = offlinequiz_number_of_pages($offlinequiz->questions);
            if ($numofpages<$page) {
                // The page specified does not exist in offlinequiz
                $page = 0;
            } else {
                // add ending page break - the following logic requires doing this at this point
                $questions[] = 0;
                $currentpage = 1;
                $addnow = false;
                foreach ($questions as $question) {
                    if ($question == 0) {
                        $currentpage++;
                        // The current page is the one after the one we want to add on,
                        // so we add the question before adding the current page.
                        if ($currentpage == $page + 1) {
                            $questions_new[] = $id;
                        }
                    }
                    $questions_new[] = $question;
                }
                $questions = $questions_new;
            }
        }
        if ($page == 0) {
            // add question
            $questions[] = $id;
            // add ending page break
            $questions[] = 0;
        }
        // Only add a new question instance if there isn't already one.
        if (!$instance = $DB->get_record('offlinequiz_q_instances', array('offlinequizid' => $offlinequiz->id, 'questionid' => $id))) {
            // Add the new question instance if it doesn't already exist
            $instance = new stdClass();
            $instance->offlinequizid = $offlinequiz->id;
            $instance->questionid = $id;
            $instance->grade = $DB->get_field('question', 'defaultmark', array('id' => $id));

            $DB->insert_record('offlinequiz_q_instances', $instance);
        }
    }

    // Save new questionslist in database
    $offlinequiz->questions = implode(',', $questions);

    offlinequiz_save_questions($offlinequiz);
}

/**
 * Remove a question from a offlinequiz
 * @param object $offlinequiz the offlinequiz object.
 * @param int $questionid The id of the question to be deleted.
 */
function offlinequiz_remove_question($offlinequiz, $questionid) {
    global $DB;

    $questionids = explode(',', $offlinequiz->questions);
    $key = array_search($questionid, $questionids);
    if ($key === false) {
        return;
    }

    unset($questionids[$key]);

    offlinequiz_save_questions($offlinequiz, $questionids);

    $offlinequiz->questions = implode(',', $questionids);

    $otherusagesql = 'SELECT id FROM {offlinequiz_group_questions}
                       WHERE offlinequizid = :offlinequizid
                         AND questionid = :questionid
                         AND offlinegroupid <> :groupid';
    $params = array('offlinequizid' => $offlinequiz->id, 'questionid' => $questionid, 'groupid' => $offlinequiz->groupid);

    $otherusages = $DB->get_records_sql($otherusagesql, $params);

    // Question instances can only be deleted if the question is not used in any offlinequiz group
    if (!$otherusages) {
        $DB->delete_records('offlinequiz_q_instances',
                array('offlinequizid' => $offlinequiz->id, 'questionid' => $questionid));
    }
}

function offlinequiz_remove_questionlist($offlinequiz, $questionids) {
    global $DB;

    $quizquestions = explode(',', $offlinequiz->questions);

    foreach ($questionids as $questionid) {
        $key = array_search($questionid, $quizquestions);
        if ($key === false) {
            continue;
        }
        unset($quizquestions[$key]);

        $otherusagesql = 'SELECT id FROM {offlinequiz_group_questions}
                           WHERE offlinequizid = :offlinequizid
                             AND questionid = :questionid
                             AND offlinegroupid <> :groupid';
        $params = array('offlinequizid' => $offlinequiz->id, 'questionid' => $questionid, 'groupid' => $offlinequiz->groupid);

        $otherusages = $DB->get_records_sql($otherusagesql, $params);

        // Question instances can only be deleted if the question is not used in any offlinequiz group
        if (!$otherusages) {
            $DB->delete_records('offlinequiz_q_instances',
                    array('offlinequizid' => $offlinequiz->id, 'questionid' => $questionid));
        }

    }
    $offlinequiz->questions = implode(',', $quizquestions);

    offlinequiz_save_questions($offlinequiz, $quizquestions);
}


/**
 * Remove an empty page from the offlinequiz layout. If that is not possible, do nothing.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $index the position into $layout where the empty page should be removed.
 * @return the updated layout
 */
function offlinequiz_delete_empty_page($layout, $index) {
    $questionids = explode(',', $layout);

    if ($index < -1 || $index >= count($questionids) - 1) {
        return $layout;
    }

    if (($index >= 0 && $questionids[$index] != 0) || $questionids[$index + 1] != 0) {
        return $layout; // This was not an empty page.
    }

    unset($questionids[$index + 1]);

    return implode(',', $questionids);
}

/**
 * Add a question to a offlinequiz
 *
 * Adds a question to a offlinequiz by updating $offlinequiz as well as the
 * offlinequiz and offlinequiz_question_instances tables. It also adds a page break
 * if required.
 * @param int $id The id of the question to be added
 * @param object $offlinequiz The extended offlinequiz object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in offlinequiz to add the question on. If 0 (default),
 *      add at the end
 * @return bool false if the question was already in the offlinequiz
 */
function offlinequiz_add_offlinequiz_question($id, $offlinequiz, $page = 0) {
    global $DB;
    $questions = explode(',', offlinequiz_clean_layout($offlinequiz->questions));
    if (in_array($id, $questions)) {
        return false;
    }

    // remove ending page break if it is not needed
    if ($breaks = array_keys($questions, 0)) {
        // determine location of the last two page breaks
        $end = end($breaks);
        $last = prev($breaks);
        $last = $last ? $last : -1;
        if (!$offlinequiz->questionsperpage || (($end - $last - 1) < $offlinequiz->questionsperpage)) {
            array_pop($questions);
        }
    }
    if (is_int($page) && $page >= 1) {
        $numofpages = offlinequiz_number_of_pages($offlinequiz->questions);
        if ($numofpages<$page) {
            // the page specified does not exist in offlinequiz
            $page = 0;
        } else {
            // add ending page break - the following logic requires doing
            // this at this point
            $questions[] = 0;
            $currentpage = 1;
            $addnow = false;
            foreach ($questions as $question) {
                if ($question == 0) {
                    $currentpage++;
                    // The current page is the one after the one we want to add on,
                    // so we add the question before adding the current page.
                    if ($currentpage == $page + 1) {
                        $questions_new[] = $id;
                    }
                }
                $questions_new[] = $question;
            }
            $questions = $questions_new;
        }
    }
    if ($page == 0) {
        // add question
        $questions[] = $id;
        // add ending page break
        $questions[] = 0;
    }

    // Save new questionslist in database
    $offlinequiz->questions = implode(',', $questions);
    offlinequiz_save_questions($offlinequiz);

    // Add the new question instance.
    $instance = new stdClass();
    $instance->offlinequizid = $offlinequiz->id;
    $instance->questionid = $id;
    $instance->grade = $DB->get_field('question', 'defaultmark', array('id' => $id));
    $DB->insert_record('offlinequiz_q_instances', $instance);
}

/**
 * Randomly add a number of multichoice questions to an offlinequiz group.
 * 
 * @param unknown_type $offlinequiz
 * @param unknown_type $addonpage
 * @param unknown_type $categoryid
 * @param unknown_type $number
 * @param unknown_type $includesubcategories
 */
function offlinequiz_add_random_questions($offlinequiz, $addonpage, $categoryid, $number, $recurse) {
    global $DB;

    $category = $DB->get_record('question_categories', array('id' => $categoryid));
    if (!$category) {
        print_error('invalidcategoryid', 'error');
    }

    $catcontext = context::instance_by_id($category->contextid);
    require_capability('moodle/question:useall', $catcontext);

    if ($recurse) {
        $categoryids = question_categorylist($category->id);
    } else {
        $categoryids = array($category->id);
    }

    list($qcsql, $qcparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'qc');

    $sql = "SELECT id
              FROM {question} q
             WHERE q.category $qcsql
               AND q.parent = 0
               AND q.hidden = 0
               AND q.qtype IN ('multichoice', 'multichoiceset')
               AND NOT EXISTS (SELECT 1 
                                 FROM {offlinequiz_q_instances} oqi
                                WHERE oqi.questionid = q.id
                                  AND oqi.offlinequizid = :offlinequizid)";
    
    $qcparams['offlinequizid'] = $offlinequiz->id;
    
    $questionids = $DB->get_fieldset_sql($sql, $qcparams);
    srand(microtime() * 1000000);
    shuffle($questionids);
    
    $chosenids = array();
    while (($questionid = array_shift($questionids)) && $number > 0) {
        $chosenids[] = $questionid;
        $number -= 1;
    }
    offlinequiz_add_questionlist_to_group($chosenids, $offlinequiz);
}

/**
 * Add a page break after at particular position$.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $index the position into $layout where the empty page should be removed.
 * @return the updated layout
 */
function offlinequiz_add_page_break_at($layout, $index) {
    $questionids = explode(',', $layout);
    if ($index < 0 || $index >= count($questionids)) {
        return $layout;
    }

    array_splice($questionids, $index, 0, '0');

    return implode(',', $questionids);
}

/**
 * Add a page break after a particular question.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $qustionid the question to add the page break after.
 * @return the updated layout
 */
function offlinequiz_add_page_break_after($layout, $questionid) {
    $questionids = explode(',', $layout);
    $key = array_search($questionid, $questionids);
    if ($key === false || !$questionid) {
        return $layout;
    }

    array_splice($questionids, $key + 1, 0, '0');

    return implode(',', $questionids);
}

/**
 * Remove a page break after a particular question if it exists.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $qustionid the question to add the page break after.
 * @return the updated layout
 */
function offlinequiz_remove_page_break_after($layout, $questionid) {
    $questionids = explode(',', $layout);
    $key = array_search($questionid, $questionids);
    if ($key === false || !$questionid) {
        return $layout;
    }
    // Only remove page break if it is not the last one.
    if (intval($questionids[$key + 1]) == 0 && ($key - 1 < count($questionids))) {
        unset($questionids[$key + 1]);
    }
    return implode(',', $questionids);
}

/**
 * Save changes to a question instance
 *
 * Saves changes to the question grades in the offlinequiz_question_instances table.
 * It does not update 'sumgrades' in the offlinequiz table.
 *
 * @param int grade    The maximal grade for the question
 * @param int $questionid  The id of the question
 * @param int $offlinequizid  The id of the offlinequiz to update / add the instances for.
 */
function offlinequiz_update_question_instance($grade, $questionid, $offlinequiz) {
    global $DB;

    $instance = $DB->get_record('offlinequiz_q_instances', array('offlinequizid' => $offlinequiz->id,
            'questionid' => $questionid));

    if (!empty($instance)) {

        if (abs($grade - $instance->grade) < 1e-7) {
            // Grade has not changed. Nothing to do.
            return;
        }

        $instance->grade = $grade;
        $DB->update_record('offlinequiz_q_instances', $instance);

        $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups);

        foreach ($groups as $group) {

            if ($group->templateusageid) {
                $templateusage = question_engine::load_questions_usage_by_activity($group->templateusageid);
                $slots = $templateusage->get_slots();

                $slot = 0;
                foreach ($slots as $thisslot) {
                    if ($templateusage->get_question($thisslot)->id == $questionid) {
                        $slot = $thisslot;
                        break;
                    }
                }
                if ($slot) {
                    // update the grade in the template usage
                    question_engine::set_max_mark_in_attempts(new qubaid_list(array($group->templateusageid)), $slot, $grade);

                    /*
                     // Update the grade in the student attempts/results
                    // NOTE: The following created very inefficient subqueries in MySQL and had to be replaced by our own code.
                    question_engine::set_max_mark_in_attempts(new result_qubaids_for_offlinequiz($offlinequiz->id, $group->id), $slot, $grade);
                    */

                    // First get the IDs of the question usages that correspond to the results in this group.
                    $results = $DB->get_records('offlinequiz_results',
                            array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id));
                    $qubaids = array();

                    foreach ($results as $result) {
                        if ($result->usageid > 0) {
                            $qubaids[] = $result->usageid;
                        }
                    }

                    if (!empty($qubaids)) {
                        list($usql, $params) = $DB->get_in_or_equal($qubaids, SQL_PARAMS_NAMED, 'quba');

                        // Then update only those IDs. Hopefully, there is an index on the field questionusageid.
                        $sql = "UPDATE {question_attempts}
                                   SET maxmark = :maxmark
                                 WHERE questionusageid $usql
                                   AND slot = :slot";

                        $params['slot'] = $slot;
                        $params['maxmark'] = $grade;

                        $DB->execute($sql, $params);
                    }

                    // Now change the sumgrades in the offlinequiz results.
                    foreach ($results as $result) {
                        if ($result->usageid > 0) {
                            $resultusage = question_engine::load_questions_usage_by_activity($result->usageid);
                            $DB->set_field('offlinequiz_results', 'sumgrades', $resultusage->get_total_mark(),
                                    array('id' => $result->id));
                        }
                    }
                }
            }
        }
    }
}

// Private function used by the following two.
function _offlinequiz_move_question($layout, $questionid, $shift) {
    if (!$questionid || !($shift == 1 || $shift == -1)) {
        return $layout;
    }

    $questionids = explode(',', $layout);
    $key = array_search($questionid, $questionids);
    if ($key === false) {
        return $layout;
    }

    $otherkey = $key + $shift;
    if ($otherkey < 0 || $otherkey >= count($questionids) - 1) {
        return $layout;
    }

    $temp = $questionids[$otherkey];
    $questionids[$otherkey] = $questionids[$key];
    $questionids[$key] = $temp;

    return implode(',', $questionids);
}

/**
 * Move a particular question one space earlier in the $offlinequiz->questions list.
 * If that is not possible, do nothing.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $questionid the id of a question.
 * @return the updated layout
 */
function offlinequiz_move_question_up($layout, $questionid) {
    return _offlinequiz_move_question($layout, $questionid, -1);
}

/**
 * Move a particular question one space later in the $offlinequiz->questions list.
 * If that is not possible, do nothing.
 * @param string $layout the existinng layout, $offlinequiz->questions.
 * @param int $questionid the id of a question.
 * @return the updated layout
 */
function offlinequiz_move_question_down($layout, $questionid) {
    return _offlinequiz_move_question($layout, $questionid, +1);
}

/**
 * Prints a list of offlinequiz questions for the edit.php main view for edit
 * ($reordertool = false) and order and paging ($reordertool = true) tabs
 *
 * @return int sum of maximum grades
 * @param object $offlinequiz This is not the standard offlinequiz object used elsewhere but
 *     it contains the offlinequiz layout in $offlinequiz->questions and the grades in
 *     $offlinequiz->grades
 * @param object $pageurl The url of the current page with the parameters required
 *     for links returning to the current page, as a moodle_url object
 * @param bool $allowdelete Indicates whether the delete icons should be displayed
 * @param bool $reordertool  Indicates whether the reorder tool should be displayed
 * @param bool $offlinequiz_qbanktool  Indicates whether the question bank should be displayed
 * @param bool $hasattempts  Indicates whether the offlinequiz has attempts
 */
function offlinequiz_print_question_list($offlinequiz, $pageurl, $allowdelete, $reordertool, $gradetool, $offlinequiz_qbanktool, $hasattempts, $defaultcategoryobj) {
    global $CFG, $DB, $OUTPUT;

    $strorder = get_string('order');
    $strquestionname = get_string('questionname', 'question');
    $strgrade = get_string('grade');
    $strremove = get_string('remove', 'offlinequiz');
    $stredit = get_string('edit');
    $strview = get_string('view');
    $straction = get_string('action');
    $strmove = get_string('move');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $strcopytogroup = get_string('add');
    $strsave = get_string('save', 'offlinequiz');
    $strbulksave = get_string('bulksavegrades', 'offlinequiz');
    $strreorderquestions = get_string('reorderquestions', 'offlinequiz');

    $strselectall = get_string('selectall', 'offlinequiz');
    $strselectnone = get_string('selectnone', 'offlinequiz');
    $strtype = get_string('type', 'offlinequiz');
    $strpreview = get_string('preview', 'offlinequiz');

    if ($offlinequiz->questions) {
        list($usql, $params) = $DB->get_in_or_equal(explode(',', $offlinequiz->questions));
        $params[] = $offlinequiz->id;
        $questions = $DB->get_records_sql("SELECT q.*, qc.contextid, qqi.grade as maxmark
                                             FROM {question} q
                                             JOIN {question_categories} qc ON qc.id = q.category
                                             JOIN {offlinequiz_q_instances} qqi ON qqi.questionid = q.id
                                            WHERE q.id $usql AND qqi.offlinequizid = ?", $params);
    } else {
        $questions = array();
    }

    $layout = $offlinequiz->questions;
    $order = explode(',', $layout);
    $lastindex = count($order) - 1;

    $disabled = '';
    $pagingdisabled = '';
    $copyingdisabled = '';

    if ($hasattempts) {
        $disabled = 'disabled="disabled"';
    }
    if ($hasattempts || $offlinequiz->shufflequestions) {
        $pagingdisabled = 'disabled="disabled"';
    }
    if (($hasattempts) || ($offlinequiz->numgroups == 1)) {
        $copyingdisabled = 'disabled="disabled"';
    }

    $reordercontrolssetdefaultsubmit = '<div style="display:none;">' .
            '<input type="submit" name="savechanges" value="' .
            $editcontrolssetdefaultsubmit = '<div style="display:none;">' .
            '<input type="submit" name="savechanges" value="' .

            $strreorderquestions . '" ' . $pagingdisabled . ' /></div>';
    $reordercontrols1 = '<div class="deletepagesafterselected">' .
            '<input type="submit" name="deletepagesafterselected" value="' .
            get_string('deletepagesafterselected', 'offlinequiz') . '"  ' .
            $pagingdisabled . ' /></div>';
    $reordercontrols1 .= '<div class="addnewpagesafterselected">' .
            '<input type="submit" name="addnewpagesafterselected" value="' .
            get_string('addnewpagesafterselected', 'offlinequiz') . '"  ' .
            $pagingdisabled . ' /></div>';
    $reordercontrols1 .= '<div class="offlinequizdeleteselected">' .
            '<input type="submit" name="offlinequizdeleteselected" ' .
            'onclick="return confirm(\'' .
            get_string('areyousureremoveselected', 'offlinequiz') . '\');" value="' .
            get_string('removeselected', 'offlinequiz') . '"  ' . $disabled . ' /></div>';

    $a = '<input name="moveselectedonpagetop" type="text" size="2" ' .
            $pagingdisabled . ' />';
    $b = '<input name="moveselectedonpagebottom" type="text" size="2" ' .
            $pagingdisabled . ' />';

    $c = '';
    $d = '';
    $e = '';
    $f = '';

    $letterstr = 'ABCDEFGHIJKL';

    $c = '<select name="copyselectedtogrouptop" ' . $copyingdisabled . '>';
    $d = '<select name="copyselectedtogroupbottom" ' . $copyingdisabled . '>';
    $e = '<select name="copytogrouptop" ' . $copyingdisabled . '>';
    $f = '<select name="copytogroupbottom" ' . $copyingdisabled . '>';

    $c .= '<option value="0">' . get_string('selectagroup', 'offlinequiz') . '</option>';
    $d .= '<option value="0">' . get_string('selectagroup', 'offlinequiz') . '</option>';
    $e .= '<option value="0">' . get_string('selectagroup', 'offlinequiz') . '</option>';
    $f .= '<option value="0">' . get_string('selectagroup', 'offlinequiz') . '</option>';

    if ($offlinequiz->numgroups > 1) {
        for ($i=1; $i<=$offlinequiz->numgroups; $i++) {
            if ($i != $offlinequiz->groupnumber) {
                $c .= '<option value="' . $i . '">' . $letterstr[$i-1] . '</option>';
                $d .= '<option value="' . $i . '">' . $letterstr[$i-1] . '</option>';
                $e .= '<option value="' . $i . '">' . $letterstr[$i-1] . '</option>';
                $f .= '<option value="' . $i . '">' . $letterstr[$i-1] . '</option>';
            }
        }
    }
    $c .= '</select>';
    $d .= '</select>';
    $e .= '</select>';
    $f .= '</select>';

    // Add the controls in case we are in reorder and paging mode.
    $reordercontrols2top = '<div class="moveselectedonpage">';

    $reordercontrols2top .= get_string('copyselectedtogroup', 'offlinequiz', $c) .
            '<input type="submit" name="savechanges" value="' .
            $strcopytogroup . '" ' . $copyingdisabled . '/><br/>' .
            get_string('moveselectedonpage', 'offlinequiz', $a) .
            '<input type="submit" name="savechanges" value="' .
            $strmove . '"  ' . $pagingdisabled . ' />' . '
            <br /><input type="submit" name="savechanges" value="' .
            $strreorderquestions . '" ' . $pagingdisabled . '/>' .
            '</div>';

    $reordercontrols2bottom = '<div class="moveselectedonpage">' .
            '<input type="submit" name="savechanges" value="' .
            $strreorderquestions . '"  ' . $pagingdisabled . '/><br />' .

            get_string('moveselectedonpage', 'offlinequiz', $b) .
            '<input type="submit" name="savechanges" value="' .
            $strmove . '"  ' . $pagingdisabled . ' /><br/>' .

            get_string('copyselectedtogroup', 'offlinequiz', $d) .
            '<input type="submit" name="savechanges" value="' .
            $strcopytogroup . '" ' . $copyingdisabled . ' />' .
            '</div>';

    $reordercontrols3 = '';
    if (!$pagingdisabled || !$copyingdisabled) {
        $reordercontrols3 = '<a href="javascript:select_all_in(\'FORM\', null, ' .
                '\'offlinequizquestions\');" >' .
                $strselectall . '</a> /';
        $reordercontrols3.=    ' <a href="javascript:deselect_all_in(\'FORM\', ' .
                'null, \'offlinequizquestions\');">' .
                $strselectnone . '</a>';
    }

    $reordercontrolstop = '<div class="reordercontrols">' .
            $reordercontrolssetdefaultsubmit .
            $reordercontrols1 . $reordercontrols2top . $reordercontrols3 . "</div>";

    $reordercontrolsbottom = '<div class="reordercontrols">' .
            $reordercontrolssetdefaultsubmit .
            $reordercontrols1 . $reordercontrols3 . $reordercontrols2bottom . "</div>";

    // Add the controls in case we are in question list editing mode
    $editcontrols2top = '';
    $editcontrols2top = '<div class="moveselectedonpage">';
    $editcontrols2top .= get_string('copytogroup', 'offlinequiz', $e);
    $editcontrols2top .= '<input type="submit" name="savechanges" value="' .
                 $strcopytogroup . '" ' . $copyingdisabled . '/>' . '</div>';

    $editcontrols2bottom = '';
    $editcontrols2bottom = '<div class="moveselectedonpage">';
    $editcontrols2bottom .= get_string('copytogroup', 'offlinequiz', $f);
    $editcontrols2bottom .= '<input type="submit" name="savechanges" value="' .
            $strcopytogroup . '" ' . $copyingdisabled . ' />' . '</div>';

    $editcontrolstop = '<div class="editcontrols">' .
            $editcontrols2top . "</div>";

    $editcontrolsbottom = '<div class="editcontrols">' .
            $editcontrols2bottom . "</div>";

    if ($reordertool) {
        echo '<form method="post" action="edit.php" id="offlinequizquestions"><div>';
        echo html_writer::input_hidden_params($pageurl);
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo $reordercontrolstop;
    } if (!$gradetool) {
        echo '<form method="post" action="edit.php" id="offlinequizquestions"><div>';
        echo html_writer::input_hidden_params($pageurl);
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo $editcontrolstop;
        echo '</div></form>';
    }

    // The current question ordinal (no descriptions).
    $qno = 1;
    // The current question (includes questions and descriptions).
    $questioncount = 0;
    // The current page number in iteration.
    $pagecount = 0;

    $pageopen = false;

    $returnurl = str_replace($CFG->wwwroot, '', $pageurl->out(false));
    $questiontotalcount = count($order);

    if ($gradetool) {
        echo '<form method="post" action="edit.php" class="offlinequizbulkgradesform">
                <input type="hidden" name="sesskey" value="' . sesskey() . '" />' .
                html_writer::input_hidden_params($pageurl) .
                '<input type="hidden" name="savechanges" value="save" />';
    }

    foreach ($order as $count => $qnum) {

        $reordercheckbox = '';
        $reordercheckboxlabel = '';
        $reordercheckboxlabelclose = '';

        // If the questiontype is missing change the question type
        if ($qnum && !array_key_exists($qnum, $questions)) {
            $fakequestion = new stdClass();
            $fakequestion->id = $qnum;
            $fakequestion->category = 0;
            $fakequestion->qtype = 'missingtype';
            $fakequestion->name = get_string('missingquestion', 'offlinequiz');
            $fakequestion->questiontext = ' ';
            $fakequestion->questiontextformat = FORMAT_HTML;
            $fakequestion->length = 1;
            $questions[$qnum] = $fakequestion;
            $offlinequiz->grades[$qnum] = 0;

        } else if ($qnum && !question_bank::qtype_exists($questions[$qnum]->qtype)) {
            $questions[$qnum]->qtype = 'missingtype';
        }

        if ($qnum != 0 || ($qnum == 0 && !$pageopen)) {
            // This is either a question or a page break after another
            //        (no page is currently open).
            if (!$pageopen) {
                // If no page is open, start display of a page.
                $pagecount++;
                echo  '<div class="offlinequizpage"><span class="pagetitle">' .
                        get_string('page') . '&nbsp;' . $pagecount .
                        '</span><div class="pagecontent">';
                $pageopen = true;
            }
            if ($qnum == 0  && $count < $questiontotalcount) {
                // This is the second successive page break. Tell the user the page is empty.
                echo '<div class="pagestatus">';
                print_string('noquestionsonpage', 'offlinequiz');
                echo '</div>';
                if ($allowdelete) {
                    echo '<div class="offlinequizpagedelete">';
                    echo $OUTPUT->action_icon($pageurl->out(true,
                            array('deleteemptypage' => $count - 1, 'sesskey'=>sesskey())),
                            new pix_icon('t/delete', $strremove),
                            new component_action('click',
                                    'M.core_scroll_manager.save_scroll_action'),
                            array('title' => $strremove));
                    echo '</div>';
                }
            }

            if ($qnum != 0) {
                $question = $questions[$qnum];
                $questionparams = array(
                        'returnurl' => $returnurl,
                        'cmid' => $offlinequiz->cmid,
                        'id' => $question->id);
                $questionurl = new moodle_url('/question/question.php',
                        $questionparams);
                $questioncount++;
                // This is an actual question.

                /* Display question start */
                echo '<div class="question">';
                echo '  <div class="questioncontainer ' . $question->qtype . '">';
                echo '    <div class="qnum">';

                $reordercheckbox = '';
                $reordercheckboxlabel = '';
                $reordercheckboxlabelclose = '';
                if ($reordertool) {
                    $disabled = $pagingdisabled;
                    if (!$copyingdisabled ) {
                        $disabled = '';
                    }
                    $reordercheckbox = '<input type="checkbox" name="s' . $question->id .
                    '" id="s' . $question->id . '" ' . $disabled . ' />';
                    $reordercheckboxlabel = '<label for="s' . $question->id . '">';
                    $reordercheckboxlabelclose = '</label>';
                }
                if ($question->length == 0) {
                    $qnodisplay = get_string('infoshort', 'offlinequiz');
                } else if ($offlinequiz->shufflequestions) {
                    $qnodisplay = '?';
                } else {
                    if ($qno > 999 || ($reordertool && $qno > 99)) {
                        $qnodisplay = html_writer::tag('small', $qno);
                    } else {
                        $qnodisplay = $qno;
                    }
                    $qno += $question->length;
                }
                echo $reordercheckboxlabel . $qnodisplay . $reordercheckboxlabelclose .
                $reordercheckbox;

                echo '    </div>';
                echo '    <div class="content">';
                echo '    <div class="questioncontrols">';

                if ($count != 0) {
                    if (!$hasattempts) {
                        $upbuttonclass = '';
                        if ($count >= $lastindex - 1) {
                            $upbuttonclass = 'upwithoutdown';
                        }
                        echo $OUTPUT->action_icon($pageurl->out(true,
                                array('up' => $question->id, 'sesskey'=>sesskey())),
                                new pix_icon('t/up', $strmoveup),
                                new component_action('click',
                                        'M.core_scroll_manager.save_scroll_action'),
                                array('title' => $strmoveup));
                    }

                }
                if ($count < $lastindex - 1) {
                    if (!$hasattempts) {
                        echo $OUTPUT->action_icon($pageurl->out(true,
                                array('down' => $question->id, 'sesskey'=>sesskey())),
                                new pix_icon('t/down', $strmovedown),
                                new component_action('click',
                                        'M.core_scroll_manager.save_scroll_action'),
                                array('title' => $strmovedown));
                    }
                }
                if ($allowdelete && ($question->qtype == 'missingtype' ||
                        question_has_capability_on($question, 'use', $question->category))) {
                    // Remove from offlinequiz, not question delete.
                    if (!$hasattempts) {
                        echo $OUTPUT->action_icon($pageurl->out(true,
                                array('remove' => $question->id, 'sesskey'=>sesskey())),
                                new pix_icon('t/delete', $strremove),
                                new component_action('click',
                                        'M.core_scroll_manager.save_scroll_action'),
                                array('title' => $strremove));
                    }
                }
                echo '</div>'; // End div questioncontrols
                if (!in_array($question->qtype, array('description', 'missingtype')) && !$reordertool && !$gradetool) {

                    echo '<div class="points">
                    <form method="post" action="edit.php" class="offlinequizsavegradesform">
                    <div>
                    <fieldset class="invisiblefieldset" style="display: block;">
                    <label for="inputq' . $question->id . '">' . $strgrade . '
                    </label>:<br /> <input type="hidden" name="sesskey"
                    value="' . sesskey() . '" />' . html_writer::input_hidden_params($pageurl) . '
                    <input type="hidden" name="savechanges" value="save" />
                    <input type="text" name="g' . $question->id .
                    '" id="inputq' . $question->id .
                    '" size="' . ($offlinequiz->decimalpoints + 2) .
                    '" value="' . format_float($offlinequiz->grades[$qnum], 2, true, true) .
                    '" tabindex="' . ($lastindex + $qno) . '" />
                    <input type="submit" class="pointssubmitbutton" value="' . $strsave . '" />
                    </fieldset>';
                    echo '      </div>
                    </form>
                    </div>';

                } else if (!in_array($question->qtype, array('description', 'missingtype')) && $gradetool) {
                    echo '<div class="points">
                    <label for="inputq' . $question->id . '">' . $strgrade . '
                    </label>:
                    <input type="text" name="g' . $question->id .
                    '" id="inputq' . $question->id .
                    '" size="' . ($offlinequiz->decimalpoints + 2) .
                    '" value="' . format_float($offlinequiz->grades[$qnum], 2, true, true) .
                    '" tabindex="' . ($lastindex + $qno) . '" />';
                    echo '      </div>';
                    
                } else if ($reordertool) {
                    if ($qnum) {
                        echo '<div class="qorder">';
                        echo '  <input type="text" name="o' . $question->id .
                        '" size="2" value="' . (10*$count + 10) .
                        '" tabindex="' . ($lastindex + $qno) . '" />';
                        echo '</div>';
                    }
                }
                echo '<div class="questioncontentcontainer">';

//                 if ($question->qtype == 'random') { // it is a random question
//                     if (!$reordertool) {
//                         offlinequiz_print_randomquestion($question, $pageurl, $offlinequiz, $offlinequiz_qbanktool);
//                     } else {
//                         offlinequiz_print_randomquestion_reordertool($question, $pageurl, $offlinequiz);
//                     }
//                 } else { // it is a single question
                if ($reordertool) {
                    offlinequiz_print_singlequestion_reordertool($question, $returnurl, $offlinequiz);
                } else if ($gradetool) {
                    offlinequiz_print_singlequestion_gradetool($question, $returnurl, $offlinequiz);
                } else {
                    offlinequiz_print_singlequestion($question, $returnurl, $offlinequiz);
}
//                 }
                echo '            </div></div></div></div>';
            }
        }
        // A page break: end the existing page.
        if ($qnum == 0) {
            if ($pageopen) {
                if (!$reordertool && !$gradetool && !($offlinequiz->shufflequestions &&
                        $count < $questiontotalcount - 1)) {
                    offlinequiz_print_pagecontrols($offlinequiz, $pageurl, $pagecount,
                            $hasattempts, $defaultcategoryobj);
                } else if ($count < $questiontotalcount - 1) {
                    // Do not include the last page break for reordering
                    // To avoid creating a new extra page in the end
                    echo '<input type="hidden" name="opg' . $pagecount . '" size="2" value="' .
                            (10*$count + 10) . '" />';
                }
                echo "</div></div>";

                if (!$reordertool && !$gradetool && !$offlinequiz->shufflequestions) {
                    echo $OUTPUT->container_start('addpage');
                    $url = new moodle_url($pageurl->out_omit_querystring(),
                            array('cmid' => $offlinequiz->cmid, 'courseid' => $offlinequiz->course,
                                    'addpage' => $count, 'sesskey' => sesskey()));
                    echo $OUTPUT->single_button($url, get_string('addpagehere', 'offlinequiz'), 'post',
                            array('disabled' => $hasattempts,
                                    'actions' => array(new component_action('click',
                                            'M.core_scroll_manager.save_scroll_action'))));
                    echo $OUTPUT->container_end();
                }
                $pageopen = false;
                $count++;
            }
        }
    }

    if ($gradetool) {
        echo '<center><input type="submit" class="bulksubmitbutton" value="' . $strbulksave . '" name="bulkgradesubmit" /></center>
        </form>';
    }

    if ($reordertool) {
        echo $reordercontrolsbottom;
        echo '</div></form>';
    } else if (!$gradetool) {
        echo '<form method="post" action="edit.php" id="offlinequizquestions"><div>';
        echo html_writer::input_hidden_params($pageurl);
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo $editcontrolsbottom;
        echo '</div></form>';
    }
}

/**
 * Print all the controls for adding questions directly into the
 * specific page in the edit tab of edit.php
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $pageurl
 * @param unknown_type $page
 * @param unknown_type $hasattempts
 */
function offlinequiz_print_pagecontrols($offlinequiz, $pageurl, $page, $hasattempts, $defaultcategoryobj) {
    global $CFG, $OUTPUT;
    static $randombuttoncount = 0;
    $randombuttoncount++;
    echo '<div class="pagecontrols">';

    // Get the default category.
    list($defaultcategoryid) = explode(',', $pageurl->param('cat'));
    if (empty($defaultcategoryid)) {
        $defaultcategoryid = $defaultcategoryobj->id;
    }

    // Create the url the question page will return to
    $returnurladdtoofflinequiz = new moodle_url($pageurl, array('addonpage' => $page));

    // Print a button linking to the choose question type page.
    $returnurladdtoofflinequiz = str_replace($CFG->wwwroot, '', $returnurladdtoofflinequiz->out(false));
    $newquestionparams = array('returnurl' => $returnurladdtoofflinequiz,
            'cmid' => $offlinequiz->cmid, 'appendqnumstring' => 'addquestion');
    offlinequiz_create_new_question_button($defaultcategoryid, $newquestionparams,
            get_string('addaquestion', 'offlinequiz'),
            get_string('createquestionandadd', 'offlinequiz'), $hasattempts);

    if ($hasattempts) {
        $disabled = 'disabled="disabled"';
    } else {
        $disabled = '';
    }
    echo '
    <div class="singlebutton">
    <form class="randomquestionform"
    action="' . $CFG->wwwroot . '/mod/offlinequiz/addrandom.php"
    method="get">
    <div>
    <input type="hidden" class="addonpage_formelement" name="addonpage" value="' . $page . '" />
    <input type="hidden" name="cmid" value="' . $offlinequiz->cmid . '" />
    <input type="hidden" name="courseid" value="' . $offlinequiz->course . '" />
    <input type="hidden" name="category" value="' . $pageurl->param('cat') . '" />
    <input type="hidden" name="returnurl" value="' . s(str_replace($CFG->wwwroot, '', $pageurl->out(false))) . '" />
    </div>
    </form>
    </div>';
    echo "\n</div>";
}

/**
 * Print a given single question in offlinequiz for the edit tab of edit.php.
 * Meant to be used from offlinequiz_print_question_list()
 *
 * @param object $question A question object from the database questions table
 * @param object $returnurl The url to get back to this page, for example after editing.
 * @param object $offlinequiz The offlinequiz in the context of which the question is being displayed
 */
function offlinequiz_print_singlequestion($question, $returnurl, $offlinequiz) {
    echo '<div class="singlequestion ' . $question->qtype . '">';
    echo offlinequiz_question_edit_button($offlinequiz->cmid, $question, $returnurl,
            offlinequiz_question_tostring($question) . ' ');
    echo '<span class="questiontype">';
    echo print_question_icon($question);
    echo ' ' . question_bank::get_qtype_name($question->qtype) . '</span>';
    echo '<span class="questionpreview">' .
            offlinequiz_question_preview_button($offlinequiz, $question, true) . '</span>';
    echo "</div>\n";
}

/**
 * Print a given single question in offlinequiz for the edit tab of edit.php.
 * Meant to be used from offlinequiz_print_question_list()
 *
 * @param object $question A question object from the database questions table
 * @param object $returnurl The url to get back to this page, for example after editing.
 * @param object $offlinequiz The offlinequiz in the context of which the question is being displayed
 */
function offlinequiz_print_singlequestion_gradetool($question, $returnurl, $offlinequiz) {
    echo '<div class="singlequestion ' . $question->qtype . '">';
    echo offlinequiz_question_edit_button($offlinequiz->cmid, $question, $returnurl,
            offlinequiz_question_tostring($question) . ' ');
    echo '<span class="questiontype">';
    echo print_question_icon($question);
    echo ' ' . question_bank::get_qtype_name($question->qtype) . '</span>';
    echo '<span class="questionpreview">' .
            offlinequiz_question_preview_button($offlinequiz, $question, true) . '</span>';
    echo "</div>\n";
}

/**
 * Print a given single question in offlinequiz for the reordertool tab of edit.php.
 * Meant to be used from offlinequiz_print_question_list()
 *
 * @param object $question A question object from the database questions table
 * @param object $questionurl The url of the question editing page as a moodle_url object
 * @param object $offlinequiz The offlinequiz in the context of which the question is being displayed
 */
function offlinequiz_print_singlequestion_reordertool($question, $returnurl, $offlinequiz) {
    echo '<div class="singlequestion ' . $question->qtype . '">';
    echo '<label for="s' . $question->id . '">';
    echo print_question_icon($question);
    echo ' ' . offlinequiz_question_tostring($question);
    echo '</label>';
    echo '<span class="questionpreview">' .
            offlinequiz_question_action_icons($offlinequiz, $offlinequiz->cmid, $question, $returnurl) . '</span>';
    echo "</div>\n";
}

/**
 * Print an icon to indicate the 'include subcategories' state of a random question.
 * @param $question the random question.
 */
function print_random_option_icon($question) {
    global $OUTPUT;
    if (!empty($question->questiontext)) {
        $icon = 'withsubcat';
        $tooltip = get_string('randomwithsubcat', 'offlinequiz');
    } else {
        $icon = 'nosubcat';
        $tooltip = get_string('randomnosubcat', 'offlinequiz');
    }
    echo '<img src="' . $OUTPUT->pix_url('i/' . $icon) . '" alt="' .
            $tooltip . '" title="' . $tooltip . '" class="uihint" />';
}

/**
 * Creates a textual representation of a question for display.
 *
 * @param object $question A question object from the database questions table
 * @param bool $showicon If true, show the question's icon with the question. False by default.
 * @param bool $showquestiontext If true (default), show question text after question name.
 *       If false, show only question name.
 * @param bool $return If true (default), return the output. If false, print it.
 */
function offlinequiz_question_tostring($question, $showicon = false,
        $showquestiontext = true, $return = true) {
    global $COURSE;

    $result = '';

    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $formatoptions->para = false;
    $questiontext = strip_tags(format_text($question->questiontext, $question->questiontextformat, $formatoptions, $COURSE->id));
    $result .= '<span class="questionname" title="' . $questiontext . '">';
    
    if ($showicon) {
        $result .= print_question_icon($question, true);
        echo ' ';
    }
    $result .= shorten_text(format_string($question->name), 200) . '</span>';
    if ($showquestiontext) {
        $result .= '<span class="questiontext" title="' . $questiontext . '">';

        $questiontext = shorten_text($questiontext, 200);

        if (!empty($questiontext)) {
            $result .= $questiontext;
        } else {
            $result .= '<span class="error">';
            $result .= get_string('questiontextisempty', 'offlinequiz');
            $result .= '</span>';
        }
        $result .= '</span>';
    }
    if ($return) {
        return $result;
    } else {
        echo $result;
    }
}

/**
 * A column type for the name of the question type.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_my_question_type_column extends question_bank_question_type_column {
    public function get_name() {
        return 'myqtype';
    }

    protected function display_content($question, $rowclasses) {
        global $PAGE;
        $qtypename = $question->qtype;
        $qtype = question_bank::get_qtype($qtypename, false);
        $namestr = $qtype->local_name();
        if ($question->hidden) {
            echo $PAGE->get_renderer('question', 'bank')->pix_icon('icon', $namestr, $qtype->plugin_name(),
                    array('title' => $namestr, 'style' => 'opacity: 0.4; filter: alpha(opacity=40); /* msie *//'));
        } else {
            echo $PAGE->get_renderer('question', 'bank')->qtype_icon($question->qtype);
        }
//    echo print_question_icon($question);
    }
}

/**
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_my_checkbox_column extends question_bank_checkbox_column {
    public function get_name() {
        return 'mycheckbox';
    }

    protected function display_content($question, $rowclasses) {
        global $PAGE;
        if ($question->hidden) {
            echo '<input title="' . $this->strselect . '" type="checkbox" name="q' .
                    $question->id . '" id="checkq' . $question->id . '" value="1" disabled="disabled"/>';
        } else {
            echo '<input title="' . $this->strselect . '" type="checkbox" name="q' .
                    $question->id . '" id="checkq' . $question->id . '" value="1"/>';
        }
        if ($this->firstrow) {
            $PAGE->requires->js('/question/qengine.js');
            $module = array(
                'name'      => 'qbank',
                'fullpath'  => '/question/qbank.js',
                'requires'  => array('yui2-dom', 'yui2-event', 'yui2-container'),
                'strings'   => array(),
                'async'     => false,
            );
            $PAGE->requires->js_init_call('question_bank.init_checkbox_column', array(get_string('selectall'),
                    get_string('deselectall'), 'checkq' . $question->id), false, $module);
            $this->firstrow = false;
        }
    }
}

/**
 * A column type for the add this question to the offlinequiz.
 *
 * @copyright  2012 Juergen Zimmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_add_to_offlinequiz_action_column extends question_bank_action_column_base {
    protected $stradd;

    public function init() {
        parent::init();
        $this->stradd = get_string('addtoofflinequiz', 'offlinequiz');
    }

    public function get_name() {
        return 'addtoofflinequizaction';
    }

    protected function display_content($question, $rowclasses) {
        // for RTL languages: switch right and left arrows
        if (right_to_left()) {
            $movearrow = 't/removeright';
        } else {
            $movearrow = 't/moveleft';
        }
        if ($question->hidden) {
            $disabled = true;
        } else {
            $disabled = false;
        }
        
        $this->print_icon($movearrow, $this->stradd, $this->qbank->add_to_offlinequiz_url($question->id), $disabled);
    }

    protected function print_icon($icon, $title, $url, $disabled=false) {
        global $OUTPUT;
        if ($disabled) {
            echo '<a title="' . $title . '" href="' . $url . '" disabled="disabled">
                    <img src="' . $OUTPUT->pix_url($icon) . '" class="iconsmall" alt="' . $title . '"
                            style="opacity: 0.4; filter: alpha(opacity=40); /* msie *//"></a>';
        } else {
            echo '<a title="' . $title . '" href="' . $url . '">
                    <img src="' . $OUTPUT->pix_url($icon) . '" class="iconsmall" alt="' . $title . '" /></a>';
        }
    }

    public function get_required_fields() {
        return array('q.id');
    }
}

/**
 * A column type for the name followed by the start of the question text.
 *
 * @copyright  2012 Juergen Zimmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_question_name_text_column extends question_bank_question_name_column {
    public function get_name() {
        return 'questionnametext';
    }

    protected function display_content($question, $rowclasses) {
        echo '<div>';
        $labelfor = $this->label_for($question);
        if ($labelfor) {
            echo '<label for="' . $labelfor . '">';
        }
        echo offlinequiz_question_tostring($question, false, true, true);
        if ($labelfor) {
            echo '</label>';
        }
        echo '</div>';
    }

    public function get_required_fields() {
        $fields = parent::get_required_fields();
        $fields[] = 'q.questiontext';
        $fields[] = 'q.questiontextformat';
        return $fields;
    }
}

/**
 * Subclass to customise the view of the question bank for the offlinequiz editing screen.
 *
 * @copyright  2012 Juergen Zimmer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_question_bank_view extends question_bank_view {
    protected $offlinequizhasattempts = false;
    /** @var object the offlinequiz settings. */
    protected $offlinequiz = false;

    /**
     * Constructor
     * @param question_edit_contexts $contexts
     * @param moodle_url $pageurl
     * @param object $course course settings
     * @param object $cm activity settings.
     * @param object $offlinequiz offlinequiz settings.
     */
    public function __construct($contexts, $pageurl, $course, $cm, $offlinequiz) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->offlinequiz = $offlinequiz;
    }

    protected function known_field_types() {
        $types = parent::known_field_types();
        $types[] = new question_bank_add_to_offlinequiz_action_column($this);
        $types[] = new question_bank_my_question_type_column($this);
        $types[] = new question_bank_my_checkbox_column($this);
        $types[] = new question_bank_question_name_text_column($this);
        return $types;
    }

    protected function wanted_columns() {
        return array('addtoofflinequizaction', 'mycheckbox', 'myqtype', 'questionnametext',
                'editaction', 'previewaction');
    }

    protected function default_sort() {
        return array('qtype' => 1, 'questionnametext' => 1);
    }

    /**
     * Let the question bank display know whether the offlinequiz has been attempted,
     * hence whether some bits of UI, like the add this question to the offlinequiz icon,
     * should be displayed.
     * @param bool $offlinequizhasattempts whether the offlinequiz has attempts.
     */
    public function set_offlinequiz_has_attempts($offlinequizhasattempts) {
        $this->offlinequizhasattempts = $offlinequizhasattempts;
        if ($offlinequizhasattempts && isset($this->visiblecolumns['addtoofflinequizaction'])) {
            unset($this->visiblecolumns['addtoofflinequizaction']);
        }
    }

    public function preview_question_url($question) {
        return offlinequiz_question_preview_url($this->offlinequiz, $question);
    }

    public function add_to_offlinequiz_url($questionid) {
        global $CFG;
        $params = $this->baseurl->params();
        $params['addquestion'] = $questionid;
        $params['sesskey'] = sesskey();
        return new moodle_url('/mod/offlinequiz/edit.php', $params);
    }

    public function display($tabname, $page, $perpage, $cat,
            $recurse, $showhidden, $showquestiontext) {
        global $OUTPUT;
        if ($this->process_actions_needing_ui()) {
            return;
        }

        // Display the current category.
        if (!$category = $this->get_current_category($cat)) {
            return;
        }
        $this->print_category_info($category);

        echo $OUTPUT->box_start('generalbox questionbank');

        $this->display_category_form($this->contexts->having_one_edit_tab_cap($tabname),
                $this->baseurl, $cat);

        // continues with list of questions
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
                $this->baseurl, $cat, $this->cm, $recurse, $page,
                $perpage, $showhidden, $showquestiontext,
                $this->contexts->having_cap('moodle/question:add'));

        $this->display_options($recurse, $showhidden, $showquestiontext);
        echo $OUTPUT->box_end();
    }

    protected function print_choose_category_message($categoryandcontext) {
        global $OUTPUT;
        echo $OUTPUT->box_start('generalbox questionbank');
        $this->display_category_form($this->contexts->having_one_edit_tab_cap('edit'),
                $this->baseurl, $categoryandcontext);
        echo "<p style=\"text-align:center;\"><b>";
        print_string('selectcategoryabove', 'question');
        echo "</b></p>";
        echo $OUTPUT->box_end();
    }

    protected function print_category_info($category) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        $strcategory = get_string('category', 'offlinequiz');
        echo '<div class="categoryinfo"><div class="categorynamefieldcontainer">' .
                $strcategory;
        echo ': <span class="categorynamefield">';
        echo shorten_text(strip_tags(format_string($category->name)), 60);
        echo '</span></div><div class="categoryinfofieldcontainer">' .
                '<span class="categoryinfofield">';
        echo shorten_text(strip_tags(format_text($category->info, $category->infoformat,
                $formatoptions, $this->course->id)), 200);
        echo '</span></div></div>';
    }

    protected function display_options($recurse, $showhidden, $showquestiontext) {
        echo '<form method="get" action="edit.php" id="displayoptions">';
        echo "<fieldset class='invisiblefieldset'>";
        echo html_writer::input_hidden_params($this->baseurl,
                array('recurse', 'showhidden', 'qbshowtext'));
        $this->display_category_form_checkbox('recurse', $recurse,
                get_string('includesubcategories', 'question'));
        $this->display_category_form_checkbox('showhidden', $showhidden,
                get_string('showhidden', 'question'));
        echo '<noscript><div class="centerpara"><input type="submit" value="' .
                get_string('go') . '" />';
        echo '</div></noscript></fieldset></form>';
    }

    /* overriding the function for gettting the question list.
     * The queries returns multichoice and description questions only.
    **/
    protected function build_query_sql($category, $recurse, $showhidden) {
        global $DB;

        // Get the required tables.
        $joins = array();
        foreach ($this->requiredcolumns as $column) {
            $extrajoins = $column->get_extra_joins();
            foreach ($extrajoins as $prefix => $join) {
                if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                    throw new coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                }
                $joins[$prefix] = $join;
            }
        }

        // Get the required fields.
        $fields = array('q.hidden', 'q.category');
        foreach ($this->visiblecolumns as $column) {
            $fields = array_merge($fields, $column->get_required_fields());
        }
        foreach ($this->extrarows as $row) {
            $fields = array_merge($fields, $row->get_required_fields());
        }
        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = array();
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->knowncolumntypes[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $tests = array('parent = 0');

        if (!$showhidden) {
            $tests[] = 'hidden = 0';
        }

        if ($recurse) {
            $categoryids = question_categorylist($category->id);
        } else {
            $categoryids = array($category->id);
        }
        list($catidtest, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
        $tests[] = 'q.category ' . $catidtest;
        $this->sqlparams = $params;

        // JZ: For Offlinequizzes we only want to see multichoice and description questions
        $tests[] = "(q.qtype = 'multichoice' OR q.qtype = 'multichoiceset' OR q.qtype = 'description')";

        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
        $this->sqlparams = $params;
    }

    /**
     * Prints the table of questions in a category with interactions. We need to overwrite this because
     * of the hard-coded edit.php link.
     *
     * @param object $course   The course object
     * @param int $categoryid  The id of the question category to be displayed
     * @param int $cm      The course module record if we are in the context of a particular module, 0 otherwise
     * @param int $recurse     This is 1 if subcategories should be included, 0 otherwise
     * @param int $page        The number of the page to be displayed
     * @param int $perpage     Number of questions to show per page
     * @param bool $showhidden   True if also hidden questions should be displayed
     * @param bool $showquestiontext whether the text of each question should be shown in the list
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext, $cm = null, $recurse=1,
            $page=0, $perpage=100, $showhidden=false, $sortorder='typename', $sortorderdecoded='qtype, name ASC',
            $showquestiontext = false, $addcontexts = array()) {
        global $CFG, $DB, $OUTPUT;

        $category = $this->get_current_category($categoryandcontext);

        $cmoptions = new stdClass();
        $cmoptions->hasattempts = !empty($this->offlinequizhasattempts);

        $strdelete = get_string('delete');

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = get_context_instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);
        $caneditall =has_capability('moodle/question:editall', $catcontext);
        $canuseall =has_capability('moodle/question:useall', $catcontext);
        $canmoveall =has_capability('moodle/question:moveall', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query_sql($category, $recurse, $showhidden);
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }

        $questions = $this->load_page_questions($page, $perpage);

        echo '<div class="categorypagingbarcontainer">';
        $pageing_url = new moodle_url('edit.php');
        $r = $pageing_url->params($pageurl->params());
        $pagingbar = new paging_bar($totalnumber, $page, $perpage, $pageing_url);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<form method="post" action="edit.php">';
        echo '<fieldset class="invisiblefieldset" style="display: block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo html_writer::input_hidden_params($pageurl);

        echo '<div class="categoryquestionscontainer">';
        $this->start_table();
        $rowcount = 0;
        $usedquestions = explode(',', $this->offlinequiz->questions);

        foreach ($questions as $question) {
            // Hide the question if it has already been used in the offlinequiz.
            if (in_array($question->id, $usedquestions)) {
                $question->hidden = true;
            }

            $this->print_table_row($question, $rowcount);
            $rowcount += 1;
        }
        $this->end_table();
        echo "</div>\n";

        echo '<div class="categorypagingbarcontainer pagingbottom">';
        echo $OUTPUT->render($pagingbar);
        if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new moodle_url('edit.php', ($pageurl->params()+array('qperpage'=>1000)));
                $showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
            } else {
                $url = new moodle_url('edit.php', ($pageurl->params()+array('qperpage'=>DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
            }
            echo "<div class='paging'>$showall</div>";
        }
        echo '</div>';

        echo '<div class="modulespecificbuttonscontainer">';
        if ($caneditall || $canmoveall || $canuseall) {
            echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';

            if (function_exists('module_specific_buttons')) {
                echo module_specific_buttons($this->cm->id, $cmoptions);
            }

            // print delete and move selected question
            if ($caneditall) {
                echo '<input type="submit" name="deleteselected" value="' . $strdelete . "\" />\n";
            }

            if ($canmoveall && count($addcontexts)) {
                echo '<input type="submit" name="move" value="' . get_string('moveto', 'question') . "\" />\n";
                question_category_select_menu($addcontexts, false, 0, "$category->id,$category->contextid");
            }

            if (function_exists('module_specific_controls') && $canuseall) {
                $modulespecific = module_specific_controls($totalnumber, $recurse, $category, $this->cm->id, $cmoptions);
                if (!empty($modulespecific)) {
                    echo "<hr />$modulespecific";
                }
            }
        }
        echo "</div>\n";

        echo '</fieldset>';
        echo "</form>\n";
    }

    protected function create_new_question_form($category, $canadd) {
        global $CFG;
        echo '<div class="createnewquestion">';
        if ($canadd) {
            offlinequiz_create_new_question_button($category->id, $this->editquestionurl->params(),
                    get_string('createnewquestion', 'question'));
        } else {
            print_string('nopermissionadd', 'question');
        }
        echo '</div>';
    }
}

/**
 * Prints the form for setting a offlinequiz' overall grade
 *
 * @param object $offlinequiz The offlinequiz object of the offlinequiz in question
 * @param object $pageurl The url of the current page with the parameters required
 *     for links returning to the current page, as a moodle_url object
 * @param int $tabindex The tabindex to start from for the form elements created
 * @return int The tabindex from which the calling page can continue, that is,
 *      the last value used +1.
 */
function offlinequiz_print_grading_form($offlinequiz, $pageurl, $tabindex) {
    global $OUTPUT;
    $strsave = get_string('save', 'offlinequiz');
    echo '<form method="post" action="edit.php" class="offlinequizsavegradesform"><div>';
    echo '<fieldset class="invisiblefieldset" style="display: block;">';
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"" . sesskey() . "\" />";
    echo html_writer::input_hidden_params($pageurl);
    $a = '<input type="text" id="inputmaxgrade" name="maxgrade" size="' .
            ($offlinequiz->decimalpoints + 2) . '" tabindex="' . $tabindex
            . '" value="' . offlinequiz_format_grade($offlinequiz, $offlinequiz->grade) . '" />';
    echo '<label for="inputmaxgrade">' . get_string('maximumgradex', '', $a) . "</label>";
    echo '<input type="hidden" name="savechanges" value="save" />';
    echo '<input type="submit" value="' . $strsave . '" />';
    echo '</fieldset>';
    echo "</div></form>\n";
    return $tabindex + 1;
}

/**
 * Print the status bar
 *
 * @param object $offlinequiz The offlinequiz object of the offlinequiz in question
 */
function offlinequiz_print_status_bar($offlinequiz) {
    global $CFG;

    $bits = array();

    $bits[] = html_writer::tag('span',
            get_string('totalpointsx', 'offlinequiz', offlinequiz_format_grade($offlinequiz, $offlinequiz->sumgrades)),
            array('class' => 'totalpoints'));

    $bits[] = html_writer::tag('span',
            get_string('numquestionsx', 'offlinequiz', offlinequiz_number_of_questions_in_offlinequiz($offlinequiz->questions)),
            array('class' => 'numberofquestions'));

    $timenow = time();

    // Exact open and close dates for the tool-tip.
    $dates = array();
    if ($offlinequiz->timeopen > 0) {
        if ($timenow > $offlinequiz->timeopen) {
            $dates[] = get_string('offlinequizopenedon', 'offlinequiz', userdate($offlinequiz->timeopen));
        } else {
            $dates[] = get_string('offlinequizwillopen', 'offlinequiz', userdate($offlinequiz->timeopen));
        }
    }
    /* if ($offlinequiz->timeclose > 0) { */
    /*  if ($timenow > $offlinequiz->timeclose) { */
    /*      $dates[] = get_string('offlinequizclosed', 'offlinequiz', userdate($offlinequiz->timeclose)); */
    /*  } else { */
    /*      $dates[] = get_string('offlinequizcloseson', 'offlinequiz', userdate($offlinequiz->timeclose)); */
    /*  } */
    /* } */
    if (empty($dates)) {
        $dates[] = get_string('alwaysavailable', 'offlinequiz');
    }
    $tooltip = implode(', ', $dates);;

    // Brief summary on the page.
    if ($timenow < $offlinequiz->timeopen) {
        $currentstatus = get_string('offlinequizisclosedwillopen', 'offlinequiz',
                userdate($offlinequiz->timeopen, get_string('strftimedatetimeshort', 'langconfig')));
    } else if ($offlinequiz->timeclose && $timenow <= $offlinequiz->timeclose) {
        $currentstatus = get_string('offlinequizisopenwillclose', 'offlinequiz',
                userdate($offlinequiz->timeclose, get_string('strftimedatetimeshort', 'langconfig')));
    } else if ($offlinequiz->timeclose && $timenow > $offlinequiz->timeclose) {
        $currentstatus = get_string('offlinequizisclosed', 'offlinequiz');
    } else {
        $currentstatus = get_string('offlinequizisopen', 'offlinequiz');
    }

       $bits[] = html_writer::tag('span', $currentstatus,
      array('class' => 'offlinequizopeningstatus', 'title' => implode(', ', $dates)));

    echo html_writer::tag('div', implode(' | ', $bits), array('class' => 'statusbar'));
}


function offlinequiz_print_choose_qtype_to_add_form($hiddenparams) {
    global $CFG, $PAGE, $OUTPUT;

    echo '<div id="chooseqtypehead" class="hd">' . "\n";
    echo $OUTPUT->heading(get_string('chooseqtypetoadd', 'question'), 3);
    echo "</div>\n";
    echo '<div id="chooseqtype">' . "\n";
    echo '<form action="' . $CFG->wwwroot . '/question/question.php" method="get"><div id="qtypeformdiv">' . "\n";
    foreach ($hiddenparams as $name => $value) {
        echo '<input type="hidden" name="' . s($name) . '" value="' . s($value) . '" />' . "\n";
    }
    echo "</div>\n";
    echo '<div class="qtypes">' . "\n";
    echo '<div class="instruction">' . get_string('selectaqtypefordescription', 'question') . "</div>\n";
    echo '<div class="realqtypes">' . "\n";
    print_qtype_to_add_option(question_bank::get_qtype('multichoice'));
    if (question_bank::is_qtype_installed('multichoiceset') && $mcset = question_bank::get_qtype('multichoiceset')) {
        print_qtype_to_add_option($mcset);
    }
    print_qtype_to_add_option(question_bank::get_qtype('description'));
    echo "</div>\n";

    echo "</div>\n";
    echo '<div class="submitbuttons">' . "\n";
    echo '<input type="submit" value="' . get_string('next') . '" id="chooseqtype_submit" />' . "\n";
    echo '<input type="submit" id="chooseqtypecancel" name="addcancel" value="' . get_string('cancel') . '" />' . "\n";
    echo "</div></form>\n";
    echo "</div>\n";
 
    $PAGE->requires->js('/question/qengine.js');
    $module = array(
            'name'      => 'qbank',
            'fullpath'  => '/question/qbank.js',
            'requires'  => array('yui2-dom', 'yui2-event', 'yui2-container'),
            'strings'   => array(),
            'async'     => false,
    );
    $PAGE->requires->js_init_call('qtype_chooser.init', array('chooseqtype'), false, $module);
}

/**
 * Print a button for creating a new question. This will open question/addquestion.php,
 * which in turn goes to question/question.php before getting back to $params['returnurl']
 * (by default the question bank screen).
 *
 * @param int $categoryid The id of the category that the new question should be added to.
 * @param array $params Other paramters to add to the URL. You need either $params['cmid'] or
 *      $params['courseid'], and you should probably set $params['returnurl']
 * @param string $caption the text to display on the button.
 * @param string $tooltip a tooltip to add to the button (optional).
 * @param bool $disabled if true, the button will be disabled.
 */
function offlinequiz_create_new_question_button($categoryid, $params, $caption, $tooltip = '', $disabled = false) {
    global $CFG, $PAGE, $OUTPUT;
    static $choiceformprinted = false;
    $params['category'] = $categoryid;
    $url = new moodle_url('/question/addquestion.php', $params);
    echo $OUTPUT->single_button($url, $caption, 'get', array('disabled'=>$disabled, 'title'=>$tooltip));

    if (!$choiceformprinted) {
        echo '<div id="qtypechoicecontainer">';
        offlinequiz_print_choose_qtype_to_add_form(array());
        echo "</div>\n";
        $choiceformprinted = true;
    }
}
