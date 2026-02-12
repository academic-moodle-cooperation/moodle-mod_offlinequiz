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

namespace offlinequiz_correct\model;

use moodle_url;
use stdClass;

/**
 * Class correctpagedata
 *
 * @package    offlinequiz_correct
 * @copyright  2026 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class correct_page_data {
    /**
     * offlinequiz db object
     * @var stdClass
     */
    public stdClass $offlinequiz;
    /**
     * the url for the next error page
     * @var moodle_url
     */
    public ?moodle_url $nexturl;
    /**
     * the url for the next error page
     * @var moodle_url
     */
    public ?moodle_url $previousurl;
}
