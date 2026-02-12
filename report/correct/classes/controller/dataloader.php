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

namespace offlinequiz_correct\controller;

use offlinequiz_correct\model\correct_page_data;

/**
 * Class dataloader
 *
 * @package    offlinequiz_correct
 * @copyright  2026 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataloader {
    /**
     * pageid that this data is for
     * @var int
     */
    private $pageid;

    /**
     * @var correct_page_data
     */
    private correct_page_data $data;
    /**
     *
     */
    public function __construct(int $pageid) {
        $this->pageid = $pageid;
        $this->load_data();
    }
    /**
     * load all data for this page
     * @return void
     */
    private function load_data() {
        $this->data = new correct_page_data();
        $this->load_general();
        $this->load_urls();
    }

    /**
     * load general data
     * @return void
     */
    private function load_general() {
        global $DB;
        $sql = "SELECT o.*
                  FROM {offlinequiz} o
                  JOIN {offlinequiz_scanned_pages} sp ON o.id = sp.offlinequizid
                 WHERE sp.id = :pageid";
        $this->data->offlinequiz = $DB->get_record_sql($sql, ['pageid' => $this->pageid]);
    }

    /**
     * load all urls
     * @return void
     */
    private function load_urls() {
        global $DB;
        $sql = "SELECT MAX(id)
                  FROM {offlinequiz_scanned_pages} sp
                 WHERE offlinequizid = :offlinequizid and sp.id < :pageid";
        $this->data->previousurl = $DB->get_field_sql($sql, [
            'offlinequizid' => $this->data->offlinequiz->id,
            'pageid' => $this->pageid,
        ]);
        $sql = "SELECT MAX(id)
                  FROM {offlinequiz_scanned_pages} sp
                 WHERE offlinequizid = :offlinequizid and sp.id < :pageid";
        $this->data->nexturl = $DB->get_field_sql($sql, [
            'offlinequizid' => $this->data->offlinequiz->id,
            'pageid' => $this->pageid,
        ]);
    }

    /**
     * loads the data
     */
    public function get_data() {
        return $this->data;
    }
}
