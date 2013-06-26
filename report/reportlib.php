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
 * Helper functions for offlinequiz reports
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
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

    // Order the reports tab in descending order of displayorder
    $reportcaps = array();
    foreach ($reports as $key => $report) {
        if (array_key_exists($report->name, $reportdirs)) {
            $reportcaps[$report->name] = $report->capability;
        }
    }

    // Add any other reports, which are on disc but not in the DB, on the end
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
    //     $reportlist = array();
    //     $capability = 'mod/offlinequiz:viewreports';
    //     if (has_capability($capability, $context)) {
    //     	$reportlist = array('overview', 'rimport', 'regrade');
    //     }
    return $reportlist;
}

// /**
//  * Get the default report for the current user.
//  * @param object $context the offlinequiz context.
//  */
// function offlinequiz_report_default_report($context) {
//     return reset(offlinequiz_report_list($context));
// }


/**
 * Takes an array of objects and constructs a multidimensional array keyed by
 * the keys it finds on the object.
 * @param array $datum an array of objects with properties on the object
 * including the keys passed as the next param.
 * @param array $keys Array of strings with the names of the properties on the
 * objects in datum that you want to index the multidimensional array by.
 * @param bool $keysunique If there is not only one object for each
 * combination of keys you are using you should set $keysunique to true.
 * Otherwise all the object will be added to a zero based array. So the array
 * returned will have count($keys) + 1 indexs.
 * @return array multidimensional array properly indexed.
 */
function offlinequiz_report_index_by_keys($datum, $keys, $keysunique = true) {
    if (!$datum) {
        return array();
    }
    $key = array_shift($keys);
    $datumkeyed = array();
    foreach ($datum as $data) {
        if ($keys || !$keysunique) {
            $datumkeyed[$data->{$key}][]= $data;
        } else {
            $datumkeyed[$data->{$key}]= $data;
        }
    }
    if ($keys) {
        foreach ($datumkeyed as $datakey => $datakeyed) {
            $datumkeyed[$datakey] = offlinequiz_report_index_by_keys($datakeyed, $keys, $keysunique);
        }
    }
    return $datumkeyed;
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

    $questionids = offlinequiz_questions_in_offlinequiz($offlinequiz->questions);
    if (empty($questionids)) {
        return array();
    }

    list($usql, $params) = $DB->get_in_or_equal(explode(',', $questionids));
    $params[] = $offlinequiz->id;
    $questions = $DB->get_records_sql("SELECT q.id, q.length, qqi.grade AS maxmark
                                         FROM {question} q
                                         JOIN {offlinequiz_q_instances} qqi ON qqi.questionid = q.id
                                        WHERE q.id $usql
                                          AND qqi.offlinequizid = ?
                                          AND length > 0", $params);

    $number = 1;
    foreach (explode(',', $questionids) as $key => $id) {
        if (!array_key_exists($id, $questions)) {
            continue;
        }
        $questions[$id]->number = $number;
        $number += $questions[$id]->length;
    }
    
//     $qsbyslot = array();
//     $number = 1;
//     foreach (explode(',', $questionids) as $key => $id) {
//         if (!array_key_exists($id, $questions)) {
//             continue;
//         }

//         $slot = $key + 1;
//         $question = $questions[$id];
//         $question->slot = $slot;
//         $question->number = $number;

//         $qsbyslot[$slot] = $question;

//         $number += $question->length;
//     }

//     return $qsbyslot;
    return $questions;
}

/**
 * @param object $offlinequiz the offlinequiz settings.
 * @return bool whether, for this offlinequiz, it is possible to filter attempts to show
 *      only those that gave the final grade.
 */
function offlinequiz_report_can_filter_only_graded($offlinequiz) {
    return $offlinequiz->attempts != 1 && $offlinequiz->grademethod != OFFLINEQUIZ_GRADEAVERAGE;
}

/**
 * Given the offlinequiz grading method return sub select sql to find the id of the
 * one attempt that will be graded for each user. Or return
 * empty string if all attempts contribute to final grade.
 */
function offlinequiz_report_qm_filter_select($offlinequiz, $offlinequizattemptsalias = 'offlinequiza') {
    if ($offlinequiz->attempts == 1) {
        // This offlinequiz only allows one attempt.
        return '';
    }

    switch ($offlinequiz->grademethod) {
        case OFFLINEQUIZ_GRADEHIGHEST :
            return "$offlinequizattemptsalias.id = (
                    SELECT MIN(qa2.id)
                    FROM {offlinequiz_results} qa2
                    WHERE qa2.offlinequiz = $offlinequizattemptsalias.offlinequiz AND
                        qa2.userid = $offlinequizattemptsalias.userid AND
                        COALESCE(qa2.sumgrades, 0) = (
                            SELECT MAX(COALESCE(qa3.sumgrades, 0))
                            FROM {offlinequiz_attempts} qa3
                            WHERE qa3.offlinequiz = $offlinequizattemptsalias.offlinequiz AND
                                qa3.userid = $offlinequizattemptsalias.userid
                        )
                    )";

        case OFFLINEQUIZ_GRADEAVERAGE :
            return '';

        case OFFLINEQUIZ_ATTEMPTFIRST :
            return "$offlinequizattemptsalias.id = (
                    SELECT MIN(qa2.id)
                    FROM {offlinequiz_attempts} qa2
                    WHERE qa2.offlinequiz = $offlinequizattemptsalias.offlinequiz AND
                        qa2.userid = $offlinequizattemptsalias.userid)";

        case OFFLINEQUIZ_ATTEMPTLAST :
            return "$offlinequizattemptsalias.id = (
                    SELECT MAX(qa2.id)
                    FROM {offlinequiz_attempts} qa2
                    WHERE qa2.offlinequiz = $offlinequizattemptsalias.offlinequiz AND
                        qa2.userid = $offlinequizattemptsalias.userid)";
    }
}

/**
 * Get the nuber of students whose score was in a particular band for this offlinequiz.
 * @param number $bandwidth the width of each band.
 * @param int $bands the number of bands
 * @param int $offlinequizid the offlinequiz id.
 * @param array $userids list of user ids.
 * @return array band number => number of users with scores in that band.
 */
function offlinequiz_report_grade_bands($bandwidth, $bands, $offlinequizid, $userids = array()) {
    global $DB;
    if (!is_int($bands)) {
        debugging('$bands passed to offlinequiz_report_grade_bands must be an integer. (' .
                gettype($bands) . ' passed.)', DEBUG_DEVELOPER);
        $bands = (int) $bands;
    }

    if ($userids) {
        list($usql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $usql = "qg.userid $usql AND";
    } else {
        $usql = '';
        $params = array();
    }
    $sql = "
SELECT band, COUNT(1)

FROM (
    SELECT FLOOR(qg.grade / :bandwidth) AS band
      FROM {offlinequiz_grades} qg
     WHERE $usql qg.offlinequiz = :offlinequizid
) subquery

GROUP BY
    band

ORDER BY
    band";

    $params['offlinequizid'] = $offlinequizid;
    $params['bandwidth'] = $bandwidth;

    $data = $DB->get_records_sql_menu($sql, $params);

    // We need to create array elements with values 0 at indexes where there is no element.
    $data =  $data + array_fill(0, $bands + 1, 0);
    ksort($data);

    // Place the maximum (prefect grade) into the last band i.e. make last
    // band for example 9 <= g <=10 (where 10 is the perfect grade) rather than
    // just 9 <= g <10.
    $data[$bands - 1] += $data[$bands];
    unset($data[$bands]);

    return $data;
}

function offlinequiz_report_highlighting_grading_method($offlinequiz, $qmsubselect, $qmfilter) {
    if ($offlinequiz->attempts == 1) {
        return '<p>' . get_string('onlyoneattemptallowed', 'offlinequiz_overview') . '</p>';

    } else if (!$qmsubselect) {
        return '<p>' . get_string('allattemptscontributetograde', 'offlinequiz_overview') . '</p>';

    } else if ($qmfilter) {
        return '<p>' . get_string('showinggraded', 'offlinequiz_overview') . '</p>';

    } else {
        return '<p>' . get_string('showinggradedandungraded', 'offlinequiz_overview',
                '<span class="gradedattempt">' . offlinequiz_get_grading_option_name($offlinequiz->grademethod) .
                '</span>') . '</p>';
    }
}

/**
 * Get the feedback text for a grade on this offlinequiz. The feedback is
 * processed ready for display.
 *
 * @param float $grade a grade on this offlinequiz.
 * @param int $offlinequizid the id of the offlinequiz object.
 * @return string the comment that corresponds to this grade (empty string if there is not one.
 */
function offlinequiz_report_feedback_for_grade($grade, $offlinequizid, $context) {
    global $DB;

    static $feedbackcache = array();

    if (!isset($feedbackcache[$offlinequizid])) {
        $feedbackcache[$offlinequizid] = $DB->get_records('offlinequiz_feedback', array('offlinequizid' => $offlinequizid));
    }

    // With CBM etc, it is possible to get -ve grades, which would then not match
    // any feedback. Therefore, we replace -ve grades with 0.
    $grade = max($grade, 0);

    $feedbacks = $feedbackcache[$offlinequizid];
    $feedbackid = 0;
    $feedbacktext = '';
    $feedbacktextformat = FORMAT_MOODLE;
    foreach ($feedbacks as $feedback) {
        if ($feedback->mingrade <= $grade && $grade < $feedback->maxgrade) {
            $feedbackid = $feedback->id;
            $feedbacktext = $feedback->feedbacktext;
            $feedbacktextformat = $feedback->feedbacktextformat;
            break;
        }
    }

    // Clean the text, ready for display.
    $formatoptions = new stdClass();
    $formatoptions->noclean = true;
    $feedbacktext = file_rewrite_pluginfile_urls($feedbacktext, 'pluginfile.php',
            $context->id, 'mod_offlinequiz', 'feedback', $feedbackid);
    $feedbacktext = format_text($feedbacktext, $feedbacktextformat, $formatoptions);

    return $feedbacktext;
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

    $mark = $rawmark * 100 / $offlinequiz->sumgrades;
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

/**
 * Should the grades be displayed in this report. That depends on the offlinequiz
 * display options, and whether the offlinequiz is graded.
 * @param object $offlinequiz the offlinequiz settings.
 * @param context $context the offlinequiz context.
 * @return bool
 */
function offlinequiz_report_should_show_grades($offlinequiz, context $context) {
    if ($offlinequiz->timeclose && time() > $offlinequiz->timeclose) {
        $when = mod_offlinequiz_display_options::AFTER_CLOSE;
    } else {
        $when = mod_offlinequiz_display_options::LATER_WHILE_OPEN;
    }
    $reviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz, $when);

    return offlinequiz_has_grades($offlinequiz) &&
            ($reviewoptions->marks >= question_display_options::MARK_AND_MAX ||
            has_capability('moodle/grade:viewhidden', $context));
}
