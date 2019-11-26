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

defined('MOODLE_INTERNAL') || die();


/**
 * puts all PhpWord dependencies to the autoload from PHP
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind
 * @copyright     2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.8
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 *
 * @param string $pclassname the classname to load
 */
function mod_offlinequiz_phpword_autoload ($pclassname) {
    $filename = __DIR__ . "/" . str_replace('\\', '/', $pclassname) . ".php";
    if (file_exists($filename)) {
        include($filename);
    }
}
// Load PhpWord classes through autoload.
spl_autoload_register("mod_offlinequiz_phpword_autoload");