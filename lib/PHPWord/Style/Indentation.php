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
 * @version    {something}
 */

/**
 * PHPWord_Style_Paragraph\Indentation
 *
 * @category   PHPWord
 * @package    PHPWord_Style_Paragraph
 * @copyright  Copyright (c) 2011 PHPWord
 * @link http://www.schemacentral.com/sc/ooxml/e-w_ind-1.html w:ind
 */
class PHPWord_Style_Indentation {

        /**
         * Indentation elements
         *
         * @var array
         */
        private static $_possibleElements = array(
            'left',
            'right',
            'hanging',
            'firstLine'
        );

        /**
         * Element
         *
         * @var array
         */
        private $_elements;

        /**
         *
         * @param array $attributes
         */
        public function __construct(array $attributes) {
                $this->_elements        = array();

                foreach ($attributes as $key => $value) {
                    $this->setIndentation($key, $value);
                }
        }

        /**
         * Set the indentation for a given attribute. The attribute must be one
         * of the following strings: left, right, hanging, or firstline. Value
         * must be an integer (or coercible to one).
         *
         * @param type $attribute The attribute to set the indent of.
         * @param integer $value The number of twips to indent.
         */
        public function setIndentation($attribute, $value) {
                if(self::isAttribute($attribute) && is_numeric($value)) {
                    $this->_elements[$attribute] = intval($value, 10);
                }
        }

        /**
         *
         * @param PHPWord_Shared_XMLWriter $objWriter
         */
        public function toXml(PHPWord_Shared_XMLWriter &$objWriter = NULL) {
            if(isset($objWriter)) {
                $objWriter->startElement('w:ind');
                foreach ($this->_elements as $element => $value) {
                    $objWriter->writeAttribute("w:$element", $value);
                }
                $objWriter->endElement();
            }
        }

        /**
         * Check that the string passed as an argument is an allowed attribute
         * type.
         *
         * @param string $attribute
         * @return Boolean Is attribute allowed.
         */
        private static function isAttribute($attribute) {
                return in_array($attribute, self::$_possibleElements);
        }
}
?>