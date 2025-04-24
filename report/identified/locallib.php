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
 * Creates the PDF forms for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @copyright     2023 Universidad de Valladolid {@link http://www.uva.es}
 * @since         Moodle 4.1+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');
/**
 * Generator of answer forms with participant identification.
 */
class offlinequiz_answer_pdf_identified extends offlinequiz_answer_pdf {
    public $participant = null;
    public $listno = null;

    public function Header(){
        global $CFG, $DB;
        // participant data.
        parent::Header();
        $offlinequizconfig = get_config('offlinequiz');
        $pdf = $this;
        $participant = $this->participant;
        // Marks identity.
        if ($participant != null) {
            $idnumber = $participant->{$offlinequizconfig->ID_field};
            // Pad with zeros.
            $idnumber = str_pad($idnumber, $offlinequizconfig->ID_digits, '0', STR_PAD_LEFT);
            $pdf->SetFont('FreeSans', '', 8);
            $pdf->setXY(34.4,  29);
            $pdf->Cell(90, 7, ' '.offlinequiz_str_html_pdf($participant->firstname), 0, 0, 'L');
            $pdf->setXY(34.4,  36);
            $pdf->Cell(90, 7, ' '.offlinequiz_str_html_pdf($participant->lastname), 0, 1, 'L');
            // Print Check test.
        
            $pdf->SetFont('FreeSans', '', 12);
            $pdf->SetXY(137, 34);

            for ($i = 0; $i < $offlinequizconfig->ID_digits; $i++) {      // Userid digits.
                $pdf->SetXY(137 + $i*6.5, 34);
                $this->Cell(7, 7, $idnumber[$i], 0, 0, 'C');
            }

            $pdf->SetDrawColor(0);

            // Print boxes for the user ID number.
            for ($i = 0; $i < $offlinequizconfig->ID_digits; $i++) {
                $x = 139 + 6.5 * $i;
                for ($j = 0; $j <= 9; $j++) {
                    $y = 44 + $j * 6;
                    $pdf->SetXY($x, $y);
                    $pdf->Cell(2.7,  1, '', 0, 0, 'C');
                    if ($idnumber[$i] == $j) {
                        $pdf->Image("$CFG->dirroot/mod/offlinequiz/pix/kreuz.gif", $x ,  $y + 0.15,  3.15,  0);
                    }
                }
            }
        }
    }
}

function offlinequizidentified_get_participants($offlinequiz, $list, $onlyifaccess = false) {
    global $CFG, $DB;
    $coursemodule = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
    $courseid = $coursemodule->course;
    $coursecontext = \context_course::instance($courseid); // Course context.
    $systemcontext = \context_system::instance();

    $offlinequizconfig = get_config('offlinequiz');
    $listname = $list->name;

    // First get roleids for students.
    if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
        throw new \moodle_exception('noroles', 'offlinequiz_identified');
    }

    $roleids = array();
    foreach ($roles as $role) {
        $roleids[] = $role->id;
    }

    list($csql, $cparams) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
    list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
    $params = array_merge($cparams, $rparams);
    // Get all users that are in the list. TODO: Use explicit JOINS.
    $sql = "SELECT DISTINCT u.id, u." . $offlinequizconfig->ID_field . ", u.firstname, u.lastname
        FROM {user} u,
            {offlinequiz_participants} p,
            {role_assignments} ra,
            {offlinequiz_p_lists} pl
    WHERE ra.userid = u.id
        AND p.listid = :listid
        AND p.listid = pl.id
        AND pl.offlinequizid = :offlinequizid
        AND p.userid = u.id
        AND ra.roleid $rsql AND ra.contextid $csql
    ORDER BY u.lastname, u.firstname";

    $params['offlinequizid'] = $offlinequiz->id;
    $params['listid'] = $list->id;               
    // $sql = "SELECT p.userid
    //         FROM {offlinequiz_participants} p, {offlinequiz_p_lists} pl
    //         WHERE p.listid = pl.id
    //         AND pl.offlinequizid = :offlinequizid
    //         AND p.listid = :listid";
    // $params = array('offlinequizid' => $offlinequiz->id, 'listid' => $list->id);

    $users = $DB->get_records_sql($sql, $params);
    if ($onlyifaccess) {
        $filtereduserids = [];
        // Check what users can access this module using condition api.
        // Get cm from instance id.
        $coursemodule = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
        $cmid = $coursemodule->id;
        foreach ($users as $user) {
            // Get cm_info.
            $modinfo = get_fast_modinfo($coursemodule->course, $user->id);
            $cm = $modinfo->get_cm($cmid);

            if ( $cm->available) {
                $filtereduserids[$user->id] = $user;
            }                
        }
        return $filtereduserids;   
    } else {
        return $users;
    }
}
/**
 * Creates a PDF document for a list of participants
 *
 * @param unknown_type $offlinequiz
 * @param int $courseid
 * @param unknown_type $list
 * @param unknown_type $context
 * @return boolean
 */
function offlinequiz_create_pdf_participants_answers($offlinequiz, $courseid, $groupnumber, $list, $context, $nogroupmark=false, $onlyifaccess = false) {
    global $CFG, $DB;
    // Get the participants filtering by access if requested.
    $participants = offlinequizidentified_get_participants($offlinequiz, $list, $onlyifaccess);
    if (empty($participants)) {
        return false;
    }

    $pdf = new offlinequiz_identified\answer_pdf_identified('P', 'mm', 'A4');
    
    $pdf->listno = $list->listnumber;
    $title = offlinequiz_str_html_pdf($offlinequiz->name);
    // Add the list name to the title.
    $title .= ', '.offlinequiz_str_html_pdf($list->name);
    $pdf->set_title($title);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetAutoPageBreak(true, 20);

    if ($nogroupmark==true){
        $groupletter = '';
    } else {
        $letterstr = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $groupletter = $letterstr[$groupnumber];
    }
    // Answer pages.
    $group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'groupnumber' => $groupnumber));
  
    $pdf->SetFont('FreeSans', '', 10);
    $maxanswers= offlinequiz_get_maxanswers($offlinequiz, array($group));
    if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
        throw new \moodle_exception(
            "missinggroup" ,
            "offlinequiz_identified",
            "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey(),
            $groupletter
        );
    }
 
    foreach ($participants as $participant) {
        $pdf->add_participant_answer_page( $participant, $maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $groupletter);
    }

    $pdf->Output("{$offlinequiz->name}_{$list->name}.pdf", 'D');
    return true;
}

