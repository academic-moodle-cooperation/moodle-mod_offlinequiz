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

defined('MOODLE_INTERNAL') || die();
define('PAGE_STATUS_OK', 'ok');
define('PAGE_STATUS_ALIGNMENT_ERROR', 'notadjusted');
define('PAGE_STATUS_GROUP_ERROR', 'grouperror');
define('PAGE_STATUS_STUDENT_ID_ERROR', 'studentiderror');
define('PAGE_STATUS_PAGE_NUMBER_ERROR', 'pagenumbererror');
define('PAGE_STATUS_SUBMITTED', 'submitted');
define('PAGE_STATUS_INSECURE_RESULT', 'PAGE_STATUS_INSECURE_RESULT');
class offlinequiz_result_page {


    public $pagenumber;
    public $answers;
    public $startanswer;
    public $resultid;
    public $expectedstudentidpositions;
    public $expectedgroupnumberpositions;
    public $group;
    public $image;
    public $scanproperties;
    public $scannedpageid;
    public $positionproperties;
    public $offlinequizid;
    public $status;
    public $studentidziphers;
    public $teacherid;
    public $userid;


    public function __construct($imagick, $offlinequizid) {
        $imagick->quantizeimage(2, \Imagick::COLORSPACE_GRAY, 0, false, false);
        $this->image = $imagick;
        $this->offlinequizid = $offlinequizid;
    }

}