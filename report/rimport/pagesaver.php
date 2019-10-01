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

/**
 * The results import report for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind
 * @copyright     2017 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace offlinequiz_result_import;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');

class offlinequiz_page_saver {


    public function save_page_information(offlinequiz_result_page $page) {
        global $DB;
        if (!$page->scannedpageid) {
            $scannedpage = new \stdClass();
            $scannedpage->offlinequizid = $page->offlinequizid;
            // TODO
            $filename = null;
            $scannedpage->status = 'ok';
            $scannedpage->time = time();
            $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
            $page->scannedpageid = $scannedpage->id;
            // TODO warningfilename und info aus der Tabelle rausnehmen
        }

        $this->save_page_corners($page);
        if ($page->status == PAGE_STATUS_ALIGNMENT_ERROR || $page->status == PAGE_STATUS_GROUP_ERROR) {
            $this->save_status($page);
            return;
        }
        $this->save_group_number($page);
        $this->save_user_id($page);
        if ($page->status == PAGE_STATUS_STUDENT_ID_ERROR || $page->status == PAGE_STATUS_PAGE_NUMBER_ERROR) {
            $this->save_status($page);
            return;
        }
        $this->save_pagenumber($page);
        $this->save_choices($page);

        $this->save_status($page);

    }

    protected function save_choices(offlinequiz_result_page $page) {
        global $DB;
        if ($page && $page->scannedpageid) {
            $conditions = array("scannedpageid" => $page->scannedpageid);
            $DB->delete_records("offlinequiz_choices", $conditions);
            $rows = $this->get_results_for_db($page);
            $DB->insert_records("offlinequiz_choices", $rows);
        }
    }

    private function get_results_for_db(offlinequiz_result_page $page) {
         $answers = $page->answers;
         $startnumber = $page->startanswer;
         $rows = array();
         $k = 0;
        for ($i = 0; $i < count($answers); $i++) {
            for ($j = 0; $j < count($answers[$i]); $j++) {
                $row = new \stdClass();
                $row->scannedpageid = $page->scannedpageid;
                $row->slotnumber = $i + $startnumber;
                $row->choicenumber = $j;
                $row->value = $answers[$i][$j]['result'];
                $rows[$k] = $row;
                $k++;
            }

        }
         return $rows;
    }


    private function save_status($page) {
        if ($page->status) {

            global $DB;
            $conditions = array('id' => $page->scannedpageid);
            $scannedpage = $DB->get_record('offlinequiz_scanned_pages', $conditions);
            if ($scannedpage) {
                if ($page->status != PAGE_STATUS_OK && $page->status != PAGE_STATUS_SUBMITTED) {
                    $scannedpage->status = 'error';
                    $scannedpage->error = $page->status;
                } else {
                    $scannedpage->status = $page->status;
                    $scannedpage->error = null;
                }
            }

        }
    }

    private function save_user_id(offlinequiz_result_page $page) {
        if ($page->studentidziphers) {
            $studentnumber = '';
            foreach ($page->studentidziphers as $zipher) {
                $studentnumber .= $zipher;
            }
            global $DB;
            $conditions = array('id' => $page->scannedpageid);
            $DB->set_field('offlinequiz_scanned_pages', 'userkey', $studentnumber, $conditions);

        }

    }

    protected function save_pagenumber(offlinequiz_result_page $page) {
        if ($page->pagenumber) {
            global $DB;
            $conditions = array('id' => $page->scannedpageid);
            $DB->set_field('offlinequiz_scanned_pages', 'pagenumber', $page->pagenumber, $conditions);
        }
    }

    protected function save_group_number(offlinequiz_result_page $page) {
        if ($page->group->id) {
            global $DB;
            $conditions = array('id' => $page->scannedpageid);
            $DB->set_field('offlinequiz_scanned_pages', 'groupnumber', $page->group->id, $conditions);

        }
    }

    protected function save_page_corners(offlinequiz_result_page $page) {
        global $DB;
        $conditions = array("scannedpageid" => $page->scannedpageid);
        $corners = $DB->get_records("offlinequiz_page_corners", $conditions);
        if ($corners) {
            foreach ($corners as $corner) {
                $this->update_corner($page, $corner);
            }
        } else {
            for ($i = 0; $i < 4; $i++) {
                $cornername = $this->get_fitting_corner_name($i);
                $point = $page->positionproperties[$cornername];
                $corner = new \stdClass();
                $corner->scannedpageid = $page->scannedpageid;
                $corner->x = $point->getx();
                $corner->y = $point->gety();
                $corner->position = $i;
                $DB->insert_record("offlinequiz_page_corners", $corner);
            }
        }
    }

    private function update_corner(offlinequiz_result_page $page, $corner) {
        global $DB;
        $cornername = $this->get_fitting_corner_name($corner->position);
        $point = $page->positionproperties[$cornername];
        $corner->x = round($point->getx());
        $corner->y = round($point->gety());
        $DB->update_record("offlinequiz_page_corners", $corner);
    }

    private function get_fitting_corner_name($cornernumber) {
        if ($cornernumber == 0 ) {
            return "upperleft";
        } else if ($cornernumber == 1) {
            return "upperright";
        } else if ($cornernumber == 2) {
            return "lowerleft";
        } else if ($cornernumber == 3) {
            return "lowerright";
        } else {
            return "UNKNOWN_CORNER";
        }

    }

}