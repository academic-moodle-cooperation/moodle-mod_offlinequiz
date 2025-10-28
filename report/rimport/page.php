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

namespace offlinequiz_result_import;

define('PAGE_STATUS_OK', 'ok');
define('PAGE_STATUS_ALIGNMENT_ERROR', 'notadjusted');
define('PAGE_STATUS_GROUP_ERROR', 'grouperror');
define('PAGE_STATUS_STUDENT_ID_ERROR', 'studentiderror');
define('PAGE_STATUS_PAGE_NUMBER_ERROR', 'pagenumbererror');
define('PAGE_STATUS_SUBMITTED', 'submitted');
define('PAGE_STATUS_INSECURE_RESULT', 'PAGE_STATUS_INSECURE_RESULT');
/**
 * scans a page for the group number
 * @package       offlinequiz_rimport
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.7
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_result_page {
    /**
     * page number of this page
     * @var int
     */
    public $pagenumber;
    /**
     * answers of this page
     * @var array
     */
    public $answers;
    /**
     * first answer in this page
     * @var int
     */
    public $startanswer;
    /**
     * id of the result entry
     * @var int
     */
    public $resultid;
    /**
     * expected studentidpositions
     * @var array
     */
    public $expectedstudentidpositions;
    /**
     * Expected group number positions
     * @var array
     */
    public $expectedgroupnumberpositions;
    /**
     * group entry in database
     * @var \stdClass
     */
    public $group;
    /**
     * image of this page
     * @var \Imagick
     */
    public $image;
    /**
     * scanproperties
     * @var \stdClass
     */
    public $scanproperties;
    /**
     * scannedpage id entry
     * @var int
     */
    public $scannedpageid;
    /**
     * positionproperties
     * @var \stdClass
     */
    public $positionproperties;
    /**
     * offlinequizid
     * @var int
     */
    public $offlinequizid;
    /**
     * status
     * @var string
     */
    public $status;
    /**
     * studentid ziphers
     * @var array
     */
    public $studentidziphers;
    /**
     * id of the teacher
     * @var int
     */
    public $teacherid;
    /**
     * id of the user
     * @var int
     */
    public $userid;

    /**
     * constructor
     * @param mixed $imagick
     * @param mixed $offlinequizid
     */
    public function __construct($imagick, $offlinequizid) {
        $imagick->quantizeimage(2, \Imagick::COLORSPACE_GRAY, 0, false, false);
        $this->image = $imagick;
        $this->offlinequizid = $offlinequizid;
    }
}
