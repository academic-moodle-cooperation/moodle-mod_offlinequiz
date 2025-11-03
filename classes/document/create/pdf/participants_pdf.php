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
 * participants_pdf
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants_pdf extends offlinequiz_pdf {
    /**
     * the id of the list
     * @var int
     */
    public $listno;

    /**
     * (non-PHPdoc)
     * @see TCPDF::Header()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Header() {
        global $CFG, $DB;
        $font = offlinequiz_get_pdffont();

        $this->Line(11, 12, 14, 12);
        $this->Line(12.5, 10.5, 12.5, 13.5);
        $this->Line(193, 12, 196, 12);
        $this->Line(194.5, 10.5, 194.5, 13.5);

        $this->Line(12.5, 18, 18.5, 12);

        $this->SetFont($font, 'I', 8);

        // Title.
        $x = $this->GetX();
        $y = $this->GetY();
        $this->SetXY($x + 9, $y + 5.5);
        if (!empty($this->title)) {
            $this->Cell(110, 15, $this->title, 0, 1, 'L');
        }

        $this->SetXY($x, $y);
        $this->Rect(15, 23, 175, 0.3, 'F');
        // Line break.
        $this->Ln(26);

        $this->Cell(10, 3.5, '', 0, 0, 'C');
        $this->Cell(3.5, 3.5, '', 1, 0, 'C');
        $this->Image($CFG->dirroot . '/mod/offlinequiz/pix/kreuz.gif', $this->GetX() - 3.3, $this->Gety() + 0.2, 3.15, 0);
        $this->SetFont($font, 'B', 10);
        $this->Cell(31, 3.5, "", 0, 0, 'L');
        $this->Cell(55, 3.5, offlinequiz_str_html_pdf(get_string('lastname')), 0, 0, 'L');
        $this->Cell(60, 3.5, offlinequiz_str_html_pdf(get_string('firstname')), 0, 1, 'L');
        $this->Rect(15, ($this->GetY() + 1), 175, 0.3, 'F');
        $this->Ln(4.5);
        $x = $this->GetX();
        $y = $this->GetY();
        $this->Rect(145, 8, 25, 13);     // Square for the teachers to sign.

        $this->SetXY(145.5, 6.5);
        $this->SetFont($font, '', 8);
        $this->Cell(29, 7, get_string('invigilator', 'offlinequiz'), 0, 0, 'L');

        $this->SetXY($x, $y);
    }

    /**
     * (non-PHPdoc)
     * @see TCPDF::Footer()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Footer() {
        $font = offlinequiz_get_pdffont();
        $this->Line(11, 285, 14, 285);
        $this->Line(12.5, 283.5, 12.5, 286.5);
        $this->Line(193, 285, 196, 285);
        $this->Line(194.5, 283.5, 194.5, 286.5);
        $this->Rect(192, 282.5, 2.5, 2.5, 'F');                // Flip indicator.
        $this->Rect(15, 281, 175, 0.5, 'F');

        // Position at 1.7 cm from bottom.
        $this->SetY(-17);
        // FreeSans italic 8.
        $this->SetFont($font, 'I', 8);
        // Page number.
        $this->Cell(
            0,
            10,
            offlinequiz_str_html_pdf(get_string('page') . ' ' .
                                             $this->getAliasNumPage() . '/' . $this->getAliasNbPages() .
            ' ( ' . $this->listno . ' )'),
            0,
            0,
            'C'
        );
        // Print barcode for list.
        $y = $this->GetY() - 5;
        $x = 170;
        barcodewriter::print_barcode($this, $this->PageNo(), $x, $y);

        $this->Rect($x, $y, 0.2, 3.7, 'F');
    }
}
