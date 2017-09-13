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
 * Defines the report class for participant lists
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/errorpages_table.php');

class participants_report {
    /**
     * Print table with import errors.
     */
    public function error_report($offlinequiz, $courseid) {
        global $CFG, $USER, $DB, $OUTPUT;

        $nologs = optional_param('nologs', 0, PARAM_INT);
        $pagesize = optional_param('pagesize', 10, PARAM_INT);

        $sql = "SELECT *
                  FROM {offlinequiz_scanned_p_pages}
                 WHERE status = 'error'
                   AND offlinequizid = :offlinequizid";

        $countsql = "SELECT COUNT(*)
                       FROM {offlinequiz_scanned_p_pages}
                      WHERE status = 'error'
                        AND offlinequizid = :offlinequizid";

        $params = array('offlinequizid' => $offlinequiz->id);
        $cparams = array('offlinequizid' => $offlinequiz->id);

        $tableparams = array('q' => $offlinequiz->id, 'mode' => 'upload', 'pagesize' => $pagesize, 'action' => 'delete',
                'strreallydel' => addslashes(get_string('deletepagecheck', 'offlinequiz')));
        $table = new offlinequiz_selectall_table('mod-offlinequiz-participants-error', 'participants.php', $tableparams);

        // Add extra limits due to initials bar.
        list($ttest, $tparams) = $table->get_sql_where();

        if (!empty($ttest)) {
            $sql .= ' AND ' . $ttest;
            $params = array_merge($params, $tparams);
        }

        if (!empty($countsql)) {
            $totalinitials = $DB->count_records_sql($countsql, $params);
            if (!empty($ttest)) {
                $countsql .= ' AND ' . $ttest;
                $cparams = array_merge($cparams, $tparams);
            }
            $total  = $DB->count_records_sql($countsql, $cparams);
        }

        // Define table columns.
        $tablecolumns = array('checkbox', 'time' , 'error', 'scan');
        $tableheaders = array('', get_string('importedon', 'offlinequiz_rimport'), get_string('error'), '');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $baseurl = new moodle_url('/mod/offlinequiz/participants.php', array('q' => $offlinequiz->id, 'mode' => 'upload'));
        $table->define_baseurl($baseurl);

        $table->sortable(true);
        $table->no_sorting('checkbox');
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'logs');
        $table->set_attribute('class', 'generaltable generalbox');

        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();
        if ($sort = $table->get_sql_sort()) {
            $sql .= ' ORDER BY ' . $sort;
        }

        $table->initialbars($totalinitials > 20);
        $strtimeformat = get_string('strftimedatetime');

        if (!$pages = $DB->get_records_sql($sql, $params)) {
            return;
        }

        // Options for the popup_action.
        $options = array();
        $options['height'] = 1200; // Optional.
        $options['width'] = 1170; // Optional.
        $options['resizable'] = false;

        $counter = 1;
        foreach ($pages as $page) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants_correct.php?pageid=' . $page->id);
            $title = get_string('correcterror', 'offlinequiz_rimport');

            $actionlink = $OUTPUT->action_link($url, $title, new popup_action('click', $url, 'correct' . $page->id, $options));

            $errorstr = '';
            if (!empty($page->error)) {
                $errorstr = get_string('error' . $page->error, 'offlinequiz_rimport');
            }
            $row = array(
                    '<input type="checkbox" name="pageid[]" value="' . $page->id . '"  class="select-multiple-checkbox" />',
                    userdate($page->time, $strtimeformat),
                    $errorstr,
                    $actionlink
                    );
            $table->add_data($row);
            $counter++;
        }

        echo $OUTPUT->heading(get_string('errorreport', 'offlinequiz'), 3, '');

        // Print table.
        $table->print_html();
    }
}
