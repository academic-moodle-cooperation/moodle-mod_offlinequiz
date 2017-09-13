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
 * Internal library of functions for module offlinequiz
 *
 * All the offlinequiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/engine/questionusage.php');

// These are the old error codes from the Moodle 1.9 module. We still need them for migration.
define("OFFLINEQUIZ_IMPORT_LMS", "1");
define("OFFLINEQUIZ_IMPORT_OK", "0");
define("OFFLINEQUIZ_IMPORT_CORRECTED", "1");
define("OFFLINEQUIZ_IMPORT_DOUBLE", "2");
define("OFFLINEQUIZ_IMPORT_ITEM_ERROR", "3");
define("OFFLINEQUIZ_IMPORT_DOUBLE_ERROR", "11");
define("OFFLINEQUIZ_IMPORT_USER_ERROR", "12");
define("OFFLINEQUIZ_IMPORT_GROUP_ERROR", "13");
define("OFFLINEQUIZ_IMPORT_FATAL_ERROR", "14");
define("OFFLINEQUIZ_IMPORT_INSECURE_ERROR", "15");
define("OFFLINEQUIZ_IMPORT_PAGE_ERROR", "16");
define("OFFLINEQUIZ_IMPORT_SINGLE_ERROR", "17"); // This is not really an error.
// It occures, when multipage answer sheets are scanned.
define("OFFLINEQUIZ_IMPORT_DOUBLE_PAGE_ERROR", "18"); // New error for double pages (e.g. page 2 occurs twice for as student).
define("OFFLINEQUIZ_IMPORT_DIFFERING_PAGE_ERROR", "19"); // New error for double pages that have different results (rawdata).

// Codes for lists of participants.
define("OFFLINEQUIZ_PART_FATAL_ERROR", "21");   // Over 20 indicates, it is a participants error.
define("OFFLINEQUIZ_PART_INSECURE_ERROR", "22");
define("OFFLINEQUIZ_PART_USER_ERROR", "23");
define("OFFLINEQUIZ_PART_LIST_ERROR", "24");
define("OFFLINEQUIZ_IMPORT_NUMUSERS", "50");

define('OFFLINEQUIZ_USER_FORMULA_REGEXP', "/^([^\[]*)\[([\-]?[0-9]+)\]([^\=]*)=([a-z]+)$/");

define('OFFLINEQUIZ_GROUP_LETTERS', "ABCDEFGHIJKL");  // Letters for naming offlinequiz groups.

define('OFFLINEQUIZ_PDF_FORMAT', 0);   // PDF file format for question sheets.
define('OFFLINEQUIZ_DOCX_FORMAT', 1);  // DOCX file format for question sheets.
define('OFFLINEQUIZ_LATEX_FORMAT', 2);  // LATEX file format for question sheets.

define('OFFLINEQUIZ_QUESTIONINFO_NONE', 0); // No info is printed.
define('OFFLINEQUIZ_QUESTIONINFO_QTYPE', 1); // The question type is printed.
define('OFFLINEQUIZ_QUESTIONINFO_ANSWERS', 2); // The number of correct answers is printed.

define('NUMBERS_PER_PAGE', 30);        // Number of students on participants list.
define('OQ_IMAGE_WIDTH', 860);         // Width of correction form.

class offlinequiz_question_usage_by_activity extends question_usage_by_activity {

    public function get_clone($qinstances) {
        // The new quba doesn't have to be cloned, so we can use the parent class.
        $newquba = question_engine::make_questions_usage_by_activity($this->owningcomponent, $this->context);
        $newquba->set_preferred_behaviour('immediatefeedback');

        foreach ($this->get_slots() as $slot) {
            $slotquestion = $this->get_question($slot);
            $attempt = $this->get_question_attempt($slot);

            // We have to check for the type because we might have old migrated templates
            // that could contain description questions.
            if ($slotquestion->get_type_name() == 'multichoice' || $slotquestion->get_type_name() == 'multichoiceset') {
                $order = $slotquestion->get_order($attempt);  // Order of the answers.
                $order = implode(',', $order);
                $newslot = $newquba->add_question($slotquestion, $qinstances[$slotquestion->id]->maxmark);
                $qa = $newquba->get_question_attempt($newslot);
                $qa->start('immediatefeedback', 1, array('_order' => $order));
            }
        }
        question_engine::save_questions_usage_by_activity($newquba);
        return $newquba;
    }

    /**
     * Create a question_usage_by_activity from records loaded from the database.
     *
     * For internal use only.
     *
     * @param Iterator $records Raw records loaded from the database.
     * @param int $questionattemptid The id of the question_attempt to extract.
     * @return question_usage_by_activity The newly constructed usage.
     */
    public static function load_from_records($records, $qubaid) {
        $record = $records->current();
        while ($record->qubaid != $qubaid) {
            $records->next();
            if (!$records->valid()) {
                throw new coding_exception("Question usage $qubaid not found in the database.");
            }
            $record = $records->current();
        }

        $quba = new offlinequiz_question_usage_by_activity($record->component,
                context::instance_by_id($record->contextid));
        $quba->set_id_from_database($record->qubaid);
        $quba->set_preferred_behaviour($record->preferredbehaviour);

        $quba->observer = new question_engine_unit_of_work($quba);

        while ($record && $record->qubaid == $qubaid && !is_null($record->slot)) {
            $quba->questionattempts[$record->slot] = question_attempt::load_from_records($records,
                    $record->questionattemptid, $quba->observer,
                    $quba->get_preferred_behaviour());
            if ($records->valid()) {
                $record = $records->current();
            } else {
                $record = false;
            }
        }

        return $quba;
    }
}

function offlinequiz_make_questions_usage_by_activity($component, $context) {
    return new offlinequiz_question_usage_by_activity($component, $context);
}

/**
 * Load a {@link question_usage_by_activity} from the database, including
 * all its {@link question_attempt}s and all their steps.
 * @param int $qubaid the id of the usage to load.
 * @param question_usage_by_activity the usage that was loaded.
 */
function offlinequiz_load_questions_usage_by_activity($qubaid) {
    global $DB;

    $records = $DB->get_recordset_sql("
            SELECT quba.id AS qubaid,
                   quba.contextid,
                   quba.component,
                   quba.preferredbehaviour,
                   qa.id AS questionattemptid,
                   qa.questionusageid,
                   qa.slot,
                   qa.behaviour,
                   qa.questionid,
                   qa.variant,
                   qa.maxmark,
                   qa.minfraction,
                   qa.maxfraction,
                   qa.flagged,
                   qa.questionsummary,
                   qa.rightanswer,
                   qa.responsesummary,
                   qa.timemodified,
                   qas.id AS attemptstepid,
                   qas.sequencenumber,
                   qas.state,
                   qas.fraction,
                   qas.timecreated,
                   qas.userid,
                   qasd.name,
                   qasd.value
              FROM {question_usages}            quba
         LEFT JOIN {question_attempts}          qa   ON qa.questionusageid    = quba.id
         LEFT JOIN {question_attempt_steps}     qas  ON qas.questionattemptid = qa.id
         LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid    = qas.id
            WHERE  quba.id = :qubaid
          ORDER BY qa.slot,
                   qas.sequencenumber
            ", array('qubaid' => $qubaid));

    if (!$records->valid()) {
        throw new coding_exception('Failed to load questions_usage_by_activity ' . $qubaid);
    }

    $quba = offlinequiz_question_usage_by_activity::load_from_records($records, $qubaid);
    $records->close();

    return $quba;
}

/**
 *
 * @param int $offlinequiz
 * @param int $groupid
 * @return string
 */
function offlinequiz_get_group_question_ids($offlinequiz, $groupid = 0) {
    global $DB;

    if (!$groupid) {
        $groupid = $offlinequiz->groupid;
    }

    // This query only makes sense if it is restricted to a offline group.
    if (!$groupid) {
        return '';
    }

    $sql = "SELECT questionid
              FROM {offlinequiz_group_questions}
             WHERE offlinequizid = :offlinequizid
               AND offlinegroupid = :offlinegroupid
          ORDER BY slot ASC ";

    $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $groupid);
    $questionids = $DB->get_fieldset_sql($sql, $params);

    return $questionids;
}


/**
 *
 * @param mixed $offlinequiz The offlinequiz
 * @return array returns an array of offline group numbers
 */
function offlinequiz_get_empty_groups($offlinequiz) {
    global $DB;

    $emptygroups = array();

    if ($groups = $DB->get_records('offlinequiz_groups',
                                   array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
        foreach ($groups as $group) {
            $questions = offlinequiz_get_group_question_ids($offlinequiz, $group->id);
            if (count($questions) < 1) {
                $emptygroups[] = $group->number;
            }
        }
    }
    return $emptygroups;
}


/**
 * Get the slot for a question with a particular id.
 * @param object $offlinequiz the offlinequiz settings.
 * @param int $questionid the of a question in the offlinequiz.
 * @return int the corresponding slot. Null if the question is not in the offlinequiz.
 */
function offlinequiz_get_slot_for_question($offlinequiz, $group, $questionid) {
    $questionids = offlinequiz_get_group_question_ids($offlinequiz, $group->id);
    foreach ($questionids as $key => $id) {
        if ($id == $questionid) {
            return $key + 1;
        }
    }
    return null;
}

/**
 * Verify that the question exists, and the user has permission to use it.
 * Does not return. Throws an exception if the question cannot be used.
 * @param int $questionid The id of the question.
 */
function offlinequiz_require_question_use($questionid) {
    global $DB;
    $question = $DB->get_record('question', array('id' => $questionid), '*', MUST_EXIST);
    question_require_capability_on($question, 'use');
}

/**
 * Add a question to a offlinequiz
 *
 * Adds a question to a offlinequiz by updating $offlinequiz as well as the
 * offlinequiz and offlinequiz_slots tables. It also adds a page break if required.
 * @param int $questionid The id of the question to be added
 * @param object $offlinequiz The extended offlinequiz object as used by edit.php
 *      This is updated by this function
 * @param int $page Which page in offlinequiz to add the question on. If 0 (default),
 *      add at the end
 * @param float $maxmark The maximum mark to set for this question. (Optional,
 *      defaults to question.defaultmark.
 * @return bool false if the question was already in the offlinequiz
 */
function offlinequiz_add_offlinequiz_question($questionid, $offlinequiz, $page = 0, $maxmark = null) {
    global $DB;

    if (offlinequiz_has_scanned_pages($offlinequiz->id)) {
        return false;
    }

    $slots = $DB->get_records('offlinequiz_group_questions',
            array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $offlinequiz->groupid),
            'slot', 'questionid, slot, page, id');
    if (array_key_exists($questionid, $slots)) {
        return false;
    }

    $trans = $DB->start_delegated_transaction();

    $maxpage = 1;
    $numonlastpage = 0;
    foreach ($slots as $slot) {
        if ($slot->page > $maxpage) {
            $maxpage = $slot->page;
            $numonlastpage = 1;
        } else {
            $numonlastpage += 1;
        }
    }

    // Add the new question instance.
    $slot = new stdClass();
    $slot->offlinequizid = $offlinequiz->id;
    $slot->offlinegroupid = $offlinequiz->groupid;
    $slot->questionid = $questionid;

    if ($maxmark !== null) {
        $slot->maxmark = $maxmark;
    } else {
        $slot->maxmark = $DB->get_field('question', 'defaultmark', array('id' => $questionid));
    }

    if (is_int($page) && $page >= 1) {
        // Adding on a given page.
        $lastslotbefore = 0;
        foreach (array_reverse($slots) as $otherslot) {
            if ($otherslot->page > $page) {
                // Increase the slot number of the other slot.
                $DB->set_field('offlinequiz_group_questions', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
            } else {
                $lastslotbefore = $otherslot->slot;
                break;
            }
        }
        $slot->slot = $lastslotbefore + 1;
        $slot->page = min($page, $maxpage + 1);

    } else {
        $lastslot = end($slots);
        if ($lastslot) {
            $slot->slot = $lastslot->slot + 1;
        } else {
            $slot->slot = 1;
        }
        if ($offlinequiz->questionsperpage && $numonlastpage >= $offlinequiz->questionsperpage) {
            $slot->page = $maxpage + 1;
        } else {
            $slot->page = $maxpage;
        }
    }

    $DB->insert_record('offlinequiz_group_questions', $slot);
    $trans->allow_commit();
}

/**
 * returns the maximum number of questions in a set of offline groups
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $groups
 * @return Ambigous <number, unknown>
 */
function offlinequiz_get_maxquestions($offlinequiz, $groups) {
    global $DB;

    $maxquestions = 0;
    foreach ($groups as $group) {

        $questionids = offlinequiz_get_group_question_ids($offlinequiz, $group->id);

        list($qsql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);

        $numquestions = $DB->count_records_sql("SELECT COUNT(id) FROM {question} WHERE qtype <> 'description' AND id $qsql",
                                               $params);
        if ($numquestions > $maxquestions) {
            $maxquestions = $numquestions;
        }
    }
    return $maxquestions;
}

/**
 *
 * @param unknown_type $scannedpage
 * @param unknown_type $corners
 */
function offlinequiz_save_page_corners($scannedpage, $corners) {
    global $DB;

    $position = 0;
    if ($existingcorners = $DB->get_records('offlinequiz_page_corners', array('scannedpageid' => $scannedpage->id), 'position')) {
        foreach ($existingcorners as $corner) {
            $corner->x = $corners[$position]->x;
            $corner->y = $corners[$position++]->y;
            $DB->update_record('offlinequiz_page_corners', $corner);
        }

    } else {
        foreach ($corners as $corner) {
            unset($corner->blank);
            $corner->position = $position++;
            $corner->scannedpageid = $scannedpage->id;
            $DB->insert_record('offlinequiz_page_corners', $corner);
        }
    }
}

/**
 * returns the maximum number of answers in the group questions of an offlinequiz
 * @param unknown_type $offlinequiz
 * @return number
 */
function offlinequiz_get_maxanswers($offlinequiz, $groups = array()) {
    global $CFG, $DB;

    $groupids = array();
    foreach ($groups as $group) {
        $groupids[] = $group->id;
    }

    $sql = "SELECT DISTINCT(questionid)
              FROM {offlinequiz_group_questions}
             WHERE offlinequizid = :offlinequizid
               AND questionid > 0";

    if (!empty($groupids)) {
        list($gsql, $params) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
        $sql .= " AND offlinegroupid " . $gsql;
    } else {
        $params = array();
    }

    $params['offlinequizid'] = $offlinequiz->id;

    $questionids = $DB->get_records_sql($sql, $params);
    $questionlist = array_keys($questionids);

    $counts = array();
    if (!empty($questionlist)) {
        foreach ($questionlist as $questionid) {
            $sql = "SELECT COUNT(id)
                      FROM {question_answers} qa
                     WHERE qa.question = :questionid
                    ";
            $params = array('questionid' => $questionid);
            $counts[] = $DB->count_records_sql($sql, $params);
        }
        return max($counts);
    } else {
        return 0;
    }
}


/**
 * Repaginate the questions in a offlinequiz
 * @param int $offlinequizid the id of the offlinequiz to repaginate.
 * @param int $slotsperpage number of items to put on each page. 0 means unlimited.
 */
function offlinequiz_repaginate_questions($offlinequizid, $offlinegroupid, $slotsperpage) {
    global $DB;
    $trans = $DB->start_delegated_transaction();

    $slots = $DB->get_records('offlinequiz_group_questions',
            array('offlinequizid' => $offlinequizid, 'offlinegroupid' => $offlinegroupid),
            'slot');

    $currentpage = 1;
    $slotsonthispage = 0;
    foreach ($slots as $slot) {
        if ($slotsonthispage && $slotsonthispage == $slotsperpage) {
            $currentpage += 1;
            $slotsonthispage = 0;
        }
        if ($slot->page != $currentpage) {
            $DB->set_field('offlinequiz_group_questions', 'page', $currentpage,
                    array('id' => $slot->id));
        }
        $slotsonthispage += 1;
    }

    $trans->allow_commit();
}

/**
 * Re-paginates the offlinequiz layout
 *
 * @return string         The new layout string
 * @param string $layout  The string representing the offlinequiz layout.
 * @param integer $perpage The number of questions per page
 * @param boolean $shuffle Should the questions be reordered randomly?
 */
function offlinequiz_shuffle_questions($questionids) {
    srand((float)microtime() * 1000000); // For php < 4.2.
    shuffle($questionids);
    return $questionids;
}

/**
 * returns true if there are scanned pages for an offline quiz.
 * @param int $offlinequizid
 */
function offlinequiz_has_scanned_pages($offlinequizid) {
    global $CFG, $DB;

    $sql = "SELECT COUNT(id)
              FROM {offlinequiz_scanned_pages}
             WHERE offlinequizid = :offlinequizid";
    $params = array('offlinequizid' => $offlinequizid);
    return $DB->count_records_sql($sql, $params) > 0;
}

/**
 *
 * @param unknown_type $page
 */
function offlinequiz_delete_scanned_page($page, $context) {
    global $DB;

    $resultid = $page->resultid;
    $fs = get_file_storage();

    // Delete the scanned page.
    $DB->delete_records('offlinequiz_scanned_pages', array('id' => $page->id));
    // Delete the choices made on the page.
    $DB->delete_records('offlinequiz_choices', array('scannedpageid' => $page->id));
    // Delete the corner coordinates.
    $DB->delete_records('offlinequiz_page_corners', array('scannedpageid' => $page->id));

    // If there is no scannedpage for the result anymore, we also delete the result.
    if ($resultid && !$DB->get_records('offlinequiz_scanned_pages', array('resultid' => $resultid))) {
        // Delete the result.
        $DB->delete_records('offlinequiz_results', array('id' => $resultid));
    }

    // JZ: also delete the image files associated with the deleted page.
    if ($page->filename && $file = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $page->filename)) {
        $file->delete();
    }
    if ($page->warningfilename &&
        $file = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $page->warningfilename)) {

        $file->delete();
    }
}

/**
 *
 * @param unknown_type $page
 */
function offlinequiz_delete_scanned_p_page($page, $context) {
    global $DB;

    $fs = get_file_storage();

    // Delete the scanned participants page.
    $DB->delete_records('offlinequiz_scanned_p_pages', array('id' => $page->id));
    // Delete the choices made on the page.
    $DB->delete_records('offlinequiz_p_choices', array('scannedppageid' => $page->id));

    // JZ: also delete the image files associated with the deleted page.
    if ($page->filename && $file = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $page->filename)) {
        $file->delete();
    }
}

/**
 * returns the number of completed results for an offline quiz.
 * @param int $offlinequizid
 * @param int $courseid
 * @param boolean $onlystudents
 */
function offlinequiz_completed_results($offlinequizid, $courseid, $onlystudents = false) {
    global $CFG, $DB;

    if ($onlystudents) {
        $coursecontext = context_course::instance($courseid);
        $contextids = $coursecontext->get_parent_context_ids(true);
        list($csql, $params) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        $params['offlinequizid'] = $offlinequizid;

        $select = "SELECT COUNT(DISTINCT(u.id)) as counter
                     FROM {user} u
                     JOIN {role_assignments} ra ON ra.userid = u.id
                LEFT JOIN {offlinequiz_results} qa
                           ON u.id = qa.userid
                          AND qa.offlinequizid = :offlinequizid
                          AND qa.status = 'complete'
                    WHERE ra.contextid $csql
                      AND qa.userid IS NOT NULL
        ";

        return $DB->count_records_sql($select, $params);
    } else {
        $params = array('offlinequizid' => $offlinequizid);
        return $DB->count_records_select('offlinequiz_results', "offlinequizid = :offlinequizid AND status = 'complete'",
                                         $params, 'COUNT(id)');
    }
}

/**
 * Delete an offlinequiz result, including the questions_usage_by_activity corresponding to it.
 *
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the offlinequiz_results table).
 * @param object $offlinequiz the offlinequiz object.
 */
function offlinequiz_delete_result($resultid, $context) {
    global $DB;

    if ($result = $DB->get_record('offlinequiz_results', array('id' => $resultid))) {

        // First delete the result itself.
        $DB->delete_records('offlinequiz_results', array('id' => $result->id));

        // Now we delete all scanned pages that refer to the result.
        $scannedpages = $DB->get_records_sql("
                SELECT *
                  FROM {offlinequiz_scanned_pages}
                 WHERE resultid = :resultid", array('resultid' => $result->id));

        foreach ($scannedpages as $page) {
            offlinequiz_delete_scanned_page($page, $context);
        }

        // Finally, delete the question usage that belongs to the result.
        if ($result->usageid) {
            question_engine::delete_questions_usage_by_activity($result->usageid);
        }
    }
}

/**
 * Save new maxgrade to a question instance
 *
 * Saves changes to the question grades in the offlinequiz_group_questions table.
 * The grades of the questions in the group template qubas are also updated.
 * This function does not update 'sumgrades' in the offlinequiz table.
 *
 * @param int $offlinequiz  The offlinequiz to update / add the instances for.
 * @param int $questionid  The id of the question
 * @param int grade    The maximal grade for the question
 */
function offlinequiz_update_question_instance($offlinequiz, $questionid, $grade) {
    global $DB;

    // First change the maxmark of the question in all offline quiz groups.
    $groupquestionids = $DB->get_fieldset_select('offlinequiz_group_questions', 'id',
                    'offlinequizid = :offlinequizid AND questionid = :questionid',
                    array('offlinequizid' => $offlinequiz->id, 'questionid' => $questionid));

    foreach ($groupquestionids as $groupquestionid) {
        $DB->set_field('offlinequiz_group_questions', 'maxmark', $grade, array('id' => $groupquestionid));
    }

    $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0,
                $offlinequiz->numgroups);

    // Now change the maxmark of the question instance in the template question usages of the offlinequiz groups.
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
                // Update the grade in the template usage.
                question_engine::set_max_mark_in_attempts(new qubaid_list(array($group->templateusageid)), $slot, $grade);
            }
        }
    }

    // Now do the same for the qubas of the results of the offline quiz.
    if ($results = $DB->get_records('offlinequiz_results', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($results as $result) {
            if ($result->usageid > 0) {
                $quba = question_engine::load_questions_usage_by_activity($result->usageid);
                $slots = $quba->get_slots();

                $slot = 0;
                foreach ($slots as $thisslot) {
                    if ($quba->get_question($thisslot)->id == $questionid) {
                        $slot = $thisslot;
                        break;
                    }
                }
                if ($slot) {
                    question_engine::set_max_mark_in_attempts(new qubaid_list(array($result->usageid)), $slot, $grade);

                    // Now set the new sumgrades also in the offline quiz result.
                    $newquba = question_engine::load_questions_usage_by_activity($result->usageid);
                    $DB->set_field('offlinequiz_results', 'sumgrades',  $newquba->get_total_mark(),
                        array('id' => $result->id));
                }
            }
        }
    }
}


/**
 * Update the sumgrades field of the results in an offline quiz.
 *
 * @param object $offlinequiz The offlinequiz.
 */
function offlinequiz_update_all_attempt_sumgrades($offlinequiz) {
    global $DB;
    $dm = new question_engine_data_mapper();
    $timenow = time();

    $sql = "UPDATE {offlinequiz_results}
               SET timemodified = :timenow,
                   sumgrades = (
                                {$dm->sum_usage_marks_subquery('usageid')}
                               )
             WHERE offlinequizid = :offlinequizid
               AND timefinish <> 0";
    $DB->execute($sql, array('timenow' => $timenow, 'offlinequizid' => $offlinequiz->id));
}

/**
 * A {@link qubaid_condition} for finding all the question usages belonging to
 * a particular offlinequiz. Used in editlib.php.
 *
 * @copyright  2010 The University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class result_qubaids_for_offlinequiz extends qubaid_join {
    public function __construct($offlinequizid, $offlinegroupid, $includepreviews = true, $onlyfinished = false) {
        $where = 'quiza.offlinequizid = :offlinequizid AND quiza.offlinegroupid = :offlinegroupid';
        if (!$includepreviews) {
            $where .= ' AND preview = 0';
        }
        if ($onlyfinished) {
            $where .= ' AND timefinish <> 0';
        }

        parent::__construct('{offlinequiz_results} quiza', 'quiza.usageid', $where,
                array('offlinequizid' => $offlinequizid, 'offlinegroupid' => $offlinegroupid));
    }
}

/**
 * The offlinequiz grade is the maximum that student's results are marked out of. When it
 * changes, the corresponding data in offlinequiz_grades and offlinequiz_feedback needs to be
 * rescaled. After calling this function, you probably need to call
 * offlinequiz_update_all_attempt_sumgrades, offlinequiz_update_all_final_grades and
 * offlinequiz_update_grades.
 *
 * @param float $newgrade the new maximum grade for the offlinequiz.
 * @param object $offlinequiz the offlinequiz we are updating. Passed by reference so its
 *      grade field can be updated too.
 * @return bool indicating success or failure.
 */
function offlinequiz_set_grade($newgrade, $offlinequiz) {
    global $DB;
    // This is potentially expensive, so only do it if necessary.
    if (abs($offlinequiz->grade - $newgrade) < 1e-7) {
        // Nothing to do.
        return true;
    }

    // Use a transaction, so that on those databases that support it, this is safer.
    $transaction = $DB->start_delegated_transaction();

    // Update the offlinequiz table.
    $DB->set_field('offlinequiz', 'grade', $newgrade, array('id' => $offlinequiz->id));

    $offlinequiz->grade = $newgrade;

    // Update grade item and send all grades to gradebook.
    offlinequiz_grade_item_update($offlinequiz);
    offlinequiz_update_grades($offlinequiz);

    $transaction->allow_commit();
    return true;
}


/**
 * Returns info about the JS module used by offlinequizzes.
 *
 * @return multitype:string multitype:string  multitype:multitype:string
 */
function offlinequiz_get_js_module() {
    global $PAGE;
    return array(
            'name' => 'mod_offlinequiz',
            'fullpath' => '/mod/offlinequiz/module.js',
            'requires' => array('base', 'dom', 'event-delegate', 'event-key',
                    'core_question_engine'),
            'strings' => array(
                    array('timesup', 'offlinequiz'),
                    array('functiondisabledbysecuremode', 'offlinequiz'),
                    array('flagged', 'question'),
            ),
    );
}

// Other offlinequiz functions.

/**
 * @param object $offlinequiz the offlinequiz.
 * @param int $cmid the course_module object for this offlinequiz.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @return string html for a number of icons linked to action pages for a
 * question - preview and edit / view icons depending on user capabilities.
 */
function offlinequiz_question_action_icons($offlinequiz, $cmid, $question, $returnurl) {
    $html = offlinequiz_question_preview_button($offlinequiz, $question);
    if (!$offlinequiz->docscreated) {
        $html .= ' ' .  offlinequiz_question_edit_button($cmid, $question, $returnurl);
    }
    return $html;
}


/**
 * Returns true if the student has access to results. Function doesn't check if there is a result.
 *
 * @param object offlinequiz  The offlinequiz object
 */
function offlinequiz_results_open($offlinequiz) {

    if ($offlinequiz->timeclose and time() >= $offlinequiz->timeclose) {
        return false;
    }
    if ($offlinequiz->timeopen and time() <= $offlinequiz->timeopen) {
        return false;
    }

    $options = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    // There has to be responses or (graded)sheetfeedback.

    if ($options->attempt == question_display_options::HIDDEN and
            $options->marks == question_display_options::HIDDEN and
            $options->sheetfeedback == question_display_options::HIDDEN and
            $options->gradedsheetfeedback == question_display_options::HIDDEN) {
        return false;
    } else {
        return true;
    }
}

/**
 * @deprecated User identification is now set in admin settings.
 */
function offlinequiz_load_useridentification() {
    return;
}

/**
 * Creates an array of maximum grades for an offlinequiz
 *
 * The grades are extracted for the offlinequiz_question_instances table.
 * @param object $offlinequiz The offlinequiz settings.
 * @return array of grades indexed by question id. These are the maximum
 *      possible grades that students can achieve for each of the questions.
 */
function offlinequiz_get_all_question_grades($offlinequiz) {
    global $CFG, $DB;

    $questionlist = $offlinequiz->questions;
    if (empty($questionlist)) {
        return array();
    }

    $wheresql = '';
    $params = array();
    if (!empty($questionlist)) {
        list($usql, $questionparams) = $DB->get_in_or_equal($questionlist, SQL_PARAMS_NAMED, 'qid');
        $wheresql = " AND questionid $usql ";
        $params = array_merge($params, $questionparams);
    }
    $params['offlinequizid'] = $offlinequiz->id;

    $instances = $DB->get_records_sql("
            SELECT questionid, maxmark
              FROM {offlinequiz_group_questions}
             WHERE offlinequizid = :offlinequizid
                   $wheresql", $params);

    $grades = array();
    foreach ($questionlist as $qid) {
        if (isset($instances[$qid])) {
            $grades[$qid] = $instances[$qid]->maxmark;
        } else {
            $grades[$qid] = 1;
        }
    }

    return $grades;
}


/**
 * Returns the number of pages in a offlinequiz layout
 *
 * @param string $layout The string representing the offlinequiz layout. Always ends in ,0
 * @return int The number of pages in the offlinequiz.
 */
function offlinequiz_number_of_pages($layout) {
    return substr_count(',' . $layout, ',0');
}

/**
 * Counts the multichoice question in a questionusage.
 *
 * @param question_usage_by_activity $questionusage
 * @return number
 */
function offlinequiz_count_multichoice_questions(question_usage_by_activity $questionusage) {
    $count = 0;
    $slots = $questionusage->get_slots();
    foreach ($slots as $slot) {
        $question = $questionusage->get_question($slot);
        if ($question->qtype->name() == 'multichoice' || $question->qtype->name() == 'multichoiceset') {
            $count++;
        }
    }
    return $count;
}

/**
 * Returns the sumgrades for a given offlinequiz group.
 *
 * @param object $offlinequiz object that must contain the groupid field.
 * @return Ambigous <mixed, boolean>
 */
function offlinequiz_get_group_sumgrades($offlinequiz) {
    global $DB;

    $sql = 'SELECT COALESCE((SELECT SUM(maxmark)
              FROM {offlinequiz_group_questions} ogq
             WHERE ogq.offlinequizid = :offlinequizid
               AND ogq.offlinegroupid = :groupid) , 0)';

    $params = array('offlinequizid' => $offlinequiz->id,
            'groupid' => $offlinequiz->groupid);

    $sumgrades = $DB->get_field_sql($sql, $params);
    return $sumgrades;
}

/**
 * Update the sumgrades field of the offlinequiz. This needs to be called whenever
 * the grading structure of the offlinequiz is changed. For example if a question is
 * added or removed, or a question weight is changed.
 *
 * @param object $offlinequiz a offlinequiz.
 */
function offlinequiz_update_sumgrades($offlinequiz, $offlinegroupid = null) {
    global $DB;

    $groupid = 0;
    if (isset($offlinequiz->groupid)) {
        $groupid = $offlinequiz->groupid;
    }
    if (!empty($offlinegroupid)) {
        $groupid = $offlinegroupid;
    }
    $sql = 'UPDATE {offlinequiz_groups}
               SET sumgrades = COALESCE((
                   SELECT SUM(maxmark)
                     FROM {offlinequiz_group_questions} ogq
                    WHERE ogq.offlinequizid = :offlinequizid1
                      AND ogq.offlinegroupid = :groupid1
                      ), 0)
             WHERE offlinequizid = :offlinequizid2
               AND id = :groupid2';

    $params = array('offlinequizid1' => $offlinequiz->id,
            'offlinequizid2' => $offlinequiz->id,
            'groupid1' => $groupid,
            'groupid2' => $groupid);
    $DB->execute($sql, $params);

    $sumgrades = $DB->get_field('offlinequiz_groups', 'sumgrades', array('id' => $groupid));
    return $sumgrades;
}

/**
 * Convert the raw grade stored in $attempt into a grade out of the maximum
 * grade for this offlinequiz.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $offlinequiz the offlinequiz object. Only the fields grade, sumgrades and decimalpoints are used.
 * @param bool|string $format whether to format the results for display
 *      or 'question' to format a question grade (different number of decimal places.
 * @return float|string the rescaled grade, or null/the lang string 'notyetgraded'
 *      if the $grade is null.
 */
function offlinequiz_rescale_grade($rawgrade, $offlinequiz, $group, $format = true) {
    if (is_null($rawgrade)) {
        $grade = null;
    } else if ($group->sumgrades >= 0.000005) {
        $grade = $rawgrade / $group->sumgrades * $offlinequiz->grade;
    } else {
        $grade = 0;
    }
    if ($format === 'question') {
        $grade = offlinequiz_format_question_grade($offlinequiz, $grade);
    } else if ($format) {
        $grade = offlinequiz_format_grade($offlinequiz, $grade);
    }
    return $grade;
}


/**
 * Extends first object with member data of the second
 *
 * @param unknown_type $first
 * @param unknown_type $second
 */
function offlinequiz_extend_object (&$first, &$second) {

    foreach ($second as $key => $value) {
        if (empty($first->$key)) {
            $first->$key = $value;
        }
    }

}

/**
 * Returns the group object for a given offlinequiz and group number (1,2,3...). Adds a
 * new group if the group does not exist.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $groupnumber
 * @return Ambigous <mixed, boolean, unknown>
 */
function offlinequiz_get_group($offlinequiz, $groupnumber) {
    global $DB;

    if (!$offlinequizgroup = $DB->get_record('offlinequiz_groups',
                                              array('offlinequizid' => $offlinequiz->id, 'number' => $groupnumber))) {
        if ($groupnumber > 0 && $groupnumber <= $offlinequiz->numgroups) {
            $offlinequizgroup = offlinequiz_add_group( $offlinequiz->id, $groupnumber);
        }
    }
    return $offlinequizgroup;
}

/**
 * Adds a new group with a given group number to a given offlinequiz.
 *
 * @param object $offlinequiz the data that came from the form.
 * @param int groupnumber The number of the group to add.
 * @return mixed the id of the new instance on success,
 *          false or a string error message on failure.
 */
function offlinequiz_add_group($offlinequizid, $groupnumber) {
    GLOBAL $DB;

    $offlinequizgroup = new StdClass();
    $offlinequizgroup->offlinequizid = $offlinequizid;
    $offlinequizgroup->number = $groupnumber;

    // Note: numberofpages and templateusageid will be filled later.

    // Try to store it in the database.
    if (!$offlinequizgroup->id = $DB->insert_record('offlinequiz_groups', $offlinequizgroup)) {
        return false;
    }

    return $offlinequizgroup;
}

/**
 * Checks whether any list of participants have been created for a given offlinequiz.
 *
 * @param unknown_type $offlinequiz
 * @return boolean
 */
function offlinequiz_partlist_created($offlinequiz) {
    global $DB;

    return $DB->count_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id)) > 0;
}


/**
 * An extension of question_display_options that includes the extra options used
 * by the offlinequiz.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_offlinequiz_display_options extends question_display_options {
    /**
     * @var boolean if this is false, then the student is not allowed to review
     * anything about the result.
     */
    public $responses = true;

    /**
     * @var boolean if this is false, then the student cannot see the scanned answer forms
     */
    public $sheetfeedback = false;

    /**
     * @var boolean if this is false, then the student cannot see any markings in the scanned answer forms.
     */
    public $gradedsheetfeedback = false;

    /**
     * Set up the various options from the offlinequiz settings, and a time constant.
     * @param object $offlinequiz the offlinequiz settings.
     * @param int $one of the {@link DURING}, {@link IMMEDIATELY_AFTER},
     * {@link LATER_WHILE_OPEN} or {@link AFTER_CLOSE} constants.
     * @return mod_offlinequiz_display_options set up appropriately.
     */
    public static function make_from_offlinequiz($offlinequiz) {
        $options = new self();

        $options->attempt = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_ATTEMPT);
        $options->marks = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_MARKS) ?
            question_display_options::MARK_AND_MAX : question_display_options::HIDDEN;
        $options->correctness = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_CORRECTNESS);
        $options->feedback = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK);
        $options->generalfeedback = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_GENERALFEEDBACK);
        $options->rightanswer = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_RIGHTANSWER);
        $options->sheetfeedback = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_SHEET);
        $options->gradedsheetfeedback = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_GRADEDSHEET);

        $options->numpartscorrect = $options->feedback;

        if (property_exists($offlinequiz, 'decimalpoints')) {
            $options->markdp = $offlinequiz->decimalpoints;
        }

        // We never want to see any flags.
        $options->flags = question_display_options::HIDDEN;

        return $options;
    }

    protected static function extract($bitmask, $bit, $whenset = self::VISIBLE, $whennotset = self::HIDDEN) {
        if ($bitmask & $bit) {
            return $whenset;
        } else {
            return $whennotset;
        }
    }
}


/**
 * The appropriate mod_offlinequiz_display_options object for this result at this
 * offlinequiz right now.
 *
 * @param object $offlinequiz the offlinequiz instance.
 * @param object $result the result in question.
 * @param $context the offlinequiz context.
 *
 * @return mod_offlinequiz_display_options
 */
function offlinequiz_get_review_options($offlinequiz, $result, $context) {

    $options = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);

    $options->readonly = true;

    if (!empty($result->id)) {
        $options->questionreviewlink = new moodle_url('/mod/offlinequiz/reviewquestion.php',
                array('resultid' => $result->id));
    }

    if (!is_null($context) &&
            has_capability('mod/offlinequiz:viewreports', $context) &&
            has_capability('moodle/grade:viewhidden', $context)) {

        // The teacher should be shown everything.
        $options->attempt = question_display_options::VISIBLE;
        $options->marks = question_display_options::MARK_AND_MAX;
        $options->correctness = question_display_options::VISIBLE;
        $options->feedback = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::VISIBLE;
        $options->sheetfeedback = question_display_options::VISIBLE;
        $options->gradedsheetfeedback = question_display_options::VISIBLE;

        // Show a link to the comment box only for closed attempts.
        if (!empty($result->id) && $result->timefinish &&
                !is_null($context) && has_capability('mod/offlinequiz:grade', $context)) {
            $options->manualcomment = question_display_options::VISIBLE;
            $options->manualcommentlink = new moodle_url('/mod/offlinequiz/comment.php',
                    array('resultid' => $result->id));
        }
    }
    return $options;
}


/**
 * Combines the review options from a number of different offlinequiz attempts.
 * Returns an array of two ojects, so the suggested way of calling this
 * funciton is:
 * list($someoptions, $alloptions) = offlinequiz_get_combined_reviewoptions(...)
 *
 * @param object $offlinequiz the offlinequiz instance.
 * @param array $attempts an array of attempt objects.
 * @param $context the roles and permissions context,
 *          normally the context for the offlinequiz module instance.
 *
 * @return array of two options objects, one showing which options are true for
 *          at least one of the attempts, the other showing which options are true
 *          for all attempts.
 */
function offlinequiz_get_combined_reviewoptions($offlinequiz) {
    $fields = array('feedback', 'generalfeedback', 'rightanswer');
    $someoptions = new stdClass();
    $alloptions = new stdClass();
    foreach ($fields as $field) {
        $someoptions->$field = false;
        $alloptions->$field = true;
    }
    $someoptions->marks = question_display_options::HIDDEN;
    $alloptions->marks = question_display_options::MARK_AND_MAX;

    $attemptoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    foreach ($fields as $field) {
        $someoptions->$field = $someoptions->$field || $attemptoptions->$field;
        $alloptions->$field = $alloptions->$field && $attemptoptions->$field;
    }
    $someoptions->marks = max($someoptions->marks, $attemptoptions->marks);
    $alloptions->marks = min($alloptions->marks, $attemptoptions->marks);

    return array($someoptions, $alloptions);
}

/**
 * Creates HTML code for a question edit button, used by editlib.php
 *
 * @param int $cmid the course_module.id for this offlinequiz.
 * @param object $question the question.
 * @param string $returnurl url to return to after action is done.
 * @param string $contentbeforeicon some HTML content to be added inside the link, before the icon.
 * @return the HTML for an edit icon, view icon, or nothing for a question
 *      (depending on permissions).
 */
function offlinequiz_question_edit_button($cmid, $question, $returnurl, $contentaftericon = '') {
    global $CFG, $OUTPUT;

    // Minor efficiency saving. Only get strings once, even if there are a lot of icons on one page.
    static $stredit = null;
    static $strview = null;
    if ($stredit === null) {
        $stredit = get_string('edit');
        $strview = get_string('view');
    }

    // What sort of icon should we show?
    $action = '';
    if (!empty($question->id) &&
            (question_has_capability_on($question, 'edit', $question->category) ||
                    question_has_capability_on($question, 'move', $question->category))
    ) {
        $action = $stredit;
        $icon = '/t/edit';
    } else if (!empty($question->id) &&
            question_has_capability_on($question, 'view', $question->category)) {
        $action = $strview;
        $icon = '/i/info';
    }

    // Build the icon.
    if ($action) {
        if ($returnurl instanceof moodle_url) {
            $returnurl = str_replace($CFG->wwwroot, '', $returnurl->out(false));
        }
        $questionparams = array('returnurl' => $returnurl, 'cmid' => $cmid, 'id' => $question->id);
        $questionurl = new moodle_url("$CFG->wwwroot/question/question.php", $questionparams);
        return '<a title="' . $action . '" href="' . $questionurl->out() . '"><img src="' .
                $OUTPUT->pix_url($icon) . '" alt="' . $action . '" />' . $contentaftericon .
                '</a>';
    } else {
        return $contentaftericon;
    }
}

/**
 * Creates HTML code for a question preview button.
 *
 * @param object $offlinequiz the offlinequiz settings
 * @param object $question the question
 * @param bool $label if true, show the preview question label after the icon
 * @return the HTML for a preview question icon.
 */
function offlinequiz_question_preview_button($offlinequiz, $question, $label = false) {
    global $CFG, $OUTPUT;
    if (property_exists($question, 'category') &&
            !question_has_capability_on($question, 'use', $question->category)) {
        return '';
    }

    $url = offlinequiz_question_preview_url($offlinequiz, $question);

    // Do we want a label?
    $strpreviewlabel = '';
    if ($label) {
        $strpreviewlabel = get_string('preview', 'offlinequiz');
    }

    // Build the icon.
    $strpreviewquestion = get_string('previewquestion', 'offlinequiz');
    $image = $OUTPUT->pix_icon('t/preview', $strpreviewquestion);

    $action = new popup_action('click', $url, 'questionpreview',
            question_preview_popup_params());

    return $OUTPUT->action_link($url, $image, $action, array('title' => $strpreviewquestion));
}

/**
 * @param object $offlinequiz the offlinequiz settings
 * @param object $question the question
 * @return moodle_url to preview this question with the options from this offlinequiz.
 */
function offlinequiz_question_preview_url($offlinequiz, $question) {
    // Get the appropriate display options.
    $displayoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);

    $maxmark = null;
    if (isset($question->maxmark)) {
        $maxmark = $question->maxmark;
    }

    // Work out the correct preview URL.
    return question_preview_url($question->id, null,
            $maxmark, $displayoptions);
}


/**
 * Retrieves a template question usage for an offline group. Creates a new template if there is none.
 * While creating question usage it shuffles the group questions if shuffleanswers is created.
 *
 * @param object $offlinequiz
 * @param object $group
 * @param object $context
 * @return question_usage_by_activity
 */
function offlinequiz_get_group_template_usage($offlinequiz, $group, $context) {
    global $CFG, $DB;

    if (!empty($group->templateusageid) && $group->templateusageid > 0) {
        $templateusage = question_engine::load_questions_usage_by_activity($group->templateusageid);
    } else {

        $questionids = offlinequiz_get_group_question_ids($offlinequiz, $group->id);

        if ($offlinequiz->shufflequestions) {
            $offlinequiz->groupid = $group->id;

            $questionids = offlinequiz_shuffle_questions($questionids);
        }

        // We have to use our own class s.t. we can use the clone function to create results.
        $templateusage = offlinequiz_make_questions_usage_by_activity('mod_offlinequiz', $context);
        $templateusage->set_preferred_behaviour('immediatefeedback');

        if (!$questionids) {
            print_error(get_string('noquestionsfound', 'offlinequiz'), 'view.php?q='.$offlinequiz->id);
        }

        // Gets database raw data for the questions.
        $questiondata = question_load_questions($questionids);

        // Get the question instances for initial markmarks.
        $sql = "SELECT questionid, maxmark
                  FROM {offlinequiz_group_questions}
                 WHERE offlinequizid = :offlinequizid
                   AND offlinegroupid = :offlinegroupid ";

        $groupquestions = $DB->get_records_sql($sql,
                array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id));

        foreach ($questionids as $questionid) {
            if ($questionid) {
                // Convert the raw data of multichoice questions to a real question definition object.
                if (!$offlinequiz->shuffleanswers) {
                    $questiondata[$questionid]->options->shuffleanswers = false;
                }
                $question = question_bank::make_question($questiondata[$questionid]);

                // We only add multichoice questions which are needed for grading.
                if ($question->get_type_name() == 'multichoice' || $question->get_type_name() == 'multichoiceset') {
                    $templateusage->add_question($question, $groupquestions[$question->id]->maxmark);
                }
            }
        }

        // Create attempts for all questions (fixes order of the answers if shuffleanswers is active).
        $templateusage->start_all_questions();

        // Save the template question usage to the DB.
        question_engine::save_questions_usage_by_activity($templateusage);

        // Save the templateusage-ID in the offlinequiz_groups table.
        $group->templateusageid = $templateusage->get_id();
        $DB->set_field('offlinequiz_groups', 'templateusageid', $group->templateusageid, array('id' => $group->id));
    } // End else.
    return $templateusage;
}


/**
 * Deletes the PDF forms of an offlinequiz.
 *
 * @param object $offlinequiz
 */
function offlinequiz_delete_pdf_forms($offlinequiz) {
    global $DB;

    $fs = get_file_storage();

    // If the offlinequiz has just been created then there is no cmid.
    if (isset($offlinequiz->cmid)) {
        $context = context_module::instance($offlinequiz->cmid);

        // Delete PDF documents.
        $files = $fs->get_area_files($context->id, 'mod_offlinequiz', 'pdfs');
        foreach ($files as $file) {
            $file->delete();
        }
    }
    // Delete the file names in the offlinequiz groups.
    $DB->set_field('offlinequiz_groups', 'questionfilename', null, array('offlinequizid' => $offlinequiz->id));
    $DB->set_field('offlinequiz_groups', 'answerfilename', null, array('offlinequizid' => $offlinequiz->id));
    $DB->set_field('offlinequiz_groups', 'correctionfilename', null, array('offlinequizid' => $offlinequiz->id));

    // Set offlinequiz->docscreated to 0.
    $offlinequiz->docscreated = 0;
    $DB->set_field('offlinequiz', 'docscreated', 0, array('id' => $offlinequiz->id));
    return $offlinequiz;
}

/**
 * Deletes the question usages by activity for an offlinequiz. This function must not be
 * called if the offline quiz has attempts or scanned pages
 *
 * @param object $offlinequiz
 */
function offlinequiz_delete_template_usages($offlinequiz, $deletefiles = true) {
    global $CFG, $DB, $OUTPUT;

    if ($groups = $DB->get_records('offlinequiz_groups',
                                   array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
        foreach ($groups as $group) {
            if ($group->templateusageid) {
                question_engine::delete_questions_usage_by_activity($group->templateusageid);
                $group->templateusageid = 0;
                $DB->set_field('offlinequiz_groups', 'templateusageid', 0, array('id' => $group->id));
            }
        }
    }

    // Also delete the PDF forms if they have been created.
    if ($deletefiles) {
        return offlinequiz_delete_pdf_forms($offlinequiz);
    } else {
        return $offlinequiz;
    }
}

/**
 * Prints a preview for a question in an offlinequiz to Stdout.
 *
 * @param object $question
 * @param array $choiceorder
 * @param int $number
 * @param object $context
 */
function offlinequiz_print_question_preview($question, $choiceorder, $number, $context, $page) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/filter/mathjaxloader/filter.php' );

    $letterstr = 'abcdefghijklmnopqrstuvwxyz';

    echo '<div id="q' . $question->id . '" class="preview">
            <div class="question">
              <span class="number">';

    if ($question->qtype != 'description') {
        echo $number.')&nbsp;&nbsp;';
    }
    echo '    </span>';

    $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
            $question->contextid, 'question', 'questiontext', $question->id,
            $context->id, 'offlinequiz');

    // Remove leading paragraph tags because the cause a line break after the question number.
    $text = preg_replace('!^<p>!i', '', $text);

    // Filter only for tex formulas.
    $texfilter = null;
    $mathjaxfilter = null;
    $filters = filter_get_active_in_context($context);

    if (array_key_exists('mathjaxloader', $filters)) {
        $mathjaxfilter = new filter_mathjaxloader($context, array());
        $mathjaxfilter->setup($page, $context);
    }
    if (array_key_exists('tex', $filters)) {
        $texfilter = new filter_tex($context, array());
    }
    if ($mathjaxfilter) {
        $text = $mathjaxfilter->filter($text);
        if ($question->qtype != 'description') {
            foreach ($choiceorder as $key => $answer) {
                $question->options->answers[$answer]->answer = $mathjaxfilter->filter($question->options->answers[$answer]->answer);
            }
        }
    } else if ($texfilter) {
        $text = $texfilter->filter($text);
        if ($question->qtype != 'description') {
            foreach ($choiceorder as $key => $answer) {
                $question->options->answers[$answer]->answer = $texfilter->filter($question->options->answers[$answer]->answer);
            }
        }
    }

    echo $text;

    echo '  </div>';
    if ($question->qtype != 'description') {
        echo '  <div class="grade">';
        echo '(' . get_string('marks', 'quiz') . ': ' . ($question->maxmark + 0) . ')';
        echo '  </div>';

        foreach ($choiceorder as $key => $answer) {
            $answertext = $question->options->answers[$answer]->answer;

            // Remove all HTML comments (typically from MS Office).
            $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
            // Remove all paragraph tags because they mess up the layout.
            $answertext = preg_replace("/<p[^>]*>/ms", "", $answertext);
            $answertext = preg_replace("/<\/p[^>]*>/ms", "", $answertext);
            //rewrite image URLs
            $answertext = question_rewrite_question_preview_urls($answertext, $question->id,
            $question->contextid, 'question', 'answer', $question->options->answers[$answer]->id,
            $context->id, 'offlinequiz');

            echo "<div class=\"answer\">$letterstr[$key])&nbsp;&nbsp;";
            echo $answertext;
            echo "</div>";
        }
    }
    echo "</div>";
}

/**
 * Prints a list of participants to Stdout.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $coursecontext
 * @param unknown_type $systemcontext
 */
function offlinequiz_print_partlist($offlinequiz, &$coursecontext, &$systemcontext) {
    global $CFG, $COURSE, $DB, $OUTPUT;
    offlinequiz_load_useridentification();
    $offlinequizconfig = get_config('offlinequiz');

    if (!$course = $DB->get_record('course', array('id' => $coursecontext->instanceid))) {
        print_error('invalid course');
    }
    $pagesize = optional_param('pagesize', NUMBERS_PER_PAGE, PARAM_INT);
    $checkoption = optional_param('checkoption', 0, PARAM_INT);
    $listid = optional_param('listid', '', PARAM_INT);
    $lists = $DB->get_records_sql("
            SELECT id, number, name
              FROM {offlinequiz_p_lists}
             WHERE offlinequizid = :offlinequizid
          ORDER BY number ASC",
            array('offlinequizid' => $offlinequiz->id));

    // First get roleids for students from leagcy.
    if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
        print_error("No roles with capability 'mod/offlinequiz:attempt' defined in system context");
    }

    $roleids = array();
    foreach ($roles as $role) {
        $roleids[] = $role->id;
    }

    list($csql, $cparams) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
    list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
    $params = array_merge($cparams, $rparams);

    $sql = "SELECT p.id, p.userid, p.listid, u.".$offlinequizconfig->ID_field.", u.firstname, u.lastname,
                   u.alternatename, u.middlename, u.firstnamephonetic, u.lastnamephonetic, u.picture, p.checked
              FROM {offlinequiz_participants} p,
                   {offlinequiz_p_lists} pl,
                   {user} u,
                   {role_assignments} ra
             WHERE p.listid = pl.id
               AND p.userid = u.id
               AND ra.userid=u.id
               AND pl.offlinequizid = :offlinequizid
               AND ra.contextid $csql
               AND ra.roleid $rsql";

    $params['offlinequizid'] = $offlinequiz->id;
    if (!empty($listid)) {
        $sql .= " AND p.listid = :listid";
        $params['listid'] = $listid;
    }

    $countsql = "SELECT COUNT(*)
                   FROM {offlinequiz_participants} p,
                        {offlinequiz_p_lists} pl,
                        {user} u
                  WHERE p.listid = pl.id
                    AND p.userid = u.id
                    AND pl.offlinequizid = :offlinequizid";

    $cparams = array('offlinequizid' => $offlinequiz->id);
    if (!empty($listid)) {
        $countsql .= " AND p.listid = :listid";
        $cparams['listid'] = $listid;
    }

    require_once($CFG->libdir . '/tablelib.php');

    $tableparams = array('q' => $offlinequiz->id,
            'mode' => 'attendances',
            'listid' => $listid,
            'pagesize' => $pagesize,
            'strreallydel' => '');

    $table = new offlinequiz_partlist_table('mod-offlinequiz-participants', 'participants.php', $tableparams);

    // Define table columns.
    $tablecolumns = array('checkbox', 'picture', 'fullname', $offlinequizconfig->ID_field, 'number', 'attempt', 'checked');
    $tableheaders = array('<input type="checkbox" name="toggle" class="select-all-checkbox"/>',
            '', get_string('fullname'), get_string($offlinequizconfig->ID_field), get_string('participantslist', 'offlinequiz'),
            get_string('attemptexists', 'offlinequiz'), get_string('present', 'offlinequiz'));

    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($CFG->wwwroot.'/mod/offlinequiz/participants.php?mode=attendances&amp;q=' .
            $offlinequiz->id . '&amp;checkoption=' . $checkoption . '&amp;pagesize=' . $pagesize. '&amp;listid=' . $listid);

    $table->sortable(true);
    $table->no_sorting('attempt');
    $table->no_sorting('checkbox');
    if (!empty($listid)) {
        $table->no_sorting('listid');
    }
    $table->set_attribute('cellpadding', '2');
    $table->set_attribute('id', 'participants-table');
    $table->set_attribute('class', 'generaltable generalbox');

    // Start working -- this is necessary as soon as the niceties are over.
    $table->setup();

    // Add extra limits due to initials bar.
    if (!empty($countsql)) {
        $totalinitials = $DB->count_records_sql($countsql, $cparams);
        // Add extra limits due to initials bar.
        list($ttest, $tparams) = $table->get_sql_where();

        if (!empty($ttest) && (empty($checkoption) or $checkoption == 0)) {
            $sql .= ' AND ' . $ttest;
            $params = array_merge($params, $tparams);

            $countsql .= ' AND ' . $ttest;
            $cparams = array_merge($cparams, $tparams);
        }
        $total  = $DB->count_records_sql($countsql, $cparams);
    }

    if ($sort = $table->get_sql_sort()) {
        $sql .= ' ORDER BY ' . $sort;
    } else {
        $sql .= ' ORDER BY u.lastname, u.firstname';
    }

    $table->initialbars($totalinitials > 20);
    // Special settings for checkoption: show all entries on one page.
    if (!empty($checkoption) and $checkoption > 0) {
        $pagesize = $total;
        $table->pagesize($pagesize, $total);
        $participants = $DB->get_records_sql($sql, $params);
    } else {
        $table->pagesize($pagesize, $total);
        $participants = $DB->get_records_sql($sql, $params, $table->get_page_start(), $table->get_page_size());
    }

    $strreallydel  = addslashes(get_string('deletepartcheck', 'offlinequiz'));

    $sql = "SELECT COUNT(*)
              FROM {offlinequiz_results}
             WHERE userid = :userid
               AND offlinequizid = :offlinequizid
               AND status = 'complete'";
    $params = array('offlinequizid' => $offlinequiz->id);
    if ($participants) {
        foreach ($participants as $participant) {
            $user = $DB->get_record('user', array('id' => $participant->userid));
            $picture = $OUTPUT->user_picture($user, array('courseid' => $coursecontext->instanceid));

            $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $participant->userid .
                '&amp;course=' . $coursecontext->instanceid.'">'.fullname($participant).'</a>';
            $params['userid'] = $participant->userid;
            if ($DB->count_records_sql($sql, $params) > 0) {
                $attempt = true;
            } else {
                $attempt = false;
            }
            $row = array(
                    '<input type="checkbox" name="participantid[]" value="'.$participant->id.'"  class="select-multiple-checkbox"/>',
                    $picture,
                    $userlink,
                    $participant->{$offlinequizconfig->ID_field},
                    $lists[$participant->listid]->name,
                    $attempt ? "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/tick.gif\" alt=\"" .
                    get_string('attemptexists', 'offlinequiz') . "\">" :
                    "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/cross.gif\" alt=\"" .
                    get_string('noattemptexists', 'offlinequiz') . "\">",
                    $participant->checked ? "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/tick.gif\" alt=\"" .
                    get_string('ischecked', 'offlinequiz') . "\">" :
                    "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/cross.gif\" alt=\"" .
                    get_string('isnotchecked', 'offlinequiz') . "\">"
                    );
            switch ($checkoption) {
                case '0':
                    $table->add_data($row);
                    break;
                case '1':
                    if (!$attempt and $participant->checked) {
                        $table->add_data($row);
                    }
                    break;
                case '2':
                    if ($attempt and !$participant->checked) {
                        $table->add_data($row);
                    }
                    break;
            }
        }
    } else {
        // Print table.
        $table->print_initials_bar();
    }
    $table->finish_html();

    // Print "Select all" etc.
    echo '<center>';

    if (!empty($participants)) {
        echo '<form id="downloadoptions" action="participants.php" method="get">';
        echo '<input type="hidden" name="q" value="' . $offlinequiz->id . '" />';
        echo '<input type="hidden" name="mode" value="attendances" />';
        echo '<input type="hidden" name="pagesize" value="' . $pagesize . '" />';
        echo '<input type="hidden" name="listid" value="' . $listid . '" />';
        echo '<table class="boxaligncenter"><tr><td>';
        $options = array(
                'Excel' => get_string('excelformat', 'offlinequiz'),
                'ODS' => get_string('odsformat', 'offlinequiz'),
                'CSV' => get_string('csvformat', 'offlinequiz')
        );
        print_string('downloadresultsas', 'offlinequiz');
        echo "</td><td>";
        echo html_writer::select($options, 'download', '', false);
        echo '<button type="submit" class="btn btn-primary" >' . get_string('go') . '</button>';
        echo '<script type="text/javascript">'."\n<!--\n".'document.getElementById("noscriptmenuaction").style.display = "none";'.
            "\n-->\n".'</script>';
        echo "</td>\n";
        echo "<td>";
        echo "</td>\n";
        echo '</tr></table></form>';
    }

    // Print display options.
    echo '<div class="controls">';
    echo '<form id="options" action="participants.php" method="get">';
    echo '<center>';
    echo '<p>'.get_string('displayoptions', 'quiz').': </p>';
    echo '<input type="hidden" name="q" value="' . $offlinequiz->id . '" />';
    echo '<input type="hidden" name="mode" value="attendances" />';
    echo '<input type="hidden" name="listid" value="'.$listid.'" />';
    echo '<table id="participant-options" class="boxaligncenter">';
    echo '<tr align="left">';
    echo '<td><label for="pagesize">'.get_string('pagesizeparts', 'offlinequiz').'</label></td>';
    echo '<td><input type="text" id="pagesize" name="pagesize" size="3" value="'.$pagesize.'" /></td>';
    echo '</tr>';
    echo '<tr align="left">';
    echo '<td colspan="2">';

    $options = array(0 => get_string('showallparts', 'offlinequiz', $total));
    if ($course->id != SITEID) {
        $options[1] = get_string('showmissingattemptonly', 'offlinequiz');
        $options[2] = get_string('showmissingcheckonly', 'offlinequiz');
    }

    echo html_writer::select($options, 'checkoption', $checkoption);
    echo '</td></tr>';
    echo '<tr><td colspan="2" align="center">';
    echo '<button type="submit" class="btn btn-secondary" >' .get_string('go'). '</button>';
    echo '</td></tr></table>';
    echo '</center>';
    echo '</form>';
    echo '</div>';
    echo "\n";
}

/**
 * Serves a list of participants as a file.
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $fileformat
 * @param unknown_type $coursecontext
 * @param unknown_type $systemcontext
 */
function offlinequiz_download_partlist($offlinequiz, $fileformat, &$coursecontext, &$systemcontext) {
    global $CFG, $DB, $COURSE;

    offlinequiz_load_useridentification();
    $offlinequizconfig = get_config('offlinequiz');

    $filename = clean_filename(get_string('participants', 'offlinequiz') . $offlinequiz->id);

    // First get roleids for students from leagcy.
    if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
        print_error("No roles with capability 'mod/offlinequiz:attempt' defined in system context");
    }

    $roleids = array();
    foreach ($roles as $role) {
        $roleids[] = $role->id;
    }

    list($csql, $cparams) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
    list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
    $params = array_merge($cparams, $rparams);

    $sql = "SELECT p.id, p.userid, p.listid, u." . $offlinequizconfig->ID_field . ", u.firstname, u.lastname,
                   u.alternatename, u.middlename, u.firstnamephonetic, u.lastnamephonetic,
                   u.picture, p.checked
             FROM {offlinequiz_participants} p,
                  {offlinequiz_p_lists} pl,
                  {user} u,
                  {role_assignments} ra
            WHERE p.listid = pl.id
              AND p.userid = u.id
              AND ra.userid=u.id
              AND pl.offlinequizid = :offlinequizid
              AND ra.contextid $csql
              AND ra.roleid $rsql";

    $params['offlinequizid'] = $offlinequiz->id;

    // Define table headers.
    $tableheaders = array(get_string('fullname'),
                          get_string($offlinequizconfig->ID_field),
                          get_string('participantslist', 'offlinequiz'),
                          get_string('attemptexists', 'offlinequiz'),
                          get_string('present', 'offlinequiz'));

    if ($fileformat == 'ODS') {
        require_once("$CFG->libdir/odslib.class.php");

        $filename .= ".ods";
        // Creating a workbook.
        $workbook = new MoodleODSWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($filename);
        // Creating the first worksheet.
        $sheettitle = get_string('participants', 'offlinequiz');
        $myxls = $workbook->add_worksheet($sheettitle);
        // Format types.
        $format = $workbook->add_format();
        $format->set_bold(0);
        $formatbc = $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $formatb = $workbook->add_format();
        $formatb->set_bold(1);
        $formaty = $workbook->add_format();
        $formaty->set_bg_color('yellow');
        $formatc = $workbook->add_format();
        $formatc->set_align('center');
        $formatr = $workbook->add_format();
        $formatr->set_bold(1);
        $formatr->set_color('red');
        $formatr->set_align('center');
        $formatg = $workbook->add_format();
        $formatg->set_bold(1);
        $formatg->set_color('green');
        $formatg->set_align('center');

        // Print worksheet headers.
        $colnum = 0;
        foreach ($tableheaders as $item) {
            $myxls->write(0, $colnum, $item, $formatbc);
            $colnum++;
        }
        $rownum = 1;
    } else if ($fileformat == 'Excel') {
        require_once("$CFG->libdir/excellib.class.php");

        $filename .= ".xls";
        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($filename);
        // Creating the first worksheet.
        $sheettitle = get_string('participants', 'offlinequiz');
        $myxls = $workbook->add_worksheet($sheettitle);
        // Format types.
        $format = $workbook->add_format();
        $format->set_bold(0);
        $formatbc = $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $formatb = new StdClass();
        $formatb = $workbook->add_format();
        $formatb->set_bold(1);
        $formaty = $workbook->add_format();
        $formaty->set_bg_color('yellow');
        $formatc = $workbook->add_format();
        $formatc->set_align('center');
        $formatr = $workbook->add_format();
        $formatr->set_bold(1);
        $formatr->set_color('red');
        $formatr->set_align('center');
        $formatg = $workbook->add_format();
        $formatg->set_bold(1);
        $formatg->set_color('green');
        $formatg->set_align('center');

        // Print worksheet headers.
        $colnum = 0;
        foreach ($tableheaders as $item) {
            $myxls->write(0, $colnum, $item, $formatbc);
            $colnum++;
        }
        $rownum = 1;
    } else if ($fileformat == 'CSV') {
        $filename .= ".csv";

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        $headers = implode(", ", $tableheaders);

        echo $headers . " \n";
    }

    $lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id));
    $participants = $DB->get_records_sql($sql, $params);
    if ($participants) {
        foreach ($participants as $participant) {
            $userid = $participant->userid;
            $attempt = false;
            $sql = "SELECT COUNT(*)
                      FROM {offlinequiz_results}
                     WHERE userid = :userid
                       AND offlinequizid = :offlinequizid
                       AND status = 'complete'";
            if ($DB->count_records_sql($sql, array('userid' => $userid, 'offlinequizid' => $offlinequiz->id)) > 0) {
                $attempt = true;
            }
            $row = array(
                    fullname($participant),
                    $participant->{$offlinequizconfig->ID_field},
                    $lists[$participant->listid]->name,
                    $attempt ? get_string('yes') : get_string('no'),
                    $participant->checked ? get_string('yes') : get_string('no')
                    );
            if ($fileformat == 'Excel' or $fileformat == 'ODS') {
                $colnum = 0;
                foreach ($row as $item) {
                    $myxls->write($rownum, $colnum, $item, $format);
                    $colnum++;
                }
                $rownum++;
            } else if ($fileformat == 'CSV') {
                $text = implode(", ", $row);
                echo $text . "\n";
            }
        }
    }

    if ($fileformat == 'Excel' or $fileformat == 'ODS') {
        $workbook->close();
    }
    exit;
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
        $showquestiontext = true, $return = true, $shorttitle = false) {
    global $COURSE;

    $result = '';

    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $formatoptions->para = false;

    $questiontext = strip_tags(question_utils::to_plain_text($question->questiontext, $question->questiontextformat,
                                                             array('noclean' => true, 'para' => false)));
    $questiontitle = strip_tags(format_text($question->name, $question->questiontextformat, $formatoptions, $COURSE->id));

    $result .= '<span class="questionname" title="' . $questiontitle . '">';
    if ($shorttitle && strlen($questiontitle) > 25) {
        $questiontitle = shorten_text($questiontitle, 25, false, '...');
    }

    if ($showicon) {
        $result .= print_question_icon($question, true);
        echo ' ';
    }

    if ($shorttitle) {
        $result .= $questiontitle;
    } else {
        $result .= shorten_text(format_string($question->name), 200) . '</span>';
    }

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
function offlinequiz_add_questionlist_to_group($questionids, $offlinequiz, $offlinegroup,
        $fromofflinegroup = null, $maxmarks = null) {
    global $DB;

    if (offlinequiz_has_scanned_pages($offlinequiz->id)) {
        return false;
    }

    // Don't add the same question twice.
    foreach ($questionids as $questionid) {
        $slots = $DB->get_records('offlinequiz_group_questions',
                array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $offlinegroup->id),
                'slot', 'questionid, slot, page, id');

        if (array_key_exists($questionid, $slots)) {
            continue;
        }

        $trans = $DB->start_delegated_transaction();
        // If the question is already in another group, take the maxmark of that.
        $maxmark = null;
        if ($fromofflinegroup && $oldmaxmark = $DB->get_field('offlinequiz_group_questions', 'maxmark',
            array('offlinequizid' => $offlinequiz->id,
            'offlinegroupid' => $fromofflinegroup,
            'questionid' => $questionid))) {
            $maxmark = $oldmaxmark;
        } else if ($maxmarks && array_key_exists($questionid, $maxmarks)) {
            $maxmark = $maxmarks[$questionid];
        }

        $maxpage = 1;
        $numonlastpage = 0;
        foreach ($slots as $slot) {
            if ($slot->page > $maxpage) {
                $maxpage = $slot->page;
                $numonlastpage = 1;
            } else {
                $numonlastpage += 1;
            }
        }

        // Add the new question instance.
        $slot = new stdClass();
        $slot->offlinequizid = $offlinequiz->id;
        $slot->offlinegroupid = $offlinegroup->id;
        $slot->questionid = $questionid;

        if ($maxmark !== null) {
            $slot->maxmark = $maxmark;
        } else {
            $slot->maxmark = $DB->get_field('question', 'defaultmark', array('id' => $questionid));
        }

        $lastslot = end($slots);
        if ($lastslot) {
            $slot->slot = $lastslot->slot + 1;
        } else {
            $slot->slot = 1;
        }
        $slot->page = 0;

        if (!$slot->page) {
            if ($offlinequiz->questionsperpage && $numonlastpage >= $offlinequiz->questionsperpage) {
                $slot->page = $maxpage + 1;
            } else {
                $slot->page = $maxpage;
            }
        }
        $DB->insert_record('offlinequiz_group_questions', $slot);
        $trans->allow_commit();
    }
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
function offlinequiz_add_random_questions($offlinequiz, $offlinegroup, $categoryid,
        $number, $recurse, $preventsamequestion) {
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
               AND q.qtype IN ('multichoice', 'multichoiceset') ";
    if (!$preventsamequestion) {
        // Find all questions in the selected categories that are not in the offline group yet.
        $sql .= "AND NOT EXISTS (SELECT 1
                                   FROM {offlinequiz_group_questions} ogq
                                  WHERE ogq.questionid = q.id
                                    AND ogq.offlinequizid = :offlinequizid
                                    AND ogq.offlinegroupid = :offlinegroupid)";
    } else {
        // Find all questions in the selected categories that are not in the offline test yet.
        $sql .= "AND NOT EXISTS (SELECT 1
                                   FROM {offlinequiz_group_questions} ogq
                                  WHERE ogq.questionid = q.id
                                    AND ogq.offlinequizid = :offlinequizid)";
    }
    $qcparams['offlinequizid'] = $offlinequiz->id;
    $qcparams['offlinegroupid'] = $offlinegroup->id;

    $questionids = $DB->get_fieldset_sql($sql, $qcparams);
    srand(microtime() * 1000000);
    shuffle($questionids);

    $chosenids = array();
    while (($questionid = array_shift($questionids)) && $number > 0) {
        $chosenids[] = $questionid;
        $number -= 1;
    }

    $maxmarks = array();
    if ($chosenids) {
        // Get the old maxmarks in case questions are already in other offlinequiz groups.
        list($qsql, $params) = $DB->get_in_or_equal($chosenids, SQL_PARAMS_NAMED);

        $sql = "SELECT id, questionid, maxmark
                  FROM {offlinequiz_group_questions}
                 WHERE offlinequizid = :offlinequizid
                   AND questionid $qsql";
        $params['offlinequizid'] = $offlinequiz->id;

        if ($slots = $DB->get_records_sql($sql, $params)) {
            foreach ($slots as $slot) {
                if (!array_key_exists($slot->questionid, $maxmarks)) {
                    $maxmarks[$slot->questionid] = $slot->maxmark;
                }
            }
        }
    }

    offlinequiz_add_questionlist_to_group($chosenids, $offlinequiz, $offlinegroup, null, $maxmarks);
}

/**
 *
 * @param unknown $offlinequiz
 * @param unknown $questionids
 */
function offlinequiz_remove_questionlist($offlinequiz, $questionids) {
    global $DB;

    // Go through the question IDs and remove them if they exist.
    // We do a DB commit after each question ID to make things simpler.
    foreach ($questionids as $questionid) {
        // Retrieve the slots indexed by id.
        $slots = $DB->get_records('offlinequiz_group_questions',
                array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $offlinequiz->groupid),
                'slot');

        // Build an array with slots indexed by questionid and indexed by slot number.
        $questionslots = array();
        $slotsinorder = array();
        foreach ($slots as $slot) {
            $questionslots[$slot->questionid] = $slot;
            $slotsinorder[$slot->slot] = $slot;
        }

        if (!array_key_exists($questionid, $questionslots)) {
            continue;
        }

        $slot = $questionslots[$questionid];

        $nextslot = null;
        $prevslot = null;
        if (array_key_exists($slot->slot + 1, $slotsinorder)) {
            $nextslot = $slotsinorder[$slot->slot + 1];
        }
        if (array_key_exists($slot->slot - 1, $slotsinorder)) {
            $prevslot = $slotsinorder[$slot->slot - 1];
        }
        $lastslot = end($slotsinorder);

        $trans = $DB->start_delegated_transaction();

        // Reduce the page numbers of the following slots if there is no previous slot
        // or the page number of the previous slot is smaller than the page number of the current slot.
        $removepage = false;
        if ($nextslot && $nextslot->page > $slot->page) {
            if (!$prevslot || $prevslot->page < $slot->page) {
                $removepage = true;
            }
        }

        // Delete the slot.
        $DB->delete_records('offlinequiz_group_questions',
                array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $offlinequiz->groupid,
                      'id' => $slot->id));

        // Reduce the slot number in the following slots if there are any.
        // Also reduce the page number if necessary.
        if ($nextslot) {
            for ($curslotnr = $nextslot->slot ; $curslotnr <= $lastslot->slot; $curslotnr++) {
                if ($slotsinorder[$curslotnr]) {
                    if ($removepage) {
                        $slotsinorder[$curslotnr]->page = $slotsinorder[$curslotnr]->page - 1;
                    }
                    // Reduce the slot number by one.
                    $slotsinorder[$curslotnr]->slot = $slotsinorder[$curslotnr]->slot - 1;
                    $DB->update_record('offlinequiz_group_questions', $slotsinorder[$curslotnr]);
                }
            }
        }

        $trans->allow_commit();
   }
}
