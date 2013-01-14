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

