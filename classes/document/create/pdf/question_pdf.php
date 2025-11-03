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
 * offlinequiz_question_pdf
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_pdf extends offlinequiz_pdf {
    /**
     * temporary files
     * @var array
     */
    private $tempfiles = [];

    /**
     * (non-PHPdoc)
     * @see TCPDF::Header()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Header() {
        $this->SetFont(offlinequiz_get_pdffont(), 'I', 8);
        // Title.
        $this->Ln(15);
        if (!empty($this->title)) {
            $this->Cell(0, 10, $this->title, 0, 0, 'C');
        }
        $this->Rect(15, 25, 175, 0.3, 'F');
        // Line break.
        $this->Ln(15);
    }

    /**
     * (non-PHPdoc)
     * @see TCPDF::Footer()
     */
    // @codingStandardsIgnoreLine  This function name is not moodle-standard but I need to overwrite TCPDF
    public function Footer() {
        // Position at 2.5 cm from bottom.
        $this->SetY(-25);
        $this->SetFont(offlinequiz_get_pdffont(), 'I', 8);
        // Page number.
        $this->Cell(0, 10, offlinequiz_str_html_pdf(get_string('page')) . ' ' . $this->getAliasNumPage() .
                '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}
