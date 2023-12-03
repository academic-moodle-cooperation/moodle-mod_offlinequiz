<?php
use offlinequiz_identified\identifiedform;

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
 * Offlinequiz identified forms generator version info
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @copyright     2023
 * @since         Moodle 4.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

require_once("report/identified/locallib.php");
class offlinequiz_identified_report extends offlinequiz_default_report
{

    public function display($offlinequiz, $cm, $course)
    {
        global $CFG, $OUTPUT, $DB;
        $context = context_module::instance($cm->id);
        $toform = array('id' => $cm->id, 'offlinequiz' => $offlinequiz, 'listid' => null, 'groupid' => null);
        $mform = new identifiedformselector(null, $toform, 'get');
        // Disable if forms are not generated.
        if ($offlinequiz->docscreated == 1) {

            $resultmsg = "";
            // Form processing and displaying is done here.
            if ($fromform = $mform->get_data()) {
                $listid = $fromform->list;
                $groupid = $fromform->groupnumber;
                $list = $DB->get_record('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id, 'id' => $listid));
                if ($list) {
                    raise_memory_limit(MEMORY_EXTRA);
                    if (offlinequiz_create_pdf_participants_answers($offlinequiz, $course->id, $groupid, $list, $context)) {
                        // PDF created and downloaded.
                        die();
                    } else {
                        $resultmsg = get_string('noparticipantsinlist', 'offlinequiz_identified');
                    }
                    ;
                }
            }
        }

        // Set anydefault data (if any).
        $mform->set_data($toform);
        // Display Tabs.
        $this->print_header_and_tabs($cm, $course, $offlinequiz, 'identified');
        if ($offlinequiz->docscreated == 0) {
            echo $OUTPUT->notification(get_string('notgenerated', 'offlinequiz_identified'), 'notifyproblem');
            return true;
        } else {
            // Display the result message.
            if ($resultmsg) {
                echo $OUTPUT->notification($resultmsg, 'notifyproblem');
            }
            // Display the description.
            echo $OUTPUT->box(get_string('identifiedreport', 'offlinequiz_identified'), 'generalbox', 'intro');
            // Display the form.
            $mform->display();
        }
        return true;
    }
    public function print_header_and_tabs($cm, $course, $offlinequiz, $reportmode = 'overview')
    {
        global $CFG, $PAGE, $OUTPUT;
        $reporttitle = get_string('pluginname', 'offlinequiz_identified');
        $currenttab = 'tabidentified';

        // Print the page header.
        $PAGE->set_title(format_string($offlinequiz->name) . ' -- ' . $reporttitle);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();
        // Prints information about the offlinequiz identified report.
        offlinequiz_print_tabs($offlinequiz, $currenttab, $cm);
    }
    public function add_to_tabs($tabs, $cm, $offlinequiz)
    {
        $tabs['tabidentified'] = [
            'tab' => 'tabofflinequizcontent',
            'url' => new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'identified', 'id' => $cm->id]),
            'title' => get_string('identified', 'offlinequiz_identified'),
        ];
        $tabs['tabidentified2'] = [
            'tab' => 'tabattendances',
            'url' => new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'identified', 'id' => $cm->id]),
            'title' => get_string('identified', 'offlinequiz_identified'),
        ];
        return $tabs;
    }
}
