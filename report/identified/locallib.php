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
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->libdir . '/formslib.php');
// require_once($CFG->dirroot . '/filter/tex/filter.php');
// require_once($CFG->dirroot . '/mod/offlinequiz/html2text.php');
// require_once($CFG->dirroot . '/mod/offlinequiz/documentlib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');

class offlinequiz_answer_pdf_identified extends offlinequiz_answer_pdf {
    public $participant = null;

    public function Header(){
        global $CFG, $DB;
        // participant data.
        parent::Header();
        $offlinequizconfig = get_config('offlinequiz');
        $letterstr = 'ABCDEF';
        $pdf = $this;
        $participant = $this->participant;
        // Marks identity.
        if ($participant != null) {
            $idnumber = $participant->{$offlinequizconfig->ID_field};
            // pad with zeros.
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
    /**
     * Overrides the footer to display PageGroup numbers instead of document-wide page numbers.
     * @see TCPDF::Footer()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Footer() {
        $letterstr = ' ABCDEF';

        $this->Line(11, 285, 14, 285);
        $this->Line(12.5, 283.5, 12.5, 286.5);
        $this->Line(193, 285, 196, 285);
        $this->Line(194.5, 283.5, 194.5, 286.5);
        $this->Rect(192, 282.5, 2.5, 2.5, 'F');                // Flip indicator.
        $this->Rect(15, 281, 174, 0.5, 'F');                   // Bold line on bottom.

        // Position at x mm from bottom.
        $this->SetY(-20);
        $this->SetFont('FreeSans', '', 8);
        $this->Cell(10, 4, $this->formtype, 1, 0, 'C');

        // ID of the offline quiz.
        $this->Cell(15, 4, substr('0000000'.$this->offlinequiz, -7), 1, 0, 'C');

        // Letter for the group.
        $this->Cell(10, 4, $letterstr[$this->groupid], 1, 0, 'C');

        // ID of the user who created the form.
        $this->Cell(15, 4, substr('0000000'.$this->userid, -7), 1, 0, 'C');

        // Name of the offline-quiz.
        $title = $this->title;
        $width = 100;

        while ($this->GetStringWidth($title) > ($width - 1)) {
            $title = mb_substr($title,  0,  mb_strlen($title) - 1);
        }
        $this->Cell($width, 4, $title, 1, 0, 'C');

        $y = $this->GetY();
        $x = $this->GetX();
        // Print bar code for page.
        offlinequiz_barcodewriter::print_barcode($this, $this->PageNo(), $x, $y);

        $this->Rect($x, $y, 0.2, 3.7, 'F');

        // Page number.
        $this->Ln(3);
        $this->SetFont('FreeSans', 'I', 8);
        $this->Cell(0, 10, offlinequiz_str_html_pdf(get_string('page') . ' ' . $this->getPageNumGroupAlias() . '/' .
                $this->getPageGroupAlias()), 0, 0, 'C');
    }

}

/*
 * Generates the PDF answer form for an offlinequiz group.
 * 
* @param offlinequiz_answer_pdf $pdf the PDF object
* @param int $maxanswers the maximum number of answers in all question of the offline group
* @param question_usage_by_activity $templateusage the template question  usage for this offline group
* @param object $offlinequiz The offlinequiz object
* @param object $group the offline group object
* @param int $courseid the ID of the Moodle course
* @param object $context the context of the offline quiz.
* @param object $participant the participant for this page.
* @return the modified PDF object.
*/
function offlinequiz_create_pdf_answer_body($pdf, $maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $participant) {
    global $CFG, $DB, $OUTPUT, $USER;
    // Static variable for caching the questions.
    static $questions_cache = array();
    // Static variable for caching the question slots.
    static $slots_cache = array();

    $letterstr = ' abcdefghijklmnopqrstuvwxyz';
    $groupletter = strtoupper($letterstr[$group->groupnumber]);

    $fm = new stdClass();
    $fm->q = 0;
    $fm->a = 0;

    // $texfilter = new filter_tex($context, array());

   
    $title = offlinequiz_str_html_pdf($offlinequiz->name);
    if (!empty($offlinequiz->time)) {
        $title = $title . ": " . offlinequiz_str_html_pdf(userdate($offlinequiz->time));
    }
    $pdf->set_title($title);
    $pdf->group = $groupletter;
    $pdf->groupid = $group->groupnumber;
    $pdf->offlinequiz = $offlinequiz->id;
    $pdf->participant = $participant;
    $pdf->formtype = 4;
    $pdf->colwidth = 7 * 6.5;
    if ($maxanswers > 5) {
        $pdf->formtype = 3;
        $pdf->colwidth = 9 * 6.5;
    }
    if ($maxanswers > 7) {
        $pdf->formtype = 2;
        $pdf->colwidth = 14 * 6.5;
    }
    if ($maxanswers > 12) {
        $pdf->formtype = 1;
        $pdf->colwidth = 26 * 6.5;
    }
    if ($maxanswers > 26) {
        print_error('Too many answers in one question');
    }
    $pdf->userid = $USER->id;
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->startPageGroup();
    $pdf->AddPage();

    // Load all the questions and quba slots needed by this script.
    $slots = $templateusage->get_slots();

    // Check cache for questions.
    if (empty($questions_cache[$offlinequiz->id][$group->id])) {
        $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
                  FROM {offlinequiz_group_questions} ogq
                  JOIN {question} q ON ogq.questionid = q.id
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                  JOIN {question_categories} c ON qbe.questioncategoryid = c.id
                 WHERE ogq.offlinequizid = :offlinequizid
                   AND ogq.offlinegroupid = :offlinegroupid
              ORDER BY ogq.slot ASC ";
        $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id);

        $questions = $DB->get_records_sql($sql, $params);
        if (!$questions) {
            echo $OUTPUT->box_start();
            echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
            echo $OUTPUT->box_end();
            return;
        }

        // Load the question type specific information.
        if (!get_question_options($questions)) {
            print_error('Could not load question options');
        }

        $questions_cache[$offlinequiz->id][$group->id] = $questions;
    } else {
        $questions = $questions_cache[$offlinequiz->id][$group->id];
    }
    // $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
    //           FROM {offlinequiz_group_questions} ogq
    //           JOIN {question} q ON ogq.questionid = q.id
    //           JOIN {question_versions} qv ON qv.questionid = q.id
    //           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
    //           JOIN {question_categories} c ON qbe.questioncategoryid = c.id
    //          WHERE ogq.offlinequizid = :offlinequizid
    //            AND ogq.offlinegroupid = :offlinegroupid
    //       ORDER BY ogq.slot ASC ";
    // $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id);

    // if (!$questions = $DB->get_records_sql($sql, $params)) {
    //     echo $OUTPUT->box_start();
    //     echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
    //     echo $OUTPUT->box_end();
    //     return;
    // }

    // // Load the question type specific information.
    // if (!get_question_options($questions)) {
    //     print_error('Could not load question options');
    // }

    // Counting the total number of multichoice questions in the question usage.
    $totalnumber = offlinequiz_count_multichoice_questions($templateusage);

    $number = 0;
    $col = 1;
    $offsety = 105.5;
    $offsetx = 17.3;
    $page = 1;

    $pdf->SetY($offsety);

    $pdf->SetFont('FreeSans', 'B', 10);
    foreach ($slots as $key => $slot) {
        set_time_limit(120);
        $slotquestion = $templateusage->get_question($slot);
        $currentquestionid = $slotquestion->id;
        $attempt = $templateusage->get_question_attempt($slot);
        $order = $slotquestion->get_order($attempt);  // Order of the answers.

        // Get the question data.
        $question = $questions[$currentquestionid];

        // Only look at multichoice questions.
        if ($question->qtype != 'multichoice' && $question->qtype != 'multichoiceset') {
            continue;
        }

        // Print the answer letters every 8 questions.
        if ($number % 8 == 0) {
            $pdf->SetFont('FreeSans', '', 8);
            $pdf->SetX(($col - 1) * ($pdf->colwidth) + $offsetx + 5);
            for ($i = 0; $i < $maxanswers; $i++) {
                $pdf->Cell(3.5, 3.5, number_in_style($i, $question->options->answernumbering), 0, 0, 'C');
                $pdf->Cell(3, 3.5, '', 0, 0, 'C');
            }
            $pdf->Ln(4.5);
            $pdf->SetFont('FreeSans', 'B', 10);
        }

        $pdf->SetX(($col - 1) * ($pdf->colwidth) + $offsetx);

        $pdf->Cell(5, 1, ($number + 1).")  ", 0, 0, 'R');

        // Print one empty box for each answer.
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        for ($i = 1; $i <= count($order); $i++) {
            // Move the boxes slightly down to align with question number.
            $pdf->Rect($x, $y + 0.6, 3.5, 3.5, '', array('all' => array('width' => 0.2)));
            $x += 6.5;
        }

        $pdf->SetX($x);

        $pdf->Ln(6.5);

        // Switch to next column if necessary.
        if (($number + 1) % 24 == 0) {
            $pdf->SetY($offsety);
            $col++;
            // Do a pagebreak if necessary.
            if ($col > $pdf->formtype and ($number + 1) < $totalnumber) {
                $col = 1;
                $pdf->AddPage();
                $page++;
                $pdf->SetY($offsety);
            }
        }
        $number ++;
    }

    $group->numberofpages = $page;
}

/**
 * Creates a PDF document for a list of participants
 *
 * @param unknown_type $offlinequiz
 * @param unknown_type $courseid
 * @param unknown_type $list
 * @param unknown_type $context
 * @return boolean
 */
function offlinequiz_create_pdf_participants_answers($offlinequiz, $courseid, $groupnumber, $list, $context) {
    global $CFG, $DB;

    $coursecontext = context_course::instance($courseid); // Course context.
    $systemcontext = context_system::instance();

    $offlinequizconfig = get_config('offlinequiz');
    $listname = $list->name;

    // First get roleids for students.
    if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
        print_error("No roles with capability 'mod/offlinequiz:attempt' defined in system context");
    }

    $roleids = array();
    foreach ($roles as $role) {
        $roleids[] = $role->id;
    }

    list($csql, $cparams) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
    list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
    $params = array_merge($cparams, $rparams);

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

    $participants = $DB->get_records_sql($sql, $params);

    if (empty($participants)) {
        return false;
    }

    // $pdf = new offlinequiz_participants_pdf('P', 'mm', 'A4');
    $pdf = new offlinequiz_answer_pdf_identified('P', 'mm', 'A4');
    
    $pdf->listno = $list->listnumber;
    $title = offlinequiz_str_html_pdf($offlinequiz->name);
    // Add the list name to the title.
    $title .= ', '.offlinequiz_str_html_pdf($listname);
    $pdf->set_title($title);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetAutoPageBreak(true, 20);

    $position = 1;
    $letterstr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // Answers page.
    $group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'groupnumber' => $groupnumber));
    $pdf->group = $letterstr[$groupnumber - 1];

    $pdf->SetFont('FreeSans', '', 10);
    $maxanswers= offlinequiz_get_maxanswers($offlinequiz, array($group));
    if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
        print_error(
            "Missing data for group " . $groupletter,
            "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey()
        );
    }
 
    foreach ($participants as $participant) {
        offlinequiz_create_pdf_answer_body($pdf, $maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $participant);
    }

    $pdf->Output("{$offlinequiz->name}_{$listname}.pdf", 'D');
    return true;
}


class identifiedformselector extends \moodleform {
      
    public function definition() {
        global $CFG, $DB;
        $offlinequiz = $this->_customdata['offlinequiz'];
        $cmid = $this->_customdata['id'];
        $sql = "SELECT id, name, listnumber, filename
        FROM {offlinequiz_p_lists}
        WHERE offlinequizid = :offlinequizid
        ORDER BY name ASC";
        $lists = $DB->get_records_sql($sql, array('offlinequizid' => $offlinequiz->id));
        $groups = $DB->get_records(
            'offlinequiz_groups',
            array('offlinequizid' => $offlinequiz->id),
            'groupnumber',
            'groupnumber',
            0,
            $offlinequiz->numgroups
        );
        // map groups to letters.
        $groups = array_map(function($group) {
            $letterstr = "ABCDEFGH"; 
            return $letterstr[$group->groupnumber-1];
        }, $groups);
        // map lists to list->name.
        $lists = array_map(function($list) use ($offlinequiz) {
            global $DB;
            
            $sql = "SELECT COUNT(*)
                      FROM {offlinequiz_participants} p, {offlinequiz_p_lists} pl
                     WHERE p.listid = pl.id
                       AND pl.offlinequizid = :offlinequizid
                       AND p.listid = :listid";
            $params = array('offlinequizid' => $offlinequiz->id, 'listid' => $list->id);

            $numusers = $DB->count_records_sql($sql, $params);
            
            $listname = $list->name . ' (' . $numusers . ')';
            return $listname;
        }, $lists);
            $mform = $this->_form;
            $mform->addElement('hidden', 'id', $cmid);
            $mform->setType('id', PARAM_INT);
            $mform->addElement('hidden', 'mode', 'identified');
            $mform->setType('mode', PARAM_TEXT);
            $mform->addElement('header', 'general', get_string('pluginname', 'offlinequiz_identified'));
            $mform->addElement('select', 'groupnumber', get_string('group', 'offlinequiz'), $groups);
            $mform->setType('groupnumber', PARAM_INT);
            $mform->addElement('select', 'list', get_string('participants', 'offlinequiz'), $lists);
            $mform->setType('list', PARAM_INT);
            $mform->addElement('submit', 'submitbutton', get_string('submit'));
        }
    }