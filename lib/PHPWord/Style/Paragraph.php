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
 * PHPWord_Style_Paragraph
 *
 * @category   PHPWord
 * @package    PHPWord_Style
 * @copyright  Copyright (c) 2011 PHPWord
 */
class PHPWord_Style_Paragraph {
	
	/**
	 * Paragraph alignment
	 * 
	 * @var string
	 */
	private $_align;
	
	/**
	 * Space before Paragraph
	 * 
	 * @var int
	 */
	private $_spaceBefore;
	
	/**
	 * Space after Paragraph
	 * 
	 * @var int
	 */
	private $_spaceAfter;
	
	/**
	 * Spacing between breaks
	 * 
	 * @var int
	 */
	private $_spacing;

	/**
	 * Set of Custom Tab Stops
	 *
	 * @var array
	 */
	private $_tabs;

	/**
	 * Paragraph indentations
	 *
	 * @var PHPWord_Style_Indentation
	 */
	private $_indentations;

	/**
	 * New Paragraph Style
	 */
	public function __construct() {
		$this->_align           = null;
		$this->_spaceBefore     = null;
		$this->_spaceAfter      = null;
		$this->_spacing         = null;
		$this->_tabs            = null;
		$this->_indentations    = null;
	}
	
	/**
	 * Set Style value
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function setStyleValue($key, $value) {
		if($key == '_spacing') {
			$value += 240; // because line height of 1 matches 240 twips
		} else if($key === '_tabs') {
			$value = new PHPWord_Style_Tabs($value);
		} else if($key === '_indentations') {
			$value = new PHPWord_Style_Indentation($value);
		}
		$this->$key = $value;
	}

	/**
	 * Get Paragraph Alignment
	 * 
	 * @return string
	 */
	public function getAlign() {
		return $this->_align;
	}

	/**
	 * Set Paragraph Alignment
	 * 
	 * @param string $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setAlign($pValue = null) {
		if(strtolower($pValue) == 'justify') {
			// justify becames both
			$pValue = 'both';
		}
		$this->_align = $pValue;
		return $this;
	}

	/**
	 * Get Space before Paragraph
	 * 
	 * @return string
	 */
	public function getSpaceBefore() {
		return $this->_spaceBefore;
	}

	/**
	 * Set Space before Paragraph
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpaceBefore($pValue = null) {
	   $this->_spaceBefore = $pValue;
	   return $this;
	}

	/**
	 * Get Space after Paragraph
	 * 
	 * @return string
	 */
	public function getSpaceAfter() {
		return $this->_spaceAfter;
	}

	/**
	 * Set Space after Paragraph
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpaceAfter($pValue = null) {
	   $this->_spaceAfter = $pValue;
	   return $this;
	}

	/**
	 * Get Spacing between breaks
	 * 
	 * @return int
	 */
	public function getSpacing() {
		return $this->_spacing;
	}

	/**
	 * Set Spacing between breaks
	 * 
	 * @param int $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setSpacing($pValue = null) {
	   $this->_spacing = $pValue;
	   return $this;
	}

	/**
	 * Set indentations for the paragraph.
	 *
	 * @param PHPWord_Style_Indentation $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setIndentions(PHPWord_Style_Indentation &$pValue = null) {
		$this->_indentations = $pValue;
		return $this;
	}

	/**
	 * Set tabs for the paragraph.
	 *
	 * @param PHPWord_Style_Tabs $pValue
	 * @return PHPWord_Style_Paragraph
	 */
	public function setTabs(PHPWord_Style_Tabs &$pValue = null) {
		$this->_tabs = $pValue;
		return $this;
	}

	/**
	 *
	 * @return PHPWord_Style_Tabs
	 */
	public function getTabs() {
		return $this->_tabs;
	}

	/**
	 * @return PHPWord_Style_Indentation
	 */
	public function getIndentation() {
		return $this->_indentations;
	}

        public function toXml(PHPWord_Shared_XMLWriter &$objWriter = NULL) {
            if(!is_null($objWriter)) {
                $objWriter->startElement("w:pPr");

                if(!is_null($this->_align)) {
                    $objWriter->startElement('w:jc');
                    $objWriter->writeAttribute('w:val', $this->_align);
                    $objWriter->endElement();
                }

                if(!is_null($this->_spaceBefore) || !is_null($this->_spaceAfter) || !is_null($this->_spacing)) {
                    $objWriter->startElement('w:spacing');

                    if(!is_null($this->_spaceBefore)) {
                        $objWriter->writeAttribute('w:before', $this->_spaceBefore);
                    }
                    if(!is_null($this->_spaceAfter)) {
                        $objWriter->writeAttribute('w:after', $this->_spaceAfter);
                    }
                    if(!is_null($this->_spacing)) {
                        $objWriter->writeAttribute('w:line', $this->_spacing);
                        $objWriter->writeAttribute('w:lineRule', 'auto');
                    }

                    $objWriter->endElement();
                }

                if(!is_null($this->_tabs)) {
                    $this->_tabs->toXml($objWriter);
                }

                // Write the w:ind element
                if(!is_null($this->_indentations)) {
                    $this->_indentations->toXml($objWriter);
                }

                $objWriter->endElement();
            }
        }
}
?>