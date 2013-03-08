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
 * @link http://www.schemacentral.com/sc/ooxml/e-w_abstractNum-1.html Abstract Numbering Definition
 */
class PHPWord_Numbering_AbstractNumbering {

    private $_index;

    /**
     *
     * @var string
     * @link http://www.schemacentral.com/sc/ooxml/e-w_name-4.html
     */
    private $_name = "";

    /**
     *
     * @var array PHPWord_Numbering_Level
     */
    private $_levels;

    public function __construct($name, array $levels = NULL) {
        $this->_name = $name;

        if(!is_null($levels)) {
            // Add all the PHPWord_Numbering_Levels to the array
            foreach($levels as &$level) {
                if($level instanceof PHPWord_Numbering_Level) {
                    $this->addLevel($level);
                }
            }
        }
    }

    public function setIndex($index = 0) {
        $this->_index = $index;
    }

    public function getIndex() {
        return $this->_index + 1;
    }

    public function addLevel(PHPWord_Numbering_Level &$level) {
        $currentMaxLevel = count($this->_levels);
        $level->setNumberingLevel($currentMaxLevel);
        $this->_levels[] = $level;
    }

    /**
     * If there are more than 1 levels in the _levels array then this is a
     * "multiLevel" type definition. Otherwise, it is a "singleLevel". There is
     * no support for "hybridMultilevel".
     *
     * @return string
     * @link http://www.schemacentral.com/sc/ooxml/t-w_ST_MultiLevelType.html
     */
    private function getMultilevelType() {
        if(count($this->_levels) === 1) {
            return "singleLevel";
        } else {
            return "multiLevel";
        }
    }

    public function toXml(PHPWord_Shared_XMLWriter &$objWriter = NULL) {
        if(isset($objWriter)) {
            $objWriter->startElement("w:abstractNum");
            $objWriter->writeAttribute("w:abstractNumId", $this->getIndex());

            $objWriter->startElement("w:multilevelType");
            $objWriter->writeAttribute("w:val", $this->getMultilevelType());
            $objWriter->endElement();

            $objWriter->startElement("w:name");
            $objWriter->writeAttribute("w:val", $this->_name);
            $objWriter->endElement();

            foreach ($this->_levels as $key => &$value) {
                $value->toXml($objWriter);
            }

            $objWriter->endElement();
        }
    }
}

?>