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

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/pagepositionscanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/pagenumberscanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/resultscanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/groupnumberscanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/studentidscanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/boxscanner.php');



class offlinequiz_result_engine {

    public $offlinequizid;
    public $contextid;
    private $pagepositionscanner;
    private $pagenumberscanner;
    private $groupnumberscanner;
    private $studentidscanner;
    private $resultscanner;
    private $page;

    public function __construct($offlinequiz, $contextid,$filepath) {


        $this->contextid = $contextid;
        $this->offlinequizid = $offlinequiz->id;
        $this->page = new offlinequiz_result_page(new \Imagick(realpath($filepath)),$this->offlinequizid);
        $this->pagepositionscanner = new offlinequiz_pagepositionscanner($this->page);
        $this->groupnumberscanner = new offlinequiz_groupnumberscanner(new pixelcountboxscanner());
        $this->pagenumberscanner = new offlinequiz_pagenumberscanner();
        $this->studentidscanner = new offlinequiz_studentid_scanner(new pixelcountboxscanner());
        $this->resultscanner = new offlinequiz_resultscanner(new pixelcountboxscanner());
    }


    public function scanpage() {
        $this->pagepositionscanner->scanposition();
        if(!($this->page->status == PAGE_STATUS_OK)) {
            return $this->page;
        }

        $this->groupnumberscanner->scan_group_number($this->page);
        if(!($this->page->status == PAGE_STATUS_OK)) {
            return $this->page;
        }
        $this->studentidscanner->scan_studentid($this->page);
        if(!($this->page->status == PAGE_STATUS_OK)) {
            return $this->page;
        }
        $this->pagenumberscanner->scan_page_number($this->page);
        if(!($this->page->status == PAGE_STATUS_OK)) {
            return $this->page;
        }
        $this->resultscanner->scanresults($this->page);
        if(!($this->page->status == PAGE_STATUS_OK)) {
            return $this->page;
        }
        return $this->page;

    }


}