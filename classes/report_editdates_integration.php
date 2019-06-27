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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>..

defined('MOODLE_INTERNAL') || die;


require_once($CFG->dirroot.'/mod/offlinequiz/lib.php');

class mod_offlinequiz_report_editdates_integration
        extends report_editdates_mod_date_extractor {

    public function __construct($course) {
        parent::__construct($course, 'offlinequiz');
        parent::load_data();
    }

    public function get_settings(cm_info $cm) {
        $offlinequiz = $this->mods[$cm->instance];
        return array('time'      => new report_editdates_date_setting(
                                        get_string('quizdate', 'offlinequiz'),
                                        $offlinequiz->time, self::DATETIME, true, 1),
                     'timeopen'  => new report_editdates_date_setting(
                                        get_string('reviewopens', 'offlinequiz'),
                                        $offlinequiz->timeopen, self::DATETIME, true, 1),
                     'timeclose' => new report_editdates_date_setting(
                                        get_string('reviewcloses', 'offlinequiz'),
                                        $offlinequiz->timeclose, self::DATETIME, true, 1)
        );
    }

    public function validate_dates(cm_info $cm, array $dates) {
        $errors = array();
        if ($dates['timeopen'] != 0 && $dates['timeclose'] != 0
                && $dates['timeclose'] < $dates['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'offlinequiz');
        }
        return $errors;
    }

    public function save_dates(cm_info $cm, array $dates) {
        parent::save_dates($cm, $dates);

        // Fetch module instance from $mods array.
        $offlinequiz = $this->mods[$cm->instance];

        $offlinequiz->instance = $cm->instance;
        $offlinequiz->coursemodule = $cm->id;

        // Updating date values.
        foreach ($dates as $datetype => $datevalue) {
            $offlinequiz->$datetype = $datevalue;
        }

        // Calling the update event method to change the calender events accordingly.
        offlinequiz_update_events($offlinequiz);
        offlinequiz_grade_item_update($offlinequiz);

    }
}
