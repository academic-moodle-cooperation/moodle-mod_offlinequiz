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

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/page.php');

class offlinequiz_resultsaver {
	
	
	public function save_result(offlinequiz_result_page $page) {
		$DB->set_field('offlinequiz_scanned_pages', 'error',$page->status,
				array('id' => $page->scannedpageid
				));
		$this->save_page_corners($page);
		if($page->status==PAGE_STATUS_ALIGNMENT_ERROR|| $page->status == PAGE_STATUS_GROUP_ERROR) {
			//TODO SAVE STATUS
			return;
		}
		$this->save_user_id($page);
		if($page->status== PAGE_STATUS_GROUP_ERROR || $page->status == PAGE_STATUS_STUDENT_ID_ERROR) {
			//TODO SAVE STATUS
			return;
		}
		$this->save_groupnumber($page);
			
		$this->save_pagenumber($page);

	}
	
	private function save_user_id(offlinequiz_result_page $page) {
		if($page->studentidziphers) {
			$studentnumber = ''; 
			foreach ($page->studentidziphers as $zipher) {
				$studentnumber .= $zipher;
			}
			global $DB;
			$conditions = array('id' =>  $page->scannedpageid);
			$DB->set_field('offlinequiz_scanned_pages', 'userkey', $studentnumber, $conditions);
			//TODO save userid
		}
		
		
	}
	
	private function save_pagenumber(offlinequiz_result_page $page) {
		if($page->pagenumber) {
			global $DB;
			$conditions = array('id' =>  $page->scannedpageid);
			$DB->set_field('offlinequiz_scanned_pages', 'pagenumber', $page->pagenumber, $conditions);
		}
	}
	
	private function save_groupnumber(offlinequiz_result_page $page) {
		if($page->group->id) {
			global $DB;
			$conditions = array('id' =>  $page->scannedpageid);
			$DB->set_field('offlinequiz_scanned_pages', 'groupnumber', $page->group->id, $conditions);
			
		}
	}
	
	private function save_page_corners(offlinequiz_result_page $page) {
		global $DB;
		$conditions = array("scannedpageid" => $page->scannedpageid);
		$corners = $DB->get_records("offlinequiz_page_corners", $conditions);
		if($corners) {
			foreach ($corners as $corner) {
				$this->change_corner($page,$corner);
				$DB->update_record("offlinequiz_page_corners", $corner);
				print_object($corner);
			}
			
		} else {
			for( $i=0; $i<4; $i++ ) {
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
	
	private function change_corner(offlinequiz_result_page $page, $corner) {
		$cornername = $this->get_fitting_corner_name($corner->position);
		$point = $page->positionproperties[$cornername];
		$corner->x = round($point->getx());
		$corner->y = round($point->gety());
	}
	
	private function get_fitting_corner_name($cornernumber) {
		if($cornernumber == 0 ) {
			return "upperleft";
		}
		else if($cornernumber == 1) {
			return "upperright";
		}
		else if($cornernumber == 2) {
			return "lowerleft";
		}
		else if($cornernumber == 3) {
			return "lowerright";
		} else {
			return "UNKNOWN_CORNER";
		}
		
	}

}