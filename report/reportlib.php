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
 * Helper functions for offlinequiz reports
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');
require_once($CFG->libdir . '/filelib.php');

define('OFFLINEQUIZ_REPORT_DEFAULT_PAGE_SIZE', 30);
define('OFFLINEQUIZ_REPORT_DEFAULT_GRADING_PAGE_SIZE', 10);

define('OFFLINEQUIZ_REPORT_ATTEMPTS_ALL', 0);
define('OFFLINEQUIZ_REPORT_ATTEMPTS_STUDENTS_WITH_NO', 1);
define('OFFLINEQUIZ_REPORT_ATTEMPTS_STUDENTS_WITH', 2);
define('OFFLINEQUIZ_REPORT_ATTEMPTS_ALL_STUDENTS', 3);

/**
 * Returns an array of reports to which the current user has access to.
 * @return array reports are ordered as they should be for display in tabs.
 */
function offlinequiz_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)) {
        return $reportlist;
    }

    $reports = $DB->get_records('offlinequiz_reports', null, 'displayorder DESC', 'name, capability');
    $reportdirs = get_plugin_list('offlinequiz');

    // Order the reports tab in descending order of displayorder.
    $reportcaps = array();
    foreach ($reports as $key => $report) {
        if (array_key_exists($report->name, $reportdirs)) {
            $reportcaps[$report->name] = $report->capability;
        }
    }

    // Add any other reports, which are on disc but not in the DB, on the end.
    foreach ($reportdirs as $reportname => $notused) {
        if (!isset($reportcaps[$reportname])) {
            $reportcaps[$reportname] = null;
        }
    }
    $reportlist = array();
    foreach ($reportcaps as $name => $capability) {
        if (empty($capability)) {
            $capability = 'mod/offlinequiz:viewreports';
        }
        if (has_capability($capability, $context)) {
            $reportlist[] = $name;
        }
    }

    return $reportlist;
}

function offlinequiz_report_unindex($datum) {
    if (!$datum) {
        return $datum;
    }
    $datumunkeyed = array();
    foreach ($datum as $value) {
        if (is_array($value)) {
            $datumunkeyed = array_merge($datumunkeyed, offlinequiz_report_unindex($value));
        } else {
            $datumunkeyed[] = $value;
        }
    }
    return $datumunkeyed;
}

/**
 * Get the slots of real questions (not descriptions) in this offlinequiz, in order.
 * @param object $offlinequiz the offlinequiz.
 * @return array of slot => $question object with fields
 *      ->slot, ->id, ->maxmark, ->number, ->length.
 */
function offlinequiz_report_get_significant_questions($offlinequiz) {
    global $DB;

    $questionids = $offlinequiz->questions;
    if (empty($questionids)) {
        return array();
    }

    list($usql, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
    
    $params['offlinequizid'] = $offlinequiz->id;
    $groupsql = '';
    if ($offlinequiz->groupid) {
        $groupsql = ' AND oqg.offlinegroupid = :offlinegroupid ';
        $params['offlinegroupid'] = $offlinequiz->groupid;
    }

    $rawquestions = $DB->get_records_sql("SELECT oqg.id as oqgid, q.id as questionid, q.length, oqg.maxmark
                                            FROM {question} q
                                            JOIN {offlinequiz_group_questions} oqg ON oqg.questionid = q.id
                                           WHERE q.id $usql
                                             AND q.qtype <> 'description'
                                             AND oqg.offlinequizid = :offlinequizid
                                                 $groupsql
                                             AND q.length > 0", $params);
    // Make sure we have unique questionids. Not sure if DISTINCT in query captures all contingencies.
    $questions = array();
    foreach ($rawquestions as $rawquestion) {
        if (!array_key_exists($rawquestion->questionid, $questions)) {
            $question = new stdClass();
            $question->id = $rawquestion->questionid;
            $question->length = $rawquestion->length;
            $question->maxmark = $rawquestion->maxmark;
            $questions[$question->id] = $question;
        }
    }
    
    $number = 1;
    foreach ($questionids as $key => $id) {
        if (!array_key_exists($id, $questions)) {
            continue;
        }
        $questions[$id]->number = $number;
        $number += $questions[$id]->length;
    }

    return $questions;
}

/**
 * Format a number as a percentage out of $offlinequiz->sumgrades
 * 
 * @param number $rawgrade the mark to format.
 * @param object $offlinequiz the offlinequiz settings
 * @param bool $round whether to round the results ot $offlinequiz->decimalpoints.
 */
function offlinequiz_report_scale_summarks_as_percentage($rawmark, $offlinequiz, $round = true) {
    if ($offlinequiz->sumgrades <= 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = 100 * $rawmark / $offlinequiz->sumgrades;
    if ($round) {
        $mark = offlinequiz_format_grade($offlinequiz, $mark);
    }
    return $mark . '%';
}

/**
 * Format a number as a percentage out of $offlinequiz->sumgrades
 * 
 * @param number $rawgrade the mark to format.
 * @param object $offlinequiz the offlinequiz settings
 * @param bool $round whether to round the results ot $offlinequiz->decimalpoints.
 */
function offlinequiz_report_scale_grade($rawmark, $offlinequiz, $round = true) {
    if ($offlinequiz->sumgrades <= 0) {
        return '';
    }
    if (!is_numeric($rawmark)) {
        return $rawmark;
    }

    $mark = $rawmark / $offlinequiz->sumgrades * $offlinequiz->grade;
    if ($round) {
        $mark = offlinequiz_format_grade($offlinequiz, $mark);
    }
    return $mark;
}


/**
 * Create a filename for use when downloading data from a offlinequiz report. It is
 * expected that this will be passed to flexible_table::is_downloading, which
 * cleans the filename of bad characters and adds the file extension.
 * @param string $report the type of report.
 * @param string $courseshortname the course shortname.
 * @param string $offlinequizname the offlinequiz name.
 * @return string the filename.
 */
function offlinequiz_report_download_filename($report, $courseshortname, $offlinequizname) {
    return $courseshortname . '-' . format_string($offlinequizname, true) . '-' . $report;
}

/**
 * Get the default report for the current user.
 * @param object $context the offlinequiz context.
 */
function offlinequiz_report_default_report($context) {
    $reports = offlinequiz_report_list($context);
    return reset($reports);
}

/**
 * Generate a message saying that this offlinequiz has no questions, with a button to
 * go to the edit page, if the user has the right capability.
 * @param object $offlinequiz the offlinequiz settings.
 * @param object $cm the course_module object.
 * @param object $context the offlinequiz context.
 * @return string HTML to output.
 */
function offlinequiz_no_questions_message($offlinequiz, $cm, $context) {
    global $OUTPUT;

    $output = '';
    $output .= $OUTPUT->notification(get_string('noquestions', 'offlinequiz'));
    if (has_capability('mod/offlinequiz:manage', $context)) {
        $output .= $OUTPUT->single_button(new moodle_url('/mod/offlinequiz/edit.php',
        array('cmid' => $cm->id)), get_string('editofflinequiz', 'offlinequiz'), 'get');
    }

    return $output;
}
