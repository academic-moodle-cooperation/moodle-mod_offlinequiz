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
 * Internal library of functions for module offlinequiz
 *
 * All the offlinequiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/engine/questionusage.php');

defined('MOODLE_INTERNAL') || die();

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
define("OFFLINEQUIZ_IMPORT_SINGLE_ERROR", "17"); // This is not really an error. it occures, when multipage answer sheets are scanned.
define("OFFLINEQUIZ_IMPORT_DOUBLE_PAGE_ERROR", "18"); // New error for double pages (e.g. page 2 occurs twice for as student).
define("OFFLINEQUIZ_IMPORT_DIFFERING_PAGE_ERROR", "19"); // New error for double pages that have different results (rawdata).

// Codes for lists of participants.
define("OFFLINEQUIZ_PART_FATAL_ERROR", "21");   // Over 20 indicates, it is a participants error.
define("OFFLINEQUIZ_PART_INSECURE_ERROR", "22");
define("OFFLINEQUIZ_PART_USER_ERROR", "23");
define("OFFLINEQUIZ_PART_LIST_ERROR", "24");
define("OFFLINEQUIZ_IMPORT_NUMUSERS", "50");

define('OFFLINEQUIZ_GROUP_LETTERS', "ABCDEFGHIJKL");  // Letters for naming offlinequiz groups.

define('NUMBERS_PER_PAGE', 30);                 // Number of students on participants list.
define('OQ_IMAGE_WIDTH', 860);                  // Width of correction form.

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
                $newslot = $newquba->add_question($slotquestion, $qinstances[$slotquestion->id]->grade);
                $qa = $newquba->get_question_attempt($newslot);
                $qa->start('immediatefeedback', 1, array('_order' =>  $order));
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
            $quba->questionattempts[$record->slot] =
            question_attempt::load_from_records($records,
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
 * @param unknown_type $offlinequiz
 * @param unknown_type $groupid
 * @param unknown_type $questionsonly
 * @return string
 */
function offlinequiz_get_group_questions($offlinequiz, $groupid = 0, $questionsonly = false) {
    global $DB;

    if (!$groupid) {
        $groupid = $offlinequiz->groupid;
    }

    $sql = "SELECT questionid
              FROM {offlinequiz_group_questions}
             WHERE offlinequizid = :offlinequizid
               AND offlinegroupid = :offlinegroupid";

    if ($questionsonly) {
        $sql .= " AND questionid <> 0 ";
    }
    $sql .= "ORDER BY position ASC";
    $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $groupid);
    $questions = $DB->get_fieldset_sql($sql, $params);

    return implode(',', $questions);
}

/**
 * Returns a comma separated list of question ids for an offlinequiz group
 *
 * @param string $offlinequiz The offlinequiz object containing the questions in a
 * comma separated layout list.
 *
 * @return string comma separated list of question ids, without page breaks.
 */
function offlinequiz_questions_in_offlinequiz($layout) {
    $questions = str_replace(',0', '', offlinequiz_clean_layout($layout, true));
    if ($questions === '0') {
        return '';
    } else {
        return $questions;
    }
}

/**
 *
 * @param mixed $offlinequiz The offlinequiz
 * @return array returns an array of offline group numbers
 */
function offlinequiz_get_empty_groups($offlinequiz) {
    global $DB;

    $emptygroups = array();

    if ($groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
        foreach ($groups as $group) {
            $questions = explode(',', offlinequiz_get_group_questions($offlinequiz, $group->id));
            if (count($questions) < 2) {
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
    $questionids = offlinequiz_questions_in_offlinequiz(offlinequiz_get_group_questions($offlinequiz, $group->id));
    foreach (explode(',', $questionids) as $key => $id) {
        if ($id == $questionid) {
            return $key + 1;
        }
    }
    return null;
}

/**
 * Save the questions of an offlinequiz in the database.
 * @param object $offlinequiz the offlinequiz object.
 * @param array $questionids an array of question IDs.
 * @return .
 */
function offlinequiz_save_questions($offlinequiz, $questionids = null) {
    global $DB;

    if (empty($questionids)) {
        $questionids = explode(',', $offlinequiz->questions);
    }

    $DB->delete_records('offlinequiz_group_questions', array('offlinequizid' => $offlinequiz->id,
            'offlinegroupid' => $offlinequiz->groupid));

    $position = 1;
    foreach ($questionids as $qid) {
        $data = new stdClass();
        $data->offlinequizid = $offlinequiz->id;
        $data->offlinegroupid = $offlinequiz->groupid;
        $data->questionid = $qid;
        $data->position = $position++;

        $DB->insert_record('offlinequiz_group_questions', $data);
    }
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

        $layout = offlinequiz_get_group_questions($offlinequiz, $group->id, true);
        $questionids = explode(',', $layout);

        list($qsql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);

        $numquestions = $DB->count_records_sql("SELECT COUNT(id) FROM {question} WHERE qtype <> 'description' AND id $qsql", $params);
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
 * Re-paginates the offlinequiz layout
 *
 * @return string         The new layout string
 * @param string $layout  The string representing the offlinequiz layout.
 * @param integer $perpage The number of questions per page
 * @param boolean $shuffle Should the questions be reordered randomly?
 */
function offlinequiz_repaginate($layout, $perpage, $shuffle=false) {
    $layout = str_replace(',0', '', $layout); // Remove existing page breaks
    $questions = explode(',', $layout);
    if ($shuffle) {
        srand((float)microtime() * 1000000); // For php < 4.2
        shuffle($questions);
    }
    $i = 1;
    $layout = '';
    foreach ($questions as $question) {
        if ($perpage and $i > $perpage) {
            $layout .= '0,';
            $i = 1;
        }
        $layout .= $question.',';
        $i++;
    }
    return $layout.'0';
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

    // Delete the scanned page
    $DB->delete_records('offlinequiz_scanned_pages', array('id' => $page->id));
    // Delete the choices made on the page
    $DB->delete_records('offlinequiz_choices', array('scannedpageid' => $page->id));
    // Delete the corner coordinates
    $DB->delete_records('offlinequiz_page_corners', array('scannedpageid' => $page->id));

    // If there is no scannedpage for the result anymore, we also delete the result
    if ($resultid && !$DB->get_records('offlinequiz_scanned_pages', array('resultid' => $resultid))) {
        // Delete the result
        $DB->delete_records('offlinequiz_results', array('id' => $resultid));
    }

    // JZ: also delete the image files associated with the deleted page
    if ($page->filename && $file = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $page->filename)) {
        $file->delete();
    }
    if ($page->warningfilename && $file = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $page->warningfilename)) {
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

    // Delete the scanned participants page
    $DB->delete_records('offlinequiz_scanned_p_pages', array('id' => $page->id));
    // Delete the choices made on the page
    $DB->delete_records('offlinequiz_p_choices', array('scannedppageid' => $page->id));

    // JZ: also delete the image files associated with the deleted page
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
        return $DB->count_records_select('offlinequiz_results', "offlinequizid = :offlinequizid AND status = 'complete'", $params, 'COUNT(id)');
    }
}

/**
 * Delete an offlinequiz result.
 * 
 * @param mixed $attempt an integer attempt id or an attempt object
 *      (row of the offlinequiz_results table).
 * @param object $offlinequiz the offlinequiz object.
 */
function offlinequiz_delete_result($resultid, $context) {
    global $DB;

    // First delete the result itself.
    $DB->delete_records('offlinequiz_results', array('id' => $resultid));

    // Now we delete all scanned pages that referred to the result.
    $scannedpages = $DB->get_records_sql("
            SELECT *
              FROM {offlinequiz_scanned_pages}
             WHERE resultid = :resultid", array('resultid' => $resultid));

    foreach ($scannedpages as $page) {
        offlinequiz_delete_scanned_page($page, $context);
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

    // Update grade item and send all grades to gradebook
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


// Other offlinequiz functions ////////////////////////////////////////////////////

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
    // There has to be responses or (graded)sheetfeedback

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
 * Splits the little formula from $offlinequizconfig->useridentification
 * into prefix, postfix, digits and field and stores them in config variables.
 */
function offlinequiz_load_useridentification() {
    global $CFG, $DB;

    $offlinequizconfig = get_config('offlinequiz');

    $errorstr = "Incorrect formula for user identification. Please contact your system administrator to change the settings.";
    $start = strpos($offlinequizconfig->useridentification, '[');
    $end = strpos($offlinequizconfig->useridentification, ']');
    $digits = substr($offlinequizconfig->useridentification, $start+1, $end-$start-1);
    if (!is_numeric($digits) or $digits > 9) {
        print_error($errorstr, 'offlinequiz');
    }
    $prefix = substr($offlinequizconfig->useridentification, 0, $start);
    $postfix = substr($offlinequizconfig->useridentification, $end+1, strpos($offlinequizconfig->useridentification, '=')-$end-1);
    $field = substr($offlinequizconfig->useridentification, strpos($offlinequizconfig->useridentification, '=')+1);

    $testuser = $DB->get_record('user', array('id' => 1));

    if (!isset($testuser->{$field})) {
        print_error($errorstr, 'offlinequiz');
    }
    set_config('ID_digits', $digits, 'offlinequiz');
    set_config('ID_prefix', $prefix, 'offlinequiz');
    set_config('ID_postfix', $postfix, 'offlinequiz');
    set_config('ID_field', $field, 'offlinequiz');
}

/**
 * Clean the question layout from various possible anomalies:
 * - Remove consecutive ","'s
 * - Remove duplicate question id's
 * - Remove extra "," from beginning and end
 * - Finally, add a ",0" in the end if there is none
 *
 * @param $string $layout the offlinequiz layout to clean up, usually from $offlinequiz->questions.
 * @param bool $removeemptypages If true, remove empty pages from the offlinequiz. False by default.
 * @return $string the cleaned-up layout
 */
function offlinequiz_clean_layout($layout, $removeemptypages = false) {
    // Remove repeated ','s. This can happen when a restore fails to find the right
    // id to relink to.
    $layout = preg_replace('/,{2,}/', ',', trim(trim($layout), ','));
    // Remove duplicate question ids
    $layout = explode(',', $layout);
    $cleanerlayout = array();
    $seen = array();
    foreach ($layout as $item) {
        if ($item == 0) {
            $cleanerlayout[] = '0';
        } else if (!in_array($item, $seen)) {
            $cleanerlayout[] = $item;
            $seen[] = $item;
        }
    }

    if ($removeemptypages) {
        // Avoid duplicate page breaks
        $layout = $cleanerlayout;
        $cleanerlayout = array();
        $stripfollowingbreaks = true; // Ensure breaks are stripped from the start.
        foreach ($layout as $item) {
            if ($stripfollowingbreaks && $item == 0) {
                continue;
            }
            $cleanerlayout[] = $item;
            $stripfollowingbreaks = $item == 0;
        }
    }

    // Add a page break at the end if there is none
    if (end($cleanerlayout) !== '0') {
        $cleanerlayout[] = '0';
    }

    $result = implode(',', $cleanerlayout);
    return $result;
}


/**
 * Creates an array of maximum grades for a offlinequiz
 *
 * The grades are extracted for the offlinequiz_question_instances table.
 * @param object $offlinequiz The offlinequiz settings.
 * @return array of grades indexed by question id. These are the maximum
 *      possible grades that students can achieve for each of the questions.
 */
function offlinequiz_get_all_question_grades($offlinequiz) {
    global $CFG, $DB;

    $questionlist = offlinequiz_questions_in_offlinequiz($offlinequiz->questions);
    if (empty($questionlist)) {
        return array();
    }

    $params = array($offlinequiz->id);
    $wheresql = '';
    if (!is_null($questionlist)) {
        list($usql, $question_params) = $DB->get_in_or_equal(explode(',', $questionlist));
        $wheresql = " AND question $usql ";
        $params = array_merge($params, $question_params);
    }
    $instances = $DB->get_records_sql("
            SELECT question, grade, id
              FROM {offlinequiz_q_instances}
             WHERE offlinequiz = ? $wheresql", $params);

    $list = explode(",", $questionlist);
    $grades = array();

    foreach ($list as $qid) {
        if (isset($instances[$qid])) {
            $grades[$qid] = $instances[$qid]->grade;
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
 * Returns the number of questions in the offlinequiz layout
 *
 * @param string $layout the string representing the offlinequiz layout.
 * @return int The number of questions in the offlinequiz.
 */
function offlinequiz_number_of_questions_in_offlinequiz($layout) {
    $layout = offlinequiz_questions_in_offlinequiz(offlinequiz_clean_layout($layout));
    $count = substr_count($layout, ',');
    if ($layout !== '') {
        $count++;
    }
    return $count;
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

    $sql = 'SELECT COALESCE((SELECT SUM(grade)
              FROM {offlinequiz_q_instances} oqi,
                   {offlinequiz_group_questions} ogq
             WHERE oqi.offlinequiz = :offlinequizid1
               AND ogq.questionid = oqi.question
               AND ogq.offlinequizid = :offlinequizid2
               AND ogq.offlinegroupid = :groupid1) , 0)';

    $params = array('offlinequizid1' => $offlinequiz->id,
            'offlinequizid2' => $offlinequiz->id,
            'groupid1' => $offlinequiz->groupid);

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
function offlinequiz_update_sumgrades($offlinequiz, $groupid = null) {
    global $DB;

    if (!empty($groupid)) {
        $offlinequiz->groupid = $groupid;
    }
    $sql = 'UPDATE {offlinequiz_groups}
               SET sumgrades = COALESCE((
                   SELECT SUM(grade)
                     FROM {offlinequiz_q_instances} oqi,
                          {offlinequiz_group_questions} ogq
                    WHERE oqi.offlinequiz = :offlinequizid1
                      AND ogq.questionid = oqi.question
                      AND ogq.offlinequizid = :offlinequizid2
                      AND ogq.offlinegroupid = :groupid1
                      ), 0)
             WHERE offlinequizid = :offlinequizid3
               AND id = :groupid2';

    $params = array('offlinequizid1' => $offlinequiz->id,
            'offlinequizid2' => $offlinequiz->id,
            'offlinequizid3' => $offlinequiz->id,
            'groupid1' => $offlinequiz->groupid,
            'groupid2' => $offlinequiz->groupid);
    $DB->execute($sql, $params);

    $sumgrades = $DB->get_field('offlinequiz_groups', 'sumgrades', array('id' => $offlinequiz->groupid));

    return $DB->get_field('offlinequiz_groups', 'sumgrades', array('id' => $offlinequiz->groupid));
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

    if (!$offlinequiz_group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'number' => $groupnumber))) {
        if ($groupnumber > 0 && $groupnumber <= $offlinequiz->numgroups) {
            $offlinequiz_group = offlinequiz_add_group( $offlinequiz->id, $groupnumber);
        }
    }
    return $offlinequiz_group;
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

    // Note: numberofpages and templateusageid will be filled later

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
 * @copyright  2012 The University of Vienna
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
        $options->marks = self::extract($offlinequiz->review, OFFLINEQUIZ_REVIEW_MARKS) ? question_display_options::MARK_AND_MAX : question_display_options::HIDDEN;
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

        // The teacher should be shown everything
        $options->attempt = question_display_options::VISIBLE;
        $options->marks = question_display_options::MARK_AND_MAX;
        $options->correctness = question_display_options::VISIBLE;
        $options->feedback = question_display_options::VISIBLE;
        $options->rightanswer = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::VISIBLE;
        $options->sheetfeedback = question_display_options::VISIBLE;
        $options->gradedsheetfeedback = question_display_options::VISIBLE;

        // Show a link to the comment box only for closed attempts
        // Show a link to the comment box only for closed attempts
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
    if (!question_has_capability_on($question, 'use', $question->category)) {
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

        $layout = offlinequiz_get_group_questions($offlinequiz, $group->id);

        if ($offlinequiz->shufflequestions) {
            $offlinequiz->groupid = $group->id;

            $layout = offlinequiz_repaginate($layout, 0, true);

            // We don't want to save the shuffled questions, they are only used to create the question usage
            //          $questionids = explode(',', $layout);
            //          offlinequiz_save_questions($offlinequiz, $questionids);
            //          $layout = offlinequiz_get_group_questions($offlinequiz, $group->id);
        }

        // We have to use our own class s.t. we can use the clone function to create results.
        $templateusage = offlinequiz_make_questions_usage_by_activity('mod_offlinequiz', $context);
        $templateusage->set_preferred_behaviour('immediatefeedback');

        $questionids = explode(',', $layout);
        if (!$questionids) {
            print_error(get_string('noquestionsfound', 'offlinequiz'), 'view.php?q='.$offlinequiz->id);
        }

        // Gets database raw data for the questions.
        $questiondata = question_load_questions($questionids);

        // Get the question instances for initial markmarks
        $qinstances = $DB->get_records_sql('SELECT question, grade FROM {offlinequiz_q_instances} WHERE offlinequiz = :offlinequizid',
                array('offlinequizid' => $offlinequiz->id));

        foreach ($questionids as $questionid) {
            if ($questionid) {
                // Convert the raw data of multichoice questions to a real question definition object.
                if (!$offlinequiz->shuffleanswers) {
                    $questiondata[$questionid]->options->shuffleanswers = false;
                }
                $question = question_bank::make_question($questiondata[$questionid]);
                // We only add multichoice questions which are needed for grading
                if ($question->get_type_name() == 'multichoice' || $question->get_type_name() == 'multichoiceset') {
                    $templateusage->add_question($question, $qinstances[$question->id]->grade);
                }
            }
        }

        // Create attempts for all questions (fixes order of the answers if shuffleanswers is active).
        $templateusage->start_all_questions();

        // Save the template question usage to the DB.
        question_engine::save_questions_usage_by_activity($templateusage);

        // Save the templateusage-ID in the offlinequiz_groups table
        $group->templateusageid = $templateusage->get_id();
        $DB->set_field('offlinequiz_groups', 'templateusageid', $group->templateusageid, array('id' => $group->id));
    } // End else
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
    $context = context_module::instance($offlinequiz->cmid);

    // Delete PDF documents
    $files = $fs->get_area_files($context->id, 'mod_offlinequiz', 'pdfs');
    foreach ($files as $file) {
        $file->delete();
    }

    // Set offlinequiz->docscreated to 0
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

    if ($groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
        foreach ($groups as $group) {
            if ($group->templateusageid) {
                question_engine::delete_questions_usage_by_activity($group->templateusageid);
                $group->templateusageid = 0;
                $DB->set_field('offlinequiz_groups', 'templateusageid', 0, array('id' => $group->id));
            }
        }

        // empty pagenumbers and usage slots
        $sql = "UPDATE {offlinequiz_group_questions}
                   SET usageslot = NULL,
                       pagenumber = NULL
                 WHERE offlinequizid = :offlinequizid
                 ";
        $params = array('offlinequizid' => $offlinequiz->id);
        $DB->execute($sql, $params);
    }

    // also delete the PDF forms if they have been created.
    if ($deletefiles) {
        return offlinequiz_delete_pdf_forms($offlinequiz);
    } else {
        return true;
    }
}


// /**
//  * Rewrite the PLUGINFILE urls in the questiontext, when viewing the question
//  * text outside and attempt (for example, in the question bank listing or in the
//  * quiz statistics report).
//  *
//  * @param string $answertext the question answer text.
//  * @param int $contextid the context the text is being displayed in.
//  * @param string $component component
//  * @param array $answerid the answer id
//  * @param array $options
//  * @return string $questiontext with URLs rewritten.
//  */
// function offlinequiz_rewrite_answertext_preview_urls($answertext, $contextid,
//      $component, $answerid, $options=null) {

//  return file_rewrite_pluginfile_urls($answertext, 'pluginfile.php', $contextid,
//          'question', 'answertext_preview', "$component/$answerid", $options);
// }


/**
 * Prints a preview for a question in an offlinequiz to Stdout.
 * 
 * @param object $question
 * @param array $choiceorder
 * @param int $number
 * @param object $context
 */
function offlinequiz_print_question_preview($question, $choiceorder, $number, $context) {
    global $CFG, $DB;

    $letterstr = 'abcdefghijklmnopqrstuvwxyz';

    echo '<div id="q' . $question->id . '" class="preview">
            <div class="question">
              <span class="number">';

    if ($question->qtype != DESCRIPTION) {
        echo $number.')&nbsp;&nbsp;';
    }
    echo '    </span>';

    $text = question_rewrite_questiontext_preview_urls($question->questiontext,
            $context->id, 'offlinequiz', $question->id);

    // filter only for tex formulas
    $texfilteractive = $DB->get_field('filter_active', 'active', array('filter' => 'filter/tex', 'contextid' => 1));
    if ($texfilteractive) {
        $tex_filter = new filter_tex($context, array());
    }

    if ($tex_filter) {
        $text = $tex_filter->filter($text);
        if ($question->qtype != DESCRIPTION) {
            foreach ($choiceorder as $key => $answer) {
                $question->options->answers[$answer]->answer = $tex_filter->filter($question->options->answers[$answer]->answer);
            }
        }
    }
    echo $text;

    echo '  </div>
            <div class="grade">';

    if ($question->qtype != DESCRIPTION) {
        echo '(' . get_string('marks', 'quiz') . ': ' . ($question->maxgrade + 0) . ')';
    }

    echo '  </div>';

    if ($question->qtype != DESCRIPTION) {

        foreach ($choiceorder as $key => $answer) {
            $answertext = $question->options->answers[$answer]->answer;
            // filter only for tex formulas
            /* if ($tex_filter) { */
            /*  $answertext = $tex_filter->filter($answertext); */
            /* } */

            // remove all HTML comments (typically from MS Office).
            $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
            // remove all paragraph tags because they mess up the layout
            $answertext = preg_replace("/<p[^>]*>/ms", "", $answertext);
            $answertext = preg_replace("/<\/p[^>]*>/ms", "", $answertext);
            //          $answertext = offlinequiz_rewrite_answertext_preview_urls($answertext,
            //                                 $context->id, 'offlinequiz', $answer);

            echo "<div class=\"answer\">$letterstr[$key])&nbsp;&nbsp;";
            echo $answertext;
            echo "</div>";
        }
    } else {
        echo "<div class=\"answer\"></div>\n";
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

    // First get roleids for students from leagcy
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

    $sql = "SELECT p.id, p.userid, p.listid, u.".$offlinequizconfig->ID_field.", u.firstname, u.lastname, u.picture, p.checked
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
        $sql.=" AND p.listid = :listid";
        $params['listid'] = $listid;
    }

    $countsql="SELECT COUNT(*)
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

    // Define table columns
    $tablecolumns = array('checkbox', 'picture', 'fullname', $offlinequizconfig->ID_field, 'number', 'attempt', 'checked');
    $tableheaders = array('<input type="checkbox" name="toggle"
            onClick="if (this.checked) {select_all_in(\'DIV\', null, \'tablecontainer\');} else {deselect_all_in(\'DIV\', null, \'tablecontainer\');}"/>',
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

    // Start working -- this is necessary as soon as the niceties are over
    $table->setup();

    // Add extra limits due to initials bar
    if (!empty($countsql)) {
        $totalinitials = $DB->count_records_sql($countsql, $cparams);
        // Add extra limits due to initials bar
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
        $sql.=' ORDER BY '.$sort;
    } else {
        $sql.=' ORDER BY u.lastname, u.firstname';
    }

    $table->initialbars($totalinitials>20);
    // Special settings for checkoption: show all entries on one page
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

            $userlink = '<a href="'.$CFG->wwwroot.'/user/view.php?id=' . $participant->userid . '&amp;course=' . $coursecontext->instanceid.'">'.fullname($participant).'</a>';
            $params['userid'] = $participant->userid;
            if ($DB->count_records_sql($sql, $params) > 0) {
                $attempt = true;
            } else {
                $attempt = false;
            }
            $row = array(
                    '<input type="checkbox" name="participantid[]" value="'.$participant->id.'" />',
                    $picture,
                    $userlink,
                    $participant->{$offlinequizconfig->ID_field},
                    $lists[$participant->listid]->name,
                    $attempt ? "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/tick.gif\" alt=\"" .
                    get_string('attemptexists', 'offlinequiz') . "\">" : "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/cross.gif\" alt=\"" .
                    get_string('noattemptexists', 'offlinequiz') . "\">",
                    $participant->checked ? "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/tick.gif\" alt=\"" .
                    get_string('ischecked', 'offlinequiz') . "\">" : "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/cross.gif\" alt=\"" .
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
        // Print table
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
        $options = array('CSV' => get_string('CSVformat', 'offlinequiz'),
                'ODS' => get_string('ODSformat', 'offlinequiz'),
                'Excel' => get_string('Excelformat', 'offlinequiz'));
        print_string('downloadresultsas', 'offlinequiz');
        echo "</td><td>";
        echo html_writer::select($options, 'download', '', array('' => 'choosedots'),
                array('onchange' => 'this.form.submit(); return true;'));
        echo '<noscript id="noscriptmenuaction" style="display: inline;"><div>';
        echo '<input type="submit" value="' . get_string('go') . '" /></div></noscript>';
        echo '<script type="text/javascript">'."\n<!--\n".'document.getElementById("noscriptmenuaction").style.display = "none";'."\n-->\n".'</script>';
        echo "</td>\n";
        echo "<td>";
        echo "</td>\n";
        echo '</tr></table></form>';
    }

    // Print display options
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
    echo '<input type="submit" value="'.get_string('go').'" />';
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

    // First get roleids for students from leagcynts'
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

    $sql = "SELECT p.id, p.userid, p.listid, u.".$offlinequizconfig->ID_field.", u.firstname, u.lastname, u.picture, p.checked
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

    // Define table columns
    $tablecolumns = array('fullname', $offlinequizconfig->ID_field, 'list', 'attempt', 'checked');
    $tableheaders = array(get_string('fullname'), get_string($offlinequizconfig->ID_field), get_string('participantslist', 'offlinequiz'),
            get_string('attemptexists', 'offlinequiz'), get_string('present', 'offlinequiz'));

    if ($fileformat =='ODS') {
        require_once("$CFG->libdir/odslib.class.php");

        $filename .= ".ods";
        // Creating a workbook
        $workbook = new MoodleODSWorkbook("-");
        // Sending HTTP headers
        $workbook->send($filename);
        // Creating the first worksheet
        $sheettitle = get_string('participants', 'offlinequiz');
        $myxls =& $workbook->add_worksheet($sheettitle);
        // Format types
        $format =& $workbook->add_format();
        $format->set_bold(0);
        $formatbc =& $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $formatb =& $workbook->add_format();
        $formatb->set_bold(1);
        $formaty =& $workbook->add_format();
        $formaty->set_bg_color('yellow');
        $formatc =& $workbook->add_format();
        $formatc->set_align('center');
        $formatr =& $workbook->add_format();
        $formatr->set_bold(1);
        $formatr->set_color('red');
        $formatr->set_align('center');
        $formatg =& $workbook->add_format();
        $formatg->set_bold(1);
        $formatg->set_color('green');
        $formatg->set_align('center');

        // Print worksheet headers
        $colnum = 0;
        foreach ($tableheaders as $item) {
            $myxls->write(0, $colnum, $item, $formatbc);
            $colnum++;
        }
        $rownum=1;
    } else if ($fileformat =='Excel') {
        require_once("$CFG->libdir/excellib.class.php");

        $filename .= ".xls";
        // Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        $workbook->send($filename);
        // Creating the first worksheet
        $sheettitle = get_string('participants', 'offlinequiz');
        $myxls =& $workbook->add_worksheet($sheettitle);
        // Format types
        $format =& $workbook->add_format();
        $format->set_bold(0);
        $formatbc =& $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $formatb =& $workbook->add_format();
        $formatb->set_bold(1);
        $formaty =& $workbook->add_format();
        $formaty->set_bg_color('yellow');
        $formatc =& $workbook->add_format();
        $formatc->set_align('center');
        $formatr =& $workbook->add_format();
        $formatr->set_bold(1);
        $formatr->set_color('red');
        $formatr->set_align('center');
        $formatg =& $workbook->add_format();
        $formatg->set_bold(1);
        $formatg->set_color('green');
        $formatg->set_align('center');

        // Print worksheet headers
        $colnum = 0;
        foreach ($tableheaders as $item) {
            $myxls->write(0, $colnum, $item, $formatbc);
            $colnum++;
        }
        $rownum=1;
    } else if ($fileformat=='CSV') {
        $filename .= ".txt";

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        $headers = implode("\t", $tableheaders);

        echo $headers." \n";
    }

    $lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id));
    $participants = $DB->get_records_sql($sql, $params);
    if ($participants) {
        foreach ($participants as $participant) {
            $userid = $participant->userid;
            $attempt = false;
            $sql = "SELECT *
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
            } else if ($fileformat=='CSV') {
                $text = implode("\t", $row);
                echo $text."\n";
            }
        }
    }

    if ($fileformat == 'Excel' or $fileformat == 'ODS') {
        $workbook->close();
    }
    exit;
}
