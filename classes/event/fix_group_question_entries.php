<?php
namespace mod_offlinequiz\event;


defined('MOODLE_INTERNAL') || die();

use core\event\course_restored;

class fix_group_question_entries {
    
    /**
     * Event handler for course restored.
     *
     * @param course_restored $event
     */
    public static function on_course_restored(course_restored $event) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/offlinequiz/locallib.php');
        $courseid = $event->objectid;
        $offlinequizzes = $DB->get_records('offlinequiz', ['course' => $courseid]);
        foreach ($offlinequizzes as $offlinequiz) {
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
            foreach ($tofix as $groupquestion)  {
                offlinequiz_update_question_instance($offlinequiz, $contextid, $groupquestion->oldquestion, $groupquestion->grade, $groupquestion->newquestion);
            }
        }

    }
}