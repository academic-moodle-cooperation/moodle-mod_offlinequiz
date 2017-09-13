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
 * Code for upgrading Moodle 1.9.x offlinequizzes to Moodle 2.2+
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/question/engine/upgrade/logger.php');
require_once($CFG->dirroot . '/question/engine/upgrade/behaviourconverters.php');
require_once($CFG->dirroot . '/question/engine/upgrade/upgradelib.php');


/**
 * This class manages upgrading all the question attempts from the old database
 * structure to the new question engine.
 *
 */

class offlinequiz_ilog_upgrader {
    /** @var offlinequiz_upgrade_question_loader */
    protected $questionloader;
    /** @var question_engine_assumption_logger */
    protected $logger;
    /** @var int used by {@link prevent_timeout()}. */
    protected $dotcounter = 0;
    /** @var progress_bar */
    protected $progressbar = null;
    /** @var boolean */
    protected $doingbackup = false;

    protected $contextid = 0;

    /**
     * Called before starting to upgrade all the attempts at a particular offlinequiz.
     * @param int $done the number of offlinequizzes processed so far.
     * @param int $outof the total number of offlinequizzes to process.
     * @param int $offlinequizid the id of the offlinequiz that is about to be processed.
     */
    protected function print_progress($done, $outof, $offlinequizid) {
        if (is_null($this->progressbar)) {
            $this->progressbar = new progress_bar('oq2ilogupgrade');
            $this->progressbar->create();
        }

        gc_collect_cycles(); // This was really helpful in PHP 5.2. Perhaps remove.
        $a = new stdClass();
        $a->done = $done;
        $a->outof = $outof;
        $a->info = $offlinequizid;
        $this->progressbar->update($done, $outof, get_string('upgradingilogs', 'offlinequiz', $a));
    }

    protected function prevent_timeout() {
        set_time_limit(300);
        if ($this->doingbackup) {
            return;
        }
        echo '.';
        $this->dotcounter += 1;
        if ($this->dotcounter % 100 == 0) {
            echo '<br /> ' . time() . "\n";
        }
    }

    public function convert_all_offlinequiz_attempts() {
        global $DB;

        echo 'starting at ' . time() . "\n";

        $offlinequizzes = $DB->get_records('offlinequiz', array('needsilogupgrade' => 1));

        if (empty($offlinequizzes)) {
            return true;
        }

        $done = 0;
        $outof = count($offlinequizzes);

        foreach ($offlinequizzes as $offlinequiz) {
            $this->print_progress($done, $outof, $offlinequiz->id);
            echo ' '. $offlinequiz->id;
            $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course);
            $context = context_module::instance($cm->id);

            $this->contextid = $context->id;
            $this->update_all_files($offlinequiz);
            $this->update_all_group_template_usages($offlinequiz);
            $this->update_all_results_and_logs($offlinequiz);

            rebuild_course_cache($offlinequiz->course);

            $done += 1;
        }

        $this->print_progress($outof, $outof, 'All done!');
        echo 'finished at ' . time() . "\n";
        return true;
    }

    public function update_all_files($offlinequiz) {
        global $DB, $CFG;

        // First we migrate the image files from the original moodledata directory.
        $dirname = $CFG->dataroot . '/' . $offlinequiz->course . '/moddata/offlinequiz/' . $offlinequiz->id;
        $filenames = get_directory_list($dirname, 'pdfs', false, false);
        $fs = get_file_storage();
        $filerecord = array(
                'contextid' => $this->contextid,      // ID of context.
                'component' => 'mod_offlinequiz', // Usually = table name.
                'filearea'  => 'imagefiles',      // Usually = table name.
                'itemid'    => 0,                 // Usually = ID of row in table.
                'filepath'  => '/'                // Any path beginning and ending in.
        ); // Any filename.

        foreach ($filenames as $filename) {
            $filerecord['filename'] = $filename;
            $pathname = $dirname . '/' . $filename;
            if (!$fs->file_exists($this->contextid, 'mod_offlinequiz', 'imagefiles', 0, '/', $filename)) {
                if ($newfile = $fs->create_file_from_pathname($filerecord, $pathname)) {
                    unlink($pathname);
                }
            }
        }

        // Now we migrate the PDF files.
        $dirname = $CFG->dataroot . '/' . $offlinequiz->course . '/moddata/offlinequiz/' . $offlinequiz->id . '/pdfs';
        $filenames = get_directory_list($dirname, '', false, false);
        $fs = get_file_storage();
        $filerecord = array(
                'contextid' => $this->contextid,
                'component' => 'mod_offlinequiz',
                'filearea'  => 'pdfs',
                'itemid'    => 0,
                'filepath'  => '/'
        ); // Any filename.

        foreach ($filenames as $filename) {
            $filerecord['filename'] = $filename;
            $pathname = $dirname . '/' . $filename;
            if ($newfile = $fs->create_file_from_pathname($filerecord, $pathname)) {
                unlink($pathname);
            }
        }
    }

    public function update_all_group_template_usages($offlinequiz) {
        global $DB, $CFG;

        $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id),
                                   'number', '*', 0, $offlinequiz->numgroups);

        $transaction = $DB->start_delegated_transaction();
        foreach ($groups as $group) {
            if ($attempt = $DB->get_record('offlinequiz_attempts', array('offlinequiz' => $offlinequiz->id,
                    'groupid' => $group->number, 'needsupgradetonewqe' => 0, 'sheet' => 1))) {

                    $DB->set_field('offlinequiz_groups', 'templateusageid', $attempt->uniqueid,
                                   array('offlinequizid' => $offlinequiz->id,
                                         'number' => $attempt->groupid));
            }
        }
        $transaction->allow_commit();
        return true;
    }

    public function update_all_results_and_logs($offlinequiz) {
        global $DB, $CFG;

        $this->prevent_timeout();

        // Now we have to migrate offlinequiz_attempts to offlinequiz_results because
        // we need the new result IDs for the scannedpages.
        // Get all attempts that have already been migrated to the new question engine.
        $attempts = $DB->get_records('offlinequiz_attempts', array('offlinequiz' => $offlinequiz->id,
                                                                   'needsupgradetonewqe' => 0, 'sheet' => 0));
        $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id),
                                   'number', '*', 0, $offlinequiz->numgroups);
        list($maxquestions, $maxanswers, $formtype, $questionsperpage) =
            offlinequiz_get_question_numbers($offlinequiz, $groups);
        $transaction = $DB->start_delegated_transaction();

        foreach ($attempts as $attempt) {
            $group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id,
                                                                 'number' => $attempt->groupid));
            $attemptlog = $DB->get_record('offlinequiz_i_log', array('offlinequiz' => $offlinequiz->id,
                                                                     'attempt' => $attempt->id, 'page' => 0));

            $result = new StdClass();
            $result->offlinequizid = $offlinequiz->id;
            if ($group) {
                $result->offlinegroupid = $group->id;
            }
            $teacherid = $attemptlog->importadmin;
            if (empty($teacherid)) {
                $teacherid = 2;
            }
            $result->userid = $attempt->userid;
            $result->sumgrades = $attempt->sumgrades;
            $result->usageid = $attempt->uniqueid;
            $result->teacherid = $teacherid;
            $result->offlinegroupid = $group->id;
            $result->status = 'complete';
            $result->timestart = $attempt->timestart;
            $result->timefinish = $attempt->timefinish;
            $result->timemodified = $attempt->timemodified;
            if (!$oldresult = $DB->get_record('offlinequiz_results', array('offlinequizid' => $result->offlinequizid,
                                                                           'userid' => $result->userid))) {
                $result->id = $DB->insert_record('offlinequiz_results', $result);
            } else {
                $result->id = $oldresult->id;
                $DB->update_record('offlinequiz_results', $result);
            }

            // Save the resultid, s.t. we can still reconstruct the data later.
            $DB->set_field('offlinequiz_attempts', 'resultid', $result->id, array('id' => $attempt->id));

            if ($quba = question_engine::load_questions_usage_by_activity($result->usageid)) {
                $quba->finish_all_questions();
                $slots = $quba->get_slots();

                // Get all the page logs that have contributed to the attempt.
                if ($group->numberofpages == 1) {
                    $pagelogs = array($attemptlog);
                } else {
                    $sql = "SELECT *
                    FROM {offlinequiz_i_log}
                    WHERE offlinequiz = :offlinequizid
                    AND attempt = :attemptid
                    AND page > 0";
                    $params = array('offlinequizid' => $offlinequiz->id, 'attemptid' => $attempt->id);
                    $pagelogs = $DB->get_records_sql($sql, $params);
                }

                foreach ($pagelogs as $pagelog) {
                    $rawdata = $pagelog->rawdata;

                    $scannedpage = new StdClass();
                    $scannedpage->offlinequizid = $offlinequiz->id;
                    $scannedpage->resultid = $result->id;
                    $scannedpage->filename = $this->get_pic_name($rawdata);
                    $scannedpage->groupnumber = $this->get_group($rawdata);
                    $scannedpage->userkey = $this->get_user_name($rawdata);
                    if ($group->numberofpages == 1) {
                        $scannedpage->pagenumber = 1;
                    } else {
                        $scannedpage->pagenumber = $pagelog->page;
                    }
                    $scannedpage->time = $pagelog->time ? $pagelog->time : time();
                    $scannedpage->status = 'submitted';
                    $scannedpage->error = '';

                    $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);

                    $itemdata = $this->get_item_data($rawdata);
                    $items = explode(',', $itemdata);

                    if (!empty($items)) {
                        // Determine the slice of slots we are interested in.
                        // we start at the top of the page (e.g. 0, 96, etc).
                        $startindex = min(($scannedpage->pagenumber - 1) * $questionsperpage, count($slots));
                        // We end on the bottom of the page or when the questions are gone (e.g., 95, 105).
                        $endindex = min( $scannedpage->pagenumber * $questionsperpage, count($slots) );

                        $questioncounter = 0;
                        for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {
                            $slot = $slots[$slotindex];
                            if (array_key_exists($questioncounter, $items)) {
                                $item = $items[$questioncounter];

                                for ($key = 0; $key < strlen($item); $key++) {
                                    $itemchoice = substr($item, $key, 1);

                                    $choice = new stdClass();
                                    $choice->scannedpageid = $scannedpage->id;
                                    $choice->slotnumber = $slot;
                                    $choice->choicenumber = $key;
                                    if ($itemchoice == '1') {
                                        $choice->value = 1;
                                    } else if ($itemchoice == '0') {
                                        $choice->value = 0;
                                    } else {
                                        $choice->value = -1;
                                    }

                                    $choice->id = $DB->insert_record('offlinequiz_choices', $choice);
                                }
                            }
                            $questioncounter++;
                        }
                    }

                    $rawcorners = explode(',', $pagelog->corners);
                    if (!empty($rawcorners) && count($rawcorners) > 8) {
                        for ($i = 0; $i < count($rawcorners); $i++) {
                            if ($rawcorners[$i] < 0) {
                                $rawcorners[$i] = 0;
                            }
                            if ($rawcorners[$i] > 2000) {
                                $rawcorners[$i] = 2000;
                            }
                        }
                        $corners = array();
                        $corners[0] = new oq_point($rawcorners[1], $rawcorners[2]);
                        $corners[1] = new oq_point($rawcorners[3], $rawcorners[4]);
                        $corners[2] = new oq_point($rawcorners[5], $rawcorners[6]);
                        $corners[3] = new oq_point($rawcorners[7], $rawcorners[8]);

                        offlinequiz_save_page_corners($scannedpage, $corners);
                    }
                }

            }
        }
        $DB->set_field('offlinequiz', 'needsilogupgrade', 0, array('id' => $offlinequiz->id));
        $transaction->allow_commit();

        // We start a new transaction for the remaining i_log entries.
        $otherlogs = $DB->get_records('offlinequiz_i_log', array('offlinequiz' => $offlinequiz->id, 'attempt' => 0));
        $transaction = $DB->start_delegated_transaction();

        foreach ($otherlogs as $pagelog) {
            list($status, $error) = $this->get_status_and_error($pagelog->error);
            $rawdata = $pagelog->rawdata;

            $groupnumber = $this->get_group($rawdata);
            $intgroup = intval($groupnumber);
            if ( $intgroup > 0 && $intgroup <= $offlinequiz->numgroups) {
                $groupnumber = $intgroup;
            } else {
                $groupnumber = 0;
            }

            $scannedpage = new StdClass();
            $scannedpage->offlinequizid = $offlinequiz->id;
            $scannedpage->filename = $this->get_pic_name($rawdata);
            $scannedpage->groupnumber = $groupnumber;
            $scannedpage->userkey = $this->get_user_name($rawdata);
            $scannedpage->pagenumber = $pagelog->page;
            if ($pagelog->time) {
                $scannedpage->time = $pagelog->time;
            } else {
                $scannedpage->time = time();
            }
            $scannedpage->status = $status;
            $scannedpage->error = $error;
            $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);

            // We do not migrate itemdata for the scanned pages with error.
            // We do store the corners though.
            $rawcorners = explode(',', $pagelog->corners);

            if (!empty($rawcorners) && count($rawcorners) > 8) {
                $corners = array();
                $corners[0] = new oq_point($rawcorners[1], $rawcorners[2]);
                $corners[1] = new oq_point($rawcorners[3], $rawcorners[4]);
                $corners[2] = new oq_point($rawcorners[5], $rawcorners[6]);
                $corners[3] = new oq_point($rawcorners[7], $rawcorners[8]);
                offlinequiz_save_page_corners($scannedpage, $corners);
            }
        }
        $transaction->allow_commit();

        return true;
    }

    /**
     * Translates old status values to new status and error values of scanned pages
     *
     * @param unknown_type $olderror
     * @return multitype:string
     */
    public function get_status_and_error($olderror) {
        $status = 'ok';
        $error = '';
        switch($olderror) {
            case OFFLINEQUIZ_IMPORT_LMS:
                $status = 'ok';
                $error = '';
                break;
            case OFFLINEQUIZ_IMPORT_OK:
                $status = 'ok';
                $error = '';
                break;
            case OFFLINEQUIZ_IMPORT_CORRECTED:
                $status = 'ok';
                $error = '';
                break;
            case OFFLINEQUIZ_IMPORT_DOUBLE:
                $status = 'ok';
                $error = '';
                break;
            case OFFLINEQUIZ_IMPORT_ITEM_ERROR:
                $status = 'ok';
                $error = '';
                break;
            case OFFLINEQUIZ_IMPORT_DOUBLE_ERROR:
                $status = 'error';
                $error = 'resultexists';
                break;
            case OFFLINEQUIZ_IMPORT_USER_ERROR:
                $status = 'error';
                $error = 'nonexistinguser';
                break;
            case OFFLINEQUIZ_IMPORT_GROUP_ERROR:
                $status = 'error';
                $error = 'grouperror';
                break;
            case OFFLINEQUIZ_IMPORT_FATAL_ERROR:
                $status = 'error';
                $error = 'notadjusted';
                break;
            case OFFLINEQUIZ_IMPORT_INSECURE_ERROR:
                $status = 'error';
                $error = 'insecuremarkings';
                break;
            case OFFLINEQUIZ_IMPORT_PAGE_ERROR:
                $status = 'error';
                $error = 'pageerror';
                break;
            case OFFLINEQUIZ_IMPORT_SINGLE_ERROR:
                $status = 'submitted';
                $error = 'missingpages';
                break;
            case OFFLINEQUIZ_IMPORT_DOUBLE_PAGE_ERROR:
                $status = 'error';
                $error = 'doublepage';
                break;
            case OFFLINEQUIZ_IMPORT_DIFFERING_PAGE_ERROR:
                $status = 'error';
                $error = 'differentpage';
                break;
            default:
                $status = 'error';
                $error = 'unknown';
                break;
        }
        return array($status, $error);
    }


    /**
     * retrieve the image name from the rawdata
     *
     */
    public function get_pic_name($rawdata) {
        $dataarray = explode(",", $rawdata);
        $last = array_pop($dataarray);
        if (preg_match('/(gif|jpg|jpeg|png|tif|tiff)$/i', $last)) {
            return $last;
        } else {
            return '';
        }
    }

    public function get_user_name($rawdata) {
        $dataarray = explode (",", $rawdata);
        return array_shift($dataarray);
    }

    public function get_group($rawdata) {
        $dataarray = explode (",", $rawdata);
        array_shift($dataarray);
        return array_shift($dataarray);
    }

    public function get_item_data($rawdata) {
        $dataarray = explode (",", $rawdata);
        $pos = count($dataarray) - 1;
        if (preg_match('/(gif|jpg|jpeg|png|tif|tiff)$/i', $dataarray[$pos])) {
            array_pop($dataarray);
        }
        array_shift($dataarray);
        array_shift($dataarray);
        $retwert = implode(",", $dataarray);
        return $retwert;
    }

}

/**
 * This class manages upgrading all the question attempts from the old database
 * structure to the new question engine.
 *
 */
class offlinequiz_attempt_upgrader extends question_engine_attempt_upgrader {
    /** @var offlinequiz_upgrade_question_loader */
    protected $questionloader;
    /** @var question_engine_assumption_logger */
    protected $logger;
    /** @var int used by {@link prevent_timeout()}. */
    protected $dotcounter = 0;
    /** @var progress_bar */
    protected $progressbar = null;
    /** @var boolean */
    protected $doingbackup = false;

    /**
     * Called before starting to upgrade all the attempts at a particular offlinequiz.
     * @param int $done the number of offlinequizzes processed so far.
     * @param int $outof the total number of offlinequizzes to process.
     * @param int $offlinequizid the id of the offlinequiz that is about to be processed.
     */
    protected function print_progress($done, $outof, $offlinequizid) {
        if (is_null($this->progressbar)) {
            $this->progressbar = new progress_bar('oq2upgrade');
            $this->progressbar->create();
        }

        gc_collect_cycles(); // This was really helpful in PHP 5.2. Perhaps remove.
        $a = new stdClass();
        $a->done = $done;
        $a->outof = $outof;
        $a->info = $offlinequizid;
        $this->progressbar->update($done, $outof, get_string('upgradingofflinequizattempts', 'offlinequiz', $a));
    }

    protected function get_quiz_ids() {
        global $CFG, $DB;

        // Look to see if the admin has set things up to only upgrade certain attempts.
        $partialupgradefile = $CFG->dirroot . '/' . $CFG->admin .
        '/tool/qeupgradehelper/partialupgrade.php';
        $partialupgradefunction = 'tool_qeupgradehelper_get_quizzes_to_upgrade';
        if (is_readable($partialupgradefile)) {
            include_once($partialupgradefile);
            if (function_exists($partialupgradefunction)) {
                $quizids = $partialupgradefunction();

                // Ignore any quiz ids that do not acually exist.
                if (empty($quizids)) {
                    return array();
                }
                list($test, $params) = $DB->get_in_or_equal($quizids);
                return $DB->get_fieldset_sql("
                        SELECT id
                        FROM {offlinequiz}
                        WHERE id $test
                        ORDER BY id", $params);
            }
        }

        // Otherwise, upgrade all attempts.
        return $DB->get_fieldset_sql('SELECT id FROM {offlinequiz} ORDER BY id');
    }

    public function convert_all_quiz_attempts() {
        global $DB;

        echo 'starting at ' . time() . "\n";
        $quizids = $this->get_quiz_ids();
        if (empty($quizids)) {
            return true;
        }

        $done = 0;
        $outof = count($quizids);
        $this->logger = new question_engine_assumption_logger();

        foreach ($quizids as $quizid) {
            $this->print_progress($done, $outof, $quizid);

            $quiz = $DB->get_record('offlinequiz', array('id' => $quizid), '*', MUST_EXIST);
            $this->update_all_attempts_at_quiz($quiz);
            rebuild_course_cache($quiz->course);

            $done += 1;
        }

        $this->print_progress($outof, $outof, 'All done!');
        $this->logger = null;
        echo 'finshed at ' . time() . "\n";
    }

    public function get_attempts_extra_where() {
        return ' AND needsupgradetonewqe = 1';
    }

    public function update_all_attempts_at_quiz($quiz) {
        global $DB;

        // Wipe question loader cache.
        $this->questionloader = new offlinequiz_upgrade_question_loader($this->logger);

        $params = array('offlinequizid' => $quiz->id);

        // Actually we want all the attempts, also the ones with sheet = 1 for the group template usages.
        $where = 'offlinequiz = :offlinequizid ' . $this->get_attempts_extra_where();

        $quizattemptsrs = $DB->get_recordset_select('offlinequiz_attempts', $where, $params, 'uniqueid');

        $questionsessionsrs = $DB->get_recordset_sql("
                SELECT s.*
                FROM {question_sessions} s
                JOIN {offlinequiz_attempts} a ON (s.attemptid = a.uniqueid)
                WHERE $where
                ORDER BY attemptid, questionid
                ", $params);

        $questionsstatesrs = $DB->get_recordset_sql("
                SELECT s.*
                FROM {question_states} s
                JOIN {offlinequiz_attempts} a ON (s.attempt = a.uniqueid)
                WHERE $where
                ORDER BY s.attempt, question, seq_number, s.id
                ", $params);

        $datatodo = $quizattemptsrs && $questionsessionsrs && $questionsstatesrs;

        while ($datatodo && $quizattemptsrs->valid()) {
            $attempt = $quizattemptsrs->current();
            $quizattemptsrs->next();

            $transaction = $DB->start_delegated_transaction();
            $this->convert_quiz_attempt($quiz, $attempt, $questionsessionsrs, $questionsstatesrs);
            $transaction->allow_commit();
        }

        $quizattemptsrs->close();
        $questionsessionsrs->close();
        $questionsstatesrs->close();

    }

    protected function convert_quiz_attempt($quiz, $attempt, moodle_recordset $questionsessionsrs,
            moodle_recordset $questionsstatesrs) {
        global $OUTPUT, $DB;

        $qas = array();
        $this->logger->set_current_attempt_id($attempt->id);
        while ($qsession = $this->get_next_question_session($attempt, $questionsessionsrs)) {
            $question = $this->load_question($qsession->questionid, $quiz->id);

            $qstates = $this->get_question_states($attempt, $question, $questionsstatesrs);
            try {
                $qas[$qsession->questionid] = $this->convert_question_attempt(
                        $quiz, $attempt, $question, $qsession, $qstates);
            } catch (Exception $e) {
                echo $OUTPUT->notification($e->getMessage());
            }
        }
        $this->logger->set_current_attempt_id(null);
        $questionorder = array();

        // For offlinequizzes we have to take the questionlist from the offline group or the attempt.
        $layout = $attempt->layout;
        $groupquestions = explode(',', $layout);

        foreach ($groupquestions as $questionid) {
            if ($questionid == 0) {
                continue;
            }
            if (!array_key_exists($questionid, $qas)) {
                $this->logger->log_assumption("Supplying minimal open state for
                        question {$questionid} in attempt {$attempt->id} at quiz
                        {$attempt->offlinequiz}, since the session was missing.", $attempt->id);
                try {
                    $question = $this->load_question($questionid, $quiz->id);
                    $qas[$questionid] = $this->supply_missing_question_attempt(
                            $quiz, $attempt, $question);
                } catch (Exception $e) {
                    echo $OUTPUT->notification($e->getMessage());
                }
            }
        }
        return $this->save_usage('deferredfeedback', $attempt, $qas, $layout);
    }

    public function save_usage($preferredbehaviour, $attempt, $qas, $quizlayout) {
        global $DB, $OUTPUT;
        $missing = array();

        $layout = explode(',', $attempt->layout);
        $questionkeys = array_combine(array_values($layout), array_keys($layout));

        $this->set_quba_preferred_behaviour($attempt->uniqueid, $preferredbehaviour);

        $i = 0;

        foreach (explode(',', $quizlayout) as $questionid) {
            if ($questionid == 0) {
                continue;
            }
            $i++;

            if (!array_key_exists($questionid, $qas)) {
                $missing[] = $questionid;
                $layout[$questionkeys[$questionid]] = $questionid;
                continue;
            }

            $qa = $qas[$questionid];
            $qa->questionusageid = $attempt->uniqueid;
            $qa->slot = $i;
            if (textlib::strlen($qa->questionsummary) > question_bank::MAX_SUMMARY_LENGTH) {
                // It seems some people write very long quesions! MDL-30760.
                $qa->questionsummary = textlib::substr($qa->questionsummary,
                        0, question_bank::MAX_SUMMARY_LENGTH - 3) . '...';
            }
            $this->insert_record('question_attempts', $qa);
            $layout[$questionkeys[$questionid]] = $qa->slot;

            foreach ($qa->steps as $step) {
                $step->questionattemptid = $qa->id;
                $this->insert_record('question_attempt_steps', $step);

                foreach ($step->data as $name => $value) {
                    $datum = new stdClass();
                    $datum->attemptstepid = $step->id;
                    $datum->name = $name;
                    $datum->value = $value;
                    $this->insert_record('question_attempt_step_data', $datum, false);
                }
            }
        }

        $this->set_quiz_attempt_layout($attempt->uniqueid, implode(',', $layout));

        if ($missing) {
            $OUTPUT->notification("Question sessions for questions " .
                    implode(', ', $missing) .
                    " were missing when upgrading question usage {$attempt->uniqueid}.");
        }
    }


    protected function set_quiz_attempt_layout($qubaid, $layout) {
        global $DB;
        $DB->set_field('offlinequiz_attempts', 'needsupgradetonewqe', 0, array('uniqueid' => $qubaid));
    }

    protected function delete_quiz_attempt($qubaid) {
        global $DB;
        $DB->delete_records('offlinequiz_attempts', array('uniqueid' => $qubaid));
        $DB->delete_records('question_attempts', array('id' => $qubaid));
    }


    protected function get_converter_class_name($question, $quiz, $qsessionid) {
        global $DB;
        if ($question->qtype == 'deleted') {
            $where = '(question = :questionid OR ' . $DB->sql_like('answer', ':randomid') . ') AND event = 7';
            $params = array('questionid' => $question->id, 'randomid' => "random{$question->id}-%");
            if ($DB->record_exists_select('question_states', $where, $params)) {
                $this->logger->log_assumption("Assuming that deleted question {$question->id} was manually graded.");
                return 'qbehaviour_manualgraded_converter';
            }
        } else if ($question->qtype == 'description') {
            return 'qbehaviour_informationitem_converter';
        } else {
            return 'qbehaviour_deferredfeedback_converter';
        }
    }

    public function supply_missing_question_attempt($quiz, $attempt, $question) {
        if ($question->qtype == 'random') {
            throw new coding_exception("Cannot supply a missing qsession for question
            {$question->id} in attempt {$attempt->id}.");
        }

        $converterclass = $this->get_converter_class_name($question, $quiz, 'missing');

        $qbehaviourupdater = new $converterclass($quiz, $attempt, $question,
                null, null, $this->logger, $this);
        $qa = $qbehaviourupdater->supply_missing_qa();
        $qbehaviourupdater->discard();
        return $qa;
    }

    protected function prevent_timeout() {
        set_time_limit(300);
        if ($this->doingbackup) {
            return;
        }
        echo '.';
        $this->dotcounter += 1;
        if ($this->dotcounter % 100 == 0) {
            echo '<br />' . "\n";
        }
    }

    public function convert_question_attempt($quiz, $attempt, $question, $qsession, $qstates) {
        $this->prevent_timeout();
        $quiz->attemptonlast = false;
        $converterclass = $this->get_converter_class_name($question, $quiz, $qsession->id);

        $qbehaviourupdater = new $converterclass($quiz, $attempt, $question, $qsession,
                $qstates, $this->logger, $this);
        $qa = $qbehaviourupdater->get_converted_qa();
        $qbehaviourupdater->discard();
        return $qa;
    }

    protected function decode_random_attempt($qstates, $maxmark) {
        $realquestionid = null;
        foreach ($qstates as $i => $state) {
            if (strpos($state->answer, '-') < 6) {
                // Broken state, skip it.
                $this->logger->log_assumption("Had to skip brokes state {$state->id}
                for question {$state->question}.");
                unset($qstates[$i]);
                continue;
            }
            list($randombit, $realanswer) = explode('-', $state->answer, 2);
            $newquestionid = substr($randombit, 6);
            if ($realquestionid && $realquestionid != $newquestionid) {
                throw new coding_exception("Question session {$this->qsession->id}
                for random question points to two different real questions
                {$realquestionid} and {$newquestionid}.");
            }
            $qstates[$i]->answer = $realanswer;
        }

        if (empty($newquestionid)) {
            // This attempt only had broken states. Set a fake $newquestionid to
            // prevent a null DB error later.
            $newquestionid = 0;
        }

        $newquestion = $this->load_question($newquestionid);
        $newquestion->maxmark = $maxmark;
        return array($newquestion, $qstates);
    }

    public function prepare_to_restore() {
        $this->doingbackup = true; // Prevent printing of dots to stop timeout on upgrade.
        $this->logger = new dummy_question_engine_assumption_logger();
        $this->questionloader = new offlinequiz_upgrade_question_loader($this->logger);
    }
}


/**
 * This class deals with loading (and caching) question definitions during the
 * offlinequiz upgrade.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_upgrade_question_loader extends question_engine_upgrade_question_loader {

    protected function load_question($questionid, $offlinequizid) {
        global $DB;

        if ($offlinequizid) {
            $question = $DB->get_record_sql("
                    SELECT q.*, qqi.grade AS maxmark
                    FROM {question} q
                    JOIN {offlinequiz_q_instances} qqi ON qqi.question = q.id
                    WHERE q.id = $questionid AND qqi.offlinequiz = $offlinequizid");
        } else {
            $question = $DB->get_record('question', array('id' => $questionid));
        }

        if (!$question) {
            return null;
        }

        if (empty($question->defaultmark)) {
            if (!empty($question->defaultgrade)) {
                $question->defaultmark = $question->defaultgrade;
            } else {
                $question->defaultmark = 0;
            }
            unset($question->defaultgrade);
        }

        $qtype = question_bank::get_qtype($question->qtype, false);
        if ($qtype->name() === 'missingtype') {
            $this->logger->log_assumption("Dealing with question id {$question->id}
            that is of an unknown type {$question->qtype}.");
            $question->questiontext = '<p>' . get_string('warningmissingtype', 'offlinequiz') .
            '</p>' . $question->questiontext;
        }

        $qtype->get_question_options($question);

        return $question;
    }

}

/**
 * Removes all 'double' entries in the offlinequiz question instances
 * table. In Moodle 1.9 each group could have their own question
 * instances.  Now we store only one entry per question.
 *
 */
function offlinequiz_remove_redundant_q_instances() {
    global $DB;

    $offlinequizzes = $DB->get_records('offlinequiz', array(), 'id', 'id');
    foreach ($offlinequizzes as $offlinequiz) {
        $transaction = $DB->start_delegated_transaction();

        $qinstances = $DB->get_records('offlinequiz_q_instances', array('offlinequiz' => $offlinequiz->id), 'groupid');
        // First delete them all.
        $DB->delete_records('offlinequiz_q_instances', array('offlinequiz' => $offlinequiz->id));

        // Now insert one per question.
        foreach ($qinstances as $qinstance) {
            if (!$DB->get_record('offlinequiz_q_instances', array('offlinequiz' => $qinstance->offlinequiz,
                                                                  'question' => $qinstance->question))) {
                $qinstance->groupid = 0;
                $DB->insert_record('offlinequiz_q_instances', $qinstance);
            }
        }
        $transaction->allow_commit();
    }
}

/**
 * Updates the new field names (questionfilename, answerfilename, correctionfilename) in the table
 * offlinequiz_groups for old offline quizzes.
 */
function offlinequiz_update_form_file_names() {
    global $DB;

    $offlinequizzes = $DB->get_records('offlinequiz');

    if (empty($offlinequizzes)) {
        return;
    }

    $progressbar = new progress_bar('filenameupdate');
    $progressbar->create();
    $done = 0;
    $outof = count($offlinequizzes);
    $fs = get_file_storage();
    $letterstr = 'abcdefghijkl';

    $a = new stdClass();
    $a->done = $done;
    $a->outof = $outof;
    $progressbar->update($done, $outof, get_string('upgradingfilenames', 'offlinequiz', $a));

    foreach ($offlinequizzes as $offlinequiz) {

        $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course);
        $context = context_module::instance($cm->id);

        $files = $fs->get_area_files($context->id, 'mod_offlinequiz', 'pdfs');
        $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0,
            $offlinequiz->numgroups);
        // Simply load all files in the 'pdfs' filearea in a ZIP file.

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number - 1];

            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($filename != '.') {
                    if (0 === strpos($filename, 'form-' . strtolower($groupletter))) {
                        $group->questionfilename = $filename;
                    } else if (0 === strpos($filename, 'answer-' . strtolower($groupletter))) {
                        $group->answerfilename = $filename;
                    } else if (0 === strpos($filename, 'correction-' . strtolower($groupletter))) {
                        $group->correctionfilename = $filename;
                    }
                }
            }
            $DB->update_record('offlinequiz_groups', $group);
        }
        $done += 1;
	    $a->done = $done;
        $a->info = $offlinequiz->id;
	    $progressbar->update($done, $outof, get_string('upgradingfilenames', 'offlinequiz', $a));
   }
}

function offlinequiz_update_refresh_all_pagecounts() {
    global $DB;
    $groups = $DB->get_records('offlinequiz_groups');
    if(empty($groups)) {
        return;
    }
    $progressbar = new progress_bar('pagenumberupdate');
    $progressbar->create();
    $done = 0;
    $outof = count($groups);

    $a = new stdClass();
    $a->done = $done;
    $a->outof = $outof;
    $progressbar->update($done, $outof, get_string('pagenumberupdate', 'offlinequiz', $a));
    foreach ($groups as $group) {
        $params = array('id' => $group->id);
        $questions = $DB->get_field_sql("SELECT count(*) FROM {question} q, {offlinequiz_group_questions} gq where gq.offlinegroupid = :id AND gq.questionid = q.id AND qtype <> 'description' ", $params);
        $maxanswers = $DB->get_field_sql("SELECT max(count) from (SELECT count(*) as count from {question_answers} qa, {offlinequiz_groups} g, {offlinequiz_group_questions} gq where gq.offlinegroupid = g.id AND gq.questionid = qa.question AND g.id = :id group by gq.id) as count", $params);
        $columns = offlinequiz_get_number_of_columns($maxanswers);
        $pages = offlinequiz_get_number_of_pages($questions,$columns);
        if($pages > 1 && $pages != $group->numberofpages) {
            $group->numberofpages = $pages;
            $DB->update_record('offlinequiz_groups', $group);
        }
        $done++;
        $progressbar->update($done, $outof, get_string('pagenumberupdate', 'offlinequiz', $a));
    }
}

function offlinequiz_get_number_of_columns($maxanswers)  {
    $i=1;
    $columnlimits = array(1 => 13, 2 => 8, 3 => 6);
    while(array_key_exists($i,$columnlimits) && $columnlimits[$i] > $maxanswers) {
        $i++;
    }
    return $i;
}

function offlinequiz_get_number_of_pages($questions,$columns) {
    return ceil($questions/$columns/24);
}
