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
 * Base class for offlinequiz report plugins.
 *
 * Doesn't do anything on it's own -- it needs to be extended.
 * This class displays offlinequiz reports.  Because it is called from
 * within /mod/offlinequiz/report.php you can assume that the page header
 * and footer are taken care of.
 *
 * This file can refer to itself as report.php to pass variables
 * to itself - all these will also be globally available.  You must
 * pass "id=$cm->id" or q=$offlinequiz->id", and "mode=reportname".
 *
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

abstract class offlinequiz_default_report {
    const NO_GROUPS_ALLOWED = -2;

    /**
     * Override this function to displays the report.
     * @param $cm the course-module for this offlinequiz.
     * @param $course the coures we are in.
     * @param $offlinequiz this offlinequiz.
     */
    public abstract function display($cm, $course, $offlinequiz);

    /**
     * Initialise some parts of $PAGE and start output.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param object $offlinequiz the offlinequiz settings.
     * @param string $reportmode the report name.
     */
    public function print_header_and_tabs($cm, $course, $offlinequiz, $reportmode = 'overview', $currenttab = 'reports') {
        global $CFG, $PAGE, $OUTPUT;

        switch ($reportmode) {
            case 'overview':
                $reporttitle = get_string('results', 'offlinequiz');
                break;
            case 'rimport':
                $reporttitle = get_string('resultimport', 'offlinequiz');
                break;
            case 'regrade':
                $reporttitle = get_string('regradingquiz', 'offlinequiz');
                break;
            case 'statistics':
                $reporttitle = get_string('statisticsplural', 'offlinequiz');
                break;
        }

        if ($currenttab == 'statistics') {
            $reporttitle = get_string('statisticsplural', 'offlinequiz');
        }

        // Print the page header.
        $PAGE->set_title(format_string($offlinequiz->name) . ' -- ' . $reporttitle);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        // Print the tabs.
        if ($currenttab == 'statistics') {
            $statmode = $reportmode;
        } else {
            $mode = $reportmode;
        }
        include($CFG->dirroot . '/mod/offlinequiz/tabs.php');
    }

    /**
     * Get the current group for the user looking at the report.
     *
     * @param object $cm the course_module information.
     * @param object $coures the course settings.
     * @param context $context the offlinequiz context.
     * @return int the current group id, if applicable. 0 for all users,
     *      NO_GROUPS_ALLOWED if the user cannot see any group.
     */
    public function get_current_group($cm, $course, $context) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        $currentgroup = groups_get_activity_group($cm, true);

        if ($groupmode == SEPARATEGROUPS && !$currentgroup && !has_capability('moodle/site:accessallgroups', $context)) {
            $currentgroup = self::NO_GROUPS_ALLOWED;
        }

        return $currentgroup;
    }
}
