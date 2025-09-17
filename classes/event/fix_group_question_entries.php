<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * event to fix group question entries
 *
 * @package    mod_offlinequiz
 * @author  2014 Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright 2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since Moodle 2.7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_offlinequiz\event;

use core\event\course_restored;
use core\event\course_module_created;
/**
 * fix group question entries
 */
class fix_group_question_entries {

    /**
     * Event handler for course restored.
     *
     * @param course_restored $event
     */
    public static function on_course_restored(course_restored $event) {
        global $DB;
        $courseid = $event->objectid;
        $offlinequizzes = $DB->get_records('offlinequiz', ['course' => $courseid]);
        foreach ($offlinequizzes as $offlinequiz) {
            self::fix_single_offlinequiz($offlinequiz);
        }
    }
    /**
     * if the module is restored
     * @param \core\event\course_module_created $event
     * @return void
     */
    public static function on_module_restored(course_module_created $event) {
        global $DB;
        if ($event->other['modulename'] == 'offlinequiz') {
            $cm = get_coursemodule_from_id(null, $event->objectid, 0, false, MUST_EXIST);
            $offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance]);
            self::fix_single_offlinequiz($offlinequiz);
        }
    }
    /**
     * fix single event
     * @param mixed $offlinequiz
     * @return void
     */
    public static function fix_single_offlinequiz($offlinequiz) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/offlinequiz/locallib.php');
        $sql = "SELECT ogq.id as id, ogq.questionid as oldquestion, ogq.maxmark as grade, qrv.questionid as newquestion
                      FROM {offlinequiz_group_questions} ogq
                      JOIN {question_versions} qv on qv.questionid = ogq.questionid
                      JOIN {question_bank_entries} qbe on qv.questionbankentryid = qbe.id
                 LEFT JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                      JOIN {question_references} qr on ogq.id = qr.itemid
                      JOIN {question_bank_entries} rqbe on rqbe.id = qr.questionbankentryid
                      JOIN {question_versions} qrv on qrv.version = qr.version and qr.questionbankentryid = qrv.questionbankentryid
                      JOIN {question_categories} qrc on qrc.id = rqbe.questioncategoryid
                     WHERE qr.component = 'mod_offlinequiz'
                       AND qr.questionbankentryid <> qv.questionbankentryid
                       AND qc.id IS NULL
    		           AND ogq.offlinequizid = :oqid";
        $tofix = $DB->get_records_sql($sql, ['oqid' => $offlinequiz->id]);
        $sql = "SELECT c.id
               FROM {context} c
               JOIN {course_modules} cm ON cm.id = c.instanceid and contextlevel = 70
               JOIN {modules} m on cm.module = m.id and m.name = 'offlinequiz'
               where cm.instance = :offlinequizid";
        $contextid = $DB->get_field_sql($sql, ['offlinequizid' => $offlinequiz->id]);
        foreach ($tofix as $groupquestion) {
            offlinequiz_update_question_instance($offlinequiz, $contextid,
            $groupquestion->oldquestion, $groupquestion->grade, $groupquestion->newquestion);
        }
    }
}
