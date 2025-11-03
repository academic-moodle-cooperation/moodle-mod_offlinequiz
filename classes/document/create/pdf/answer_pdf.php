<?php
// This file is part of Moodle - http://moodle.org/
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

namespace mod_offlinequiz\document\create\pdf;

/**
 * answer_pdf
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer_pdf extends offlinequiz_pdf {
    /**
     * group id for the answer pdf
     * @var int
     */
    public $groupid = 0;
    /**
     * groupobject of the answerpdf
     * @var \stdClass
     */
    public $group;
    /**
     * offlinequiz
     * @var \stdClass
     */
    public $offlinequiz;
    /**
     * stuff to write next into the form
     * @var string
     */
    public $formtype;
    /**
     * width of the column
     * @var int
     */
    public $colwidth;
    /**
     * user id
     * @var int
     */
    public $userid;
    /**
     * (non-PHPdoc)
     * @see TCPDF::Header()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Header() {
        global $CFG;

        $offlinequizconfig = get_config('offlinequiz');
        $font = offlinequiz_get_pdffont();
        $letterstr = 'ABCDEF';

        $logourl = trim($offlinequizconfig->logourl);
        if (!empty($logourl)) {
            $aspectratio = $this->get_logo_aspect_ratio($logourl);
            if ($aspectratio < LOGO_MAX_ASPECT_RATIO) {
                $newlength = 54 * $aspectratio / LOGO_MAX_ASPECT_RATIO;
                $this->IMAGE($logourl, 133, 10.8, $newlength, 0);
            } else {
                $this->Image($logourl, 133, 10.8, 54, 0);
            }
        }
        // Print the top left fixation cross.
        $this->Line(11, 12, 14, 12);
        $this->Line(12.5, 10.5, 12.5, 13.5);
        $this->Line(193, 12, 196, 12);
        $this->Line(194.5, 10.5, 194.5, 13.5);
        $this->SetFont($font, 'B', 14);
        $this->SetXY(15, 15);
        $this->Cell(90, 4, offlinequiz_str_html_pdf(get_string('answerform', 'offlinequiz')), 0, 0, 'C');
        $this->Ln(6);
        $this->SetFont($font, '', 10);
        $this->Cell(90, 6, offlinequiz_str_html_pdf(get_string('forautoanalysis', 'offlinequiz')), 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont($font, '', 8);
        $this->Cell(90, 7, ' ' . offlinequiz_str_html_pdf(get_string('firstname')) . ":", 1, 0, 'L');
        $this->Cell(29, 7, ' ' . offlinequiz_str_html_pdf(get_string('invigilator', 'offlinequiz')), 0, 1, 'C');
        $this->Cell(90, 7, ' ' . offlinequiz_str_html_pdf(get_string('lastname')) . ":", 1, 1, 'L');
        $this->Cell(90, 7, ' ' . offlinequiz_str_html_pdf(get_string('signature', 'offlinequiz')) . ":", 1, 1, 'L');
        $this->Ln(5);
        $this->Cell(20, 7, offlinequiz_str_html_pdf(get_string('group', 'offlinequiz')) . ":", 0, 0, 'L');
        $this->SetXY(34.4, 57.4);

        // Print boxes for groups.
        for ($i = 0; $i <= 5; $i++) {
            $this->Cell(6, 3.5, $letterstr[$i], 0, 0, 'R');
            $this->Cell(0.85, 1, '', 0, 0, 'R');
            $this->Rect($this->GetX(), $this->GetY(), 3.5, 3.5);
            $this->Cell(2.7, 1, '', 0, 0, 'C');
            if (!empty($this->group) && $letterstr[$i] == $this->group) {
                $this->Image("$CFG->dirroot/mod/offlinequiz/pix/kreuz.gif", $this->GetX() - 2.75, $this->Gety() + 0.15, 3.15, 0);
            }
        }

        $this->Ln(10);
        $this->MultiCell(115, 3, offlinequiz_str_html_pdf(get_string('instruction1', 'offlinequiz')), 0, 'L');
        $this->Ln(1);
        $this->SetY(78);
        $this->Cell(42, 8, "", 0, 0, 'C');
        $this->Rect($this->GetX(), $this->GetY(), 3.5, 3.5);
        $this->Cell(3.5, 3.5, "", 0, 1, 'C');
        $this->Ln(1);
        $this->MultiCell(115, 3, offlinequiz_str_html_pdf(get_string('instruction2', 'offlinequiz')), 0, 'L');
        $this->Image("$CFG->dirroot/mod/offlinequiz/pix/kreuz.gif", 57.2, 78.2, 3.15, 0);   // JZ added 0.4 to y value.
        $this->Image("$CFG->dirroot/mod/offlinequiz/pix/ausstreichen.jpg", 56.8, 93, 4.1, 0);  // JZ added 0.4 to y value.
        $this->SetY(93.1);
        $this->Cell(42, 8, "", 0, 0, 'C');
        $this->Cell(3.5, 3.5, '', 1, 1, 'C');
        $this->Ln(1);
        $this->MultiCell(115, 3, offlinequiz_str_html_pdf(get_string('instruction3', 'offlinequiz')), 0, 'L');

        $this->Line(109, 29, 130, 29);                                 // Rectangle for the teachers to sign.
        $this->Line(109, 50, 130, 50);
        $this->Line(109, 29, 109, 50);
        $this->Line(130, 29, 130, 50);

        $this->SetFont($font, 'B', 10);
        $this->SetXY(137, 27);
        $this->Cell(
            $offlinequizconfig->ID_digits * 6.5,
            7,
            offlinequiz_str_html_pdf(get_string('idnumber', 'offlinequiz')),
            0,
            1,
            'C'
        );
        $this->SetXY(137, 34);
        $this->Cell($offlinequizconfig->ID_digits * 6.5, 7, '', 1, 1, 'C');  // Box for ID number.

        for ($i = 1; $i < $offlinequizconfig->ID_digits; $i++) {      // Little lines to separate the digits.
            $this->Line(137 + $i * 6.5, 39, 137 + $i * 6.5, 41);
        }

        $this->SetDrawColor(150);
        $this->Line(137, 47.7, 138 + $offlinequizconfig->ID_digits * 6.5, 47.7);  // Line to sparate 0 from the other.
        $this->SetDrawColor(0);

        // Print boxes for the user ID number.
        $this->SetFont($font, '', 12);
        for ($i = 0; $i < $offlinequizconfig->ID_digits; $i++) {
            $x = 139 + 6.5 * $i;
            for ($j = 0; $j <= 9; $j++) {
                $y = 44 + $j * 6;
                $this->Rect($x, $y, 3.5, 3.5);
            }
        }

        // Print the digits for the user ID number.
        $this->SetFont($font, '', 10);
        for ($y = 0; $y <= 9; $y++) {
            $this->SetXY(134, ($y * 6 + 44));
            $this->Cell(3.5, 3.5, "$y", 0, 1, 'C');
            $this->SetXY(138 + $offlinequizconfig->ID_digits * 6.5, ($y * 6 + 44));
            $this->Cell(3.5, 3.5, "$y", 0, 1, 'C');
        }

        $this->Ln();
    }

    /**
     * get the aspect ratio of the logo
     * @param mixed $logourl
     * @return float|int
     */
    private function get_logo_aspect_ratio($logourl) {
        [$originalwidth, $originalheight] = getimagesize($logourl);
        return $originalwidth / $originalheight;
    }


    /**
     * (non-PHPdoc)
     * @see TCPDF::Footer()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Footer() {
        $letterstr = ' ABCDEF';
        $font = offlinequiz_get_pdffont();

        $this->Line(11, 285, 14, 285);
        $this->Line(12.5, 283.5, 12.5, 286.5);
        $this->Line(193, 285, 196, 285);
        $this->Line(194.5, 283.5, 194.5, 286.5);
        $this->Rect(192, 282.5, 2.5, 2.5, 'F');                // Flip indicator.
        $this->Rect(15, 281, 174, 0.5, 'F');                   // Bold line on bottom.

        // Position at x mm from bottom.
        $this->SetY(-20);
        $this->SetFont($font, '', 8);
        $this->Cell(10, 4, $this->formtype, 1, 0, 'C');

        // ID of the offline quiz.
        $this->Cell(15, 4, substr('0000000' . $this->offlinequiz, -7), 1, 0, 'C');

        // Letter for the group.
        $this->Cell(10, 4, $letterstr[$this->groupid], 1, 0, 'C');

        // ID of the user who created the form.
        $this->Cell(15, 4, substr('0000000' . $this->userid, -7), 1, 0, 'C');

        // Name of the offline-quiz.
        $title = $this->title;
        $width = 100;

        while ($this->GetStringWidth($title) > ($width - 1)) {
            $title = mb_substr($title, 0, mb_strlen($title) - 1);
        }
        $this->Cell($width, 4, $title, 1, 0, 'C');

        $y = $this->GetY();
        $x = $this->GetX();
        // Print bar code for page.
        barcodewriter::print_barcode($this, $this->getGroupPageNo(), $x, $y);

        $this->Rect($x, $y, 0.2, 3.7, 'F');

        // Page number.
        $this->Ln(3);
        $this->SetFont($font, 'I', 8);
        $this->Cell(0, 10, offlinequiz_str_html_pdf(get_string('page') . ' ' . $this->getPageNumGroupAlias() . '/' .
                                                                $this->getPageGroupAlias()), 0, 0, 'C');
    }
    /**
     * Generates the body of PDF answer form for an offlinequiz group using an optional groupletter.
     *
     * @param int $maxanswers the maximum number of answers in all question of the offline group
     * @param \question_usage_by_activity $templateusage the template question  usage for this offline group
     * @param object $offlinequiz The offlinequiz object
     * @param object $group the offline group object
     * @param int $courseid the ID of the Moodle course
     * @param object $context the context of the offline quiz.
     * @param string $groupletter the groupletter to mark. No mark if empty.
     */
    public function add_answer_page($maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $groupletter): void {
        global $CFG, $DB, $OUTPUT, $USER;
        // Static variable for caching the questions. Useful in case of consecutive calls.
        static $questionscache = [];
        $pdf = $this; // Shortcut.

        $font = offlinequiz_get_pdffont($offlinequiz);

        $title = offlinequiz_str_html_pdf($offlinequiz->name);
        if (!empty($offlinequiz->time)) {
            $title = $title . ": " . offlinequiz_str_html_pdf(userdate($offlinequiz->time));
        }
        $pdf->set_title($title);
        $pdf->group = $groupletter;
        $pdf->groupid = $group->groupnumber;
        $pdf->offlinequiz = $offlinequiz->id;
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
            throw new \moodle_exception('Too many answers in one question');
        }
        $pdf->userid = $USER->id;
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        // Start a page group to support generation of page sets. Each pagegroup has its own page numbering.
        $pdf->startPageGroup();
        $pdf->AddPage();

        // Load all the questions and quba slots needed by this script.
        $slots = $templateusage->get_slots();

        // Check cache for questions.
        if (empty($questionscache[$offlinequiz->id][$group->id])) {
            $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
                    FROM {offlinequiz_group_questions} ogq
                    JOIN {question} q ON ogq.questionid = q.id
                    JOIN {question_versions} qv ON qv.questionid = q.id
                    JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                    JOIN {question_categories} c ON qbe.questioncategoryid = c.id
                    WHERE ogq.offlinequizid = :offlinequizid
                    AND ogq.offlinegroupid = :offlinegroupid
                ORDER BY ogq.slot ASC ";
            $params = ['offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id];

            if (!$questions = $DB->get_records_sql($sql, $params)) {
                throw new \moodle_exception('noquestionsfound', 'offlinequiz', null, $groupletter);
            }

            // Load the question type specific information.
            if (!get_question_options($questions)) {
                throw new \moodle_exception('Could not load question options');
            }

            $questionscache[$offlinequiz->id][$group->id] = $questions;
        } else {
            $questions = $questionscache[$offlinequiz->id][$group->id];
        }
        // Counting the total number of multichoice questions in the question usage.
        $totalnumber = offlinequiz_count_multichoice_questions($templateusage);

        $number = 0;
        $col = 1;
        $offsety = 105.5;
        $offsetx = 17.3;
        $page = 1;

        $pdf->SetY($offsety);

        $pdf->SetFont($font, 'B', 10);
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
                $pdf->SetFont($font, '', 8);
                $pdf->SetX(($col - 1) * ($pdf->colwidth) + $offsetx + 5);
                for ($i = 0; $i < $maxanswers; $i++) {
                    $pdf->Cell(3.5, 3.5, number_in_style($i, $question->options->answernumbering), 0, 0, 'C');
                    $pdf->Cell(3, 3.5, '', 0, 0, 'C');
                }
                $pdf->Ln(4.5);
                $pdf->SetFont($font, 'B', 10);
            }

            $pdf->SetX(($col - 1) * ($pdf->colwidth) + $offsetx);

            $pdf->Cell(5, 1, ($number + 1) . ")  ", 0, 0, 'R');

            // Print one empty box for each answer.
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            for ($i = 1; $i <= count($order); $i++) {
                // Move the boxes slightly down to align with question number.
                $pdf->Rect($x, $y + 0.6, 3.5, 3.5, '', ['all' => ['width' => 0.2]]);
                $x += 6.5;
            }

            $pdf->SetX($x);

            $pdf->Ln(6.5);

            // Switch to next column if necessary.
            if (($number + 1) % 24 == 0) {
                $pdf->SetY($offsety);
                $col++;
                // Do a pagebreak if necessary.
                if ($col > $pdf->formtype && ($number + 1) < $totalnumber) {
                    $col = 1;
                    $pdf->AddPage();
                    $page++;
                    $pdf->SetY($offsety);
                }
            }
            $number++;
        }

        $group->numberofpages = $page;
    }
}
