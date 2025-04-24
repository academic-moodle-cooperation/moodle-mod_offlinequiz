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
 * The mod_offlinequiz identifiedformsselector
 *
 * @package   offlinequiz_identified
 * @author    Juan Pablo de Castro <juanpablo.decastro@uva.es>
 * @copyright 2023
 * @since     Moodle 4.1
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace offlinequiz_identified;

defined('MOODLE_INTERNAL') || die();
/**
 * PDF forms generator for offlinequizzes with participant identification.
 * Call set_participant($participant) to set the participant data and then add_answer_page(...) to add the answer page.
 */
class answer_pdf_identified extends \offlinequiz_answer_pdf {
    public $participant = null;
    public $listno = null;

    public function Header(){
        global $CFG;
        // Participant data.
        $participant = $this->participant;
        parent::Header();
        $offlinequizconfig = get_config('offlinequiz');
        $pdf = $this;
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
    public function set_participant($participant) {
        $this->participant = $participant;
    }
    /**
     * Add a new answer page to the PDF with identification of the participant.
     * @param mixed $participant
     * @param int-mask-of $maxanswers
     * @param mixed $templateusage
     * @param mixed $offlinequiz
     * @param mixed $group
     * @param int $courseid
     * @param mixed $context
     * @param string $groupletter
     * @return void
     */
    public function add_participant_answer_page( $participant, $maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $groupletter) {
        $this->set_participant($participant);
        $this->add_answer_page( $maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $groupletter);
    }

}