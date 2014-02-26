<?php
/**
 * PHPWord
 *
 * Copyright (c) 2011 PHPWord
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPWord
 * @package    PHPWord
 * @copyright  Copyright (c) 010 PHPWord
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    Beta 0.6.3, 08.07.2011
 */

/**
 * Numbering Level Definition
 *
 * @link http://www.schemacentral.com/sc/ooxml/e-w_lvl-1.html Numbering Level Definition
 */
class PHPWord_Numbering_Level {

    /**
     * Numbering Level
     *
     * @var int
     * @link http://www.schemacentral.com/sc/ooxml/a-w_ilvl-1.html
     */
    private $_ilvl;

    /**
     * Starting Value
     *
     * @var int
     * @link http://www.schemacentral.com/sc/ooxml/e-w_start-1.html Starting Value
     */
    private $_start = 1;

    /**
     * Numbering Format
     *
     * @var string
     * @link http://www.schemacentral.com/sc/ooxml/e-w_numFmt-3.html
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_NumberFormat.html Numbering Format
     */
    private $_numFmt;

    /**
     * Restart Numbering Level Symbol
     * 
     * @var int
     * @link http://www.schemacentral.com/sc/ooxml/e-w_lvlRestart-1.html
     */
    private $_lvlRestart;

    /**
     * Content Between Numbering Symbol and Paragraph Text
     *
     * @var string
     * @link http://www.schemacentral.com/sc/ooxml/e-w_suff-1.html
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_LevelSuffix.html
     */
    private $_suff = "tab";

    /**
     * Numbering Level Text
     * 
     * @var string
     * @link http://www.schemacentral.com/sc/ooxml/e-w_lvlText-1.html
     */
    private $_lvlText = NULL;

    /**
     * Justification
     *
     * @var string
     * @link http://www.schemacentral.com/sc/ooxml/e-w_lvlJc-1.html
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_Jc.html
     */
    private $_lvlJc;

    /**
     * Numbering Level Associated Paragraph Properties
     *
     * @var PHPWord_Style_Paragraph
     */
    private $_pPr = NULL;

    /**
     *
     * @var string
     */
    private $_rPr = NULL;

    /**
     *
     * @param type $start
     * @param type $numFmt
     * @param type $lvlText
     * @param type $lvlJc
     * @param PHPWord_Style_Paragraph $style
     */
    public function __construct($start, $numFmt, $lvlText, $lvlJc, PHPWord_Style_Paragraph &$para = NULL, PHPWord_Style_Font &$font = NULL) {
        $this->_start = $start;
        $this->setNumberFormat($numFmt);
        $this->setLevelText($lvlText);
        $this->setLevelJustification($lvlJc);
        $this->_pPr = $para;
        $this->_rPr = $font;
    }

    /**
     * Set the number format of the PHPWord_Numbering_Level instance. The format
     * must be one of the valid values. If it is not, the value is defaulted to
     * "decimal". See link for full list of valid values.
     * 
     * @param string $newFmt
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_NumberFormat.html
     */
    public function setNumberFormat($newFmt) {
        if(in_array($newFmt, self::$allowedNumberFormats)) {
            $this->_numFmt = $newFmt;
        } else {
            $this->_numFmt = self::$allowedNumberFormats[0];
        }
    }

    public function setLevelText($newLvlTxt) {
        $this->_lvlText = $newLvlTxt;
    }

    /**
     * Set the justification type of the PHPWord_Numbering_Level instance. The
     * format must be one of the valid values. If it is not, the value is
     * defaulted to: "left". See link for full list of valid values.
     * 
     * @param type $newLvlJustification
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_Jc.html
     */
    public function setLevelJustification($newLvlJustification) {
        if(in_array($newLvlJustification, self::$allowedHorAlignType)) {
            $this->_lvlJc = $newLvlJustification;
        } else {
            $this->_lvlJc = self::$allowedHorAlignType[0];
        }
    }

    public function setNumberingLevel($level) {
        $this->_ilvl = $level;
    }

    public function toXml(PHPWord_Shared_XMLWriter &$objWriter = NULL) {
        if(isset($objWriter)) {
            $objWriter->startElement("w:lvl");
            $objWriter->writeAttribute("w:ilvl", $this->_ilvl);

            $objWriter->startElement("w:start");
            $objWriter->writeAttribute("w:val", $this->_start);
            $objWriter->endElement();

            $objWriter->startElement("w:numFmt");
            $objWriter->writeAttribute("w:val", $this->_numFmt);
            $objWriter->endElement();

            $objWriter->startElement("w:lvlRestart");
            $objWriter->writeAttribute("w:val", $this->_lvlRestart);
            $objWriter->endElement();

            $objWriter->startElement("w:suff");
            $objWriter->writeAttribute("w:val", $this->_suff);
            $objWriter->endElement();

            $objWriter->startElement("w:lvlText");
            $objWriter->writeAttribute("w:val", $this->_lvlText);
            $objWriter->endElement();

            $objWriter->startElement("w:lvlJc");
            $objWriter->writeAttribute("w:val", $this->_lvlJc);
            $objWriter->endElement();

            // Generate XML if a numbering level associated paragraph property
            // is supplied
            if(!is_null($this->_pPr)) {
                $this->_pPr->toXml($objWriter);
            }

            // Generate XML if a numbering level associated paragraph property
            // is supplied
            if(!is_null($this->_rPr)) {
                $this->_rPr->toXml($objWriter);
            }

            $objWriter->endElement();
        }
    }

    /**
     * Obviously not the full list.
     * Should be extended.
     * @var type
     */
    private static $allowedNumberFormats = array(
        "decimal",       // Decimal Numbers
        "decimalZero",   // Initial Zero Arabic Numerals
        "upperRoman",    // Uppercase Roman Numerals
        "lowerRoman",    // Lowercase Roman Numerals
        "upperLetter",   // Uppercase Latin Alphabet
        "lowerLetter",   // Lowercase Latin Alphabet
        "ordinal",       // Ordinal
        "cardinalText",  // Cardinal Text
        "ordinalText",   // Ordinal Text
        "hex",           // Hexadecimal Numbering,
        "bullet",        // Bullet
        "chicago"        // Chicago Manual of Style
    );

    const NUMFMT_DECIMAL       = "decimal";
    const NUMFMT_DECIMAL_ZERO  = "decimalZero";
    const NUMFMT_UPPER_ROMAN   = "upperRoman";
    const NUMFMT_LOWER_ROMAN   = "lowerRoman";
    const NUMFMT_UPPER_LETTER  = "upperLetter";
    const NUMFMT_LOWER_LETTER  = "lowerLetter";
    const NUMFMT_ORDINAL       = "ordinal";
    const NUMFMT_CARDINAL_TEXT = "cardinalText";
    const NUMFMT_ORDINAL_TEXT  = "ordinalText";
    const NUMFMT_HEX           = "hex";
    const NUMFMT_BULLET        = "bullet";
    const NUMFMT_CHICAGO       = "chicago";

    private static $allowedHorAlignType = array(
        "left",          // Align Left
        "center",        // Align Center
        "right",         // Align Right
        "both",          // Justified
        "mediumKashida", // Medium Kashida Length
        "distribute",    // Distribute All Characters Equally
        "numTab",        // Align to List Tab
        "highKashida",   // Widest Kashida Length
        "lowKashida",    // Low Kashida Length
        "thaiDistribute" // Thai Language Justification
    );
}