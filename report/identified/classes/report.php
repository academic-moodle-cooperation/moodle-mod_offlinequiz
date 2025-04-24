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
 * Offlinequiz identified forms generator version info
 *
 * @package       mod_offlinequiz
 * @subpackage    report_identified
 * @author        Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @copyright     2023
 * @since         Moodle 4.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/
namespace offlinequiz_identified;
use mod_offlinequiz\default_report;
use \navigation_node;
use \moodle_url;
defined('MOODLE_INTERNAL') || die();
require_once("report/identified/locallib.php");
/**
 * Offlinequiz identified forms generator.
 */
class report extends default_report
{

    public function display($offlinequiz, $cm, $course)
    {
        global $CFG, $OUTPUT, $PAGE, $DB;
        $context = \context_module::instance($cm->id);
        $toform = ['id' => $cm->id, 'offlinequiz' => $offlinequiz, 'listid' => null, 'groupid' => null];
        $mform = new identifiedformselector(null, $toform, 'get');
        // Disable if forms are not generated.
        if ($offlinequiz->docscreated == 1) {

            $resultmsg = "";
            // Form processing and displaying is done here.
            if ($fromform = $mform->get_data()) {
                $listid = isset($fromform->list)? $fromform->list : -1;
                $groupid = $fromform->groupnumber+1;
                $nogroupmark = isset($fromform->nogroupmark);
                $onlyifaccess = isset($fromform->onlyifaccess) ? $fromform->onlyifaccess : false;
                $list = $DB->get_record('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id, 'id' => $listid));
                if ($list) {
                    raise_memory_limit(MEMORY_EXTRA);
                    if (offlinequiz_create_pdf_participants_answers($offlinequiz, $course->id, $groupid, $list, $context, $nogroupmark, $onlyifaccess)) {
                        // PDF created and downloaded.
                        die();
                    } else {
                        $resultmsg = get_string('noparticipantsinlist', 'offlinequiz_identified');
                    };
                } else {
                    $resultmsg = get_string('noparticipantsinlist', 'offlinequiz_identified');
                };
            }
        }

        // Set anydefault data (if any).
        $mform->set_data($toform);
       
        // Display Tabs.
        $this->print_header_and_tabs($cm, $course, $offlinequiz, 'identified');
        // Display the header.
        echo $OUTPUT->heading(get_string('identified', 'offlinequiz_identified'), 2);

        if ($offlinequiz->docscreated == 0) {
            // url createquiz.
            $url = new moodle_url('/mod/offlinequiz/createquiz.php', ['q' => $offlinequiz->id, 'tabs' => 'tabpreview']);
            echo $OUTPUT->notification(get_string('notgenerated', 'offlinequiz_identified', $url->out()), 'notifyproblem');
            return true;
        } else {
            // Display the result message.
            if ($resultmsg) {
                echo $OUTPUT->notification($resultmsg, 'notifyproblem');
            }
            // Display the description.
            // Url: participants.php?q=1&mode=editlists.
            $url = new moodle_url('/mod/offlinequiz/participants.php', ['q' => $offlinequiz->id, 'mode' => 'editlists', 'tabs'=>'tabattendances']);
            echo $OUTPUT->box(get_string('identifiedreport', 'offlinequiz_identified', $url->out()), 'generalbox', 'intro');
            // Display the form.
            $mform->display();
        }
        return true;
    }
   
    public function add_to_navigation(navigation_node $navigation, $cm, $offlinequiz): navigation_node
    {
        // Add navigation nodes to mod_tabofflinequizcontent and mod_tabattendances.
        $url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'identified', 'id' => $cm->id]);
        $navnode= navigation_node::create(text: get_string('identified', 'offlinequiz_identified'),
                                         action: $url,
                                         key: $this->get_navigation_key());

        // Get tabofflinequizcontent.
        $parentnode = $navigation->get('mod_offlinequiz_edit');
        $parentnode->add_node($navnode);
               
        return $navigation;
    }
    public function get_report_title(): string {
        return get_string('pluginname', 'offlinequiz_identified');
    }
    public function get_navigation_key(): string {
        return 'tab_identifiedforms';
    }
}
