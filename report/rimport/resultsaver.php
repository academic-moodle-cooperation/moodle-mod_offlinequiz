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
 * The results import report for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind
 * @copyright     2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.4
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace offlinequiz_result_import;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
define('RESULT_STATUS_ERROR', 'error');
define('RESULT_STATUS_RESULT_ALREADY_EXISTS_FOR_OTHER_GRUOP', 'resultfordifferentgroups');
define('RESULT_STATUS_RESULT_ALREADY_EXISTS_WITH_SAME_CROSSES', 'sameresultexists');
define('RESULT_STATUS_RESULT_ALREADY_EXISTS_WITH_OTHER_CROSSES', 'otherresultexists');

class offlinequiz_resultsaver {


    public function create_or_update_result_in_db($scannedpageid, $teacherid) {

        global $DB;
        $sql = "SELECT p2.* FROM {offlinequiz_scanned_pages} p1, {offlinequiz_scanned_pages} p2
                WHERE p1.id = :scannedpageid
                AND   p1.offlinequizid = p2.offlinequizid
                AND   p1.userkey = p2.userkey
                AND   (p1.status = 'ok' OR p1.status = 'submitted') ";
        $scannedpages = $DB->get_records_sql($sql, array('scannedpageid' => $scannedpageid));
        // TODO check, if page with same page and userky, but other scannedpageid exists.

        if (!$scannedpages || !$scannedpages[$scannedpageid]) {
            throw new \coding_exception('A scannedpage can not be updated if it has errors');
        }
        $resulterror = $this->get_result_exists_errors($scannedpageid);
        if ($resulterror) {
            self::save_page_status($scannedpageid, 'error', $resulterror);
        }
        if ($this->result_with_same_) {
            $groupnumber = 0;
        }
        foreach ($scannedpages as $scannedpage) {
            if (!$groupnumber) {
                $groupnumber = $scannedpage->groupnumber;
            } else if ($groupnumber != $scannedpage->groupnumber) {
                self::save_page_status($scannedpageid, RESULT_STATUS_ERROR, RESULT_STATUS_RESULT_ALREADY_EXISTS_FOR_OTHER_GRUOP);
            }

        }
        // TODO what happens with differend group versions?
        $scannedpage = $scannedpages[$scannedpageid];

        $conditions = array('groupnumber' => $scannedpage->groupnumber, 'offlinequizid' => $scannedpage->offlinequizid);
        $group = $DB->get_record('offlinequiz_groups', $conditions);

        $scannedpageids = array_keys($scannedpages);
        list($scannedpagesql, $params) = $DB->get_in_or_equal($scannedpageids, SQL_PARAMS_QM, 'pages');
        $sql = "SELECT * FROM {offlinequiz_choices} choice
                WHERE  scannedpageid" . $scannedpagesql;

        $resultid = self::get_result_id($scannedpages);
        if ($resultid) {
            $result = $DB->get_record('offlinequiz_results', ['id' => $resultid]);
            $quba = question_engine::load_questions_usage_by_activity($result->usageid);
        } else {
            $result = new \stdClass();
            $result->offlinequizid = $scannedpage->offlinequizid;
            $result->userid = self::get_userid_by_userkey($scannedpage->userkey);
            $quba = $this->clone_template_usage($scannedpage->offlinequizid, $group->id);
            $result->usageid = $quba->id;
            $result->status = 'partial';
            $result->teacherid = $teacherid;
            $result->timestart = time();
            $result->timemodified = $result->timestart;
            $result->id = $DB->insert_record('offlinequiz_results', $result);
            $scannedpage->resultid = $result->id;
            $DB->set_field('offlinequiz_scanned_pages', 'resultid', $result->id, ['id' => $scannedpageid]);
        }
        foreach ($scannedpages as $scannedpage) {
            $this->submit_scanned_page_to_result($quba, $scannedpage);
        }
        question_engine::save_questions_usage_by_activity($quba);

    }

    private function get_result_exists_errors($scannedpageid) {
        global $DB;
        $sql = "SELECT page2.*
                    FROM {offlinequiz_scanned_pages} page1,
                         {offlinequiz_scanned_pages} page2
                    WHERE page1.id = :scannedpageid
                    AND   page1.pagenumber = page2.pagenumber
                    AND   page1.userkey = page2.userkey
                    AND   page2.resultid IS NOT NULL
                    AND   page1.id <> page2.id
                    AND   page1.offlinequizid = page2.offlinequizid";
        $otherresults = $DB->get_records_sql($sql, ['scannedpageid' => $scannedpageid]);
        if (!$otherresults) {
            return;
        }
        if ($this->results_have_same_crosses($scannedpageid, $otherresults[0]->id)) {
            return RESULT_STATUS_RESULT_ALREADY_EXISTS_WITH_SAME_CROSSES;
        }
    }

    /**
     *
     */private function clone_template_usage($offlinequizid, $groupid) {
        // There is no result. First we have to clone the template question usage of the offline group.
        // We have to use our own loading function in order to get the right class.
        $templateusage = offlinequiz_load_questions_usage_by_activity($group->templateusageid);

        // Get the question instances for initial maxmarks.
        $sql = "SELECT questionid, maxmark
                      FROM {offlinequiz_group_questions}
                     WHERE offlinequizid = :offlinequizid
                       AND offlinegroupid = :offlinegroupid";

        $qinstances = $DB->get_records_sql($sql,
                array('offlinequizid' => $offlinequizid,
                        'offlinegroupid' => $groupid));

        // Clone it...
        $quba = $templateusage->get_clone($qinstances);

        // And save it. The clone contains the same question in the same order and the same order of the answers.
        question_engine::save_questions_usage_by_activity($quba);
}

private function results_have_same_crosses ($scannedpageid1, $scannedpageid2) {

    global $DB;
    $sql = "SELECT 1
                 FROM   {offlinequiz_choices} c1,
                        {offlinequiz_choices} c2
                 WHERE  c1.scannedpageid = :scannedpageid1
                        AND c2.scannedpageid = :scannedpageid2
                        AND c1.slotnumber = c2.slotnumber
                        AND c1.choicenumber = c2.choicenumber
                        AND c1.value <> c2.value";
    return $DB->count_records_sql($sql, ['scannedpageid1' => $scannedpageid1, 'scannedpageid1' => $scannedpageid2]);
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
private function submit_scanned_page_to_result($quba, $scannedpage) {
    global $DB;

    $offlinequizconfig = get_config('offlinequiz');

    $result = $DB->get_record('offlinequiz_results', array('id' => $scannedpage->resultid));
    $quba = question_engine::load_questions_usage_by_activity($result->usageid);
    $slots = $quba->get_slots();
    $choicesdata = $this->get_choices_data($scannedpage);

    for ($slotindex = $startindex; $slotindex < $endindex; $slotindex++) {

        $slot = $slots[$slotindex];
        $slotquestion = $quba->get_question($slot);
        $attempt = $quba->get_question_attempt($slot);
        $order = $slotquestion->get_order($attempt);  // Order of the answers.

        $count = 0;
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
    } // End of for slotindex...

    $scannedpage->status = 'submitted';
    $scannedpage->error = 'missingpages';
    $scannedpage->time = time();

    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);

    return $scannedpage;
}



private static function get_result_id($scannedpages) {
    foreach ($scannedpages as $currentpage) {
        if (!empty($currentpage->resultid)) {
            return $currentpage->resultid;
        }
    }
    return 0;
}

private static function get_userid_by_userkey($userkey) {
    global $DB;
    $offlinequizconfig = get_config('offlinequiz');
    // TODO prefix and suffix.
    return $DB->get_field('user', 'id', [$offlinequizconfig->ID_field => $userkey]);
}

private static function save_page_status($scannedpageid, $status, $error) {
    global $DB;
    $sql = "UPDATE {offlinequiz_scanned_pages} SET status = :status, error = :error
                WHERE id=:scannedpageid";
    $params = ['scannedpageid' => $scannedpageid, 'status' => $status, 'error' => $error];
    $DB->execute($sql, $params);

}



}