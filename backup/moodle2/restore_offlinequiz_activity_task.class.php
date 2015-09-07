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
 * Define the restore_offlinequiz_activity_task
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/backup/moodle2/restore_offlinequiz_stepslib.php');


/**
 * Offlinequiz restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 *
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_offlinequiz_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Offlinequiz only has one structure step.
        $this->add_step(new restore_offlinequiz_activity_structure_step('offlinequiz_structure', 'offlinequiz.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('offlinequiz', array('intro'), 'offlinequiz');
        $contents[] = new restore_decode_content('offlinequiz', array('pdfintro'), 'offlinequiz');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('OFFLINEQUIZVIEWBYID',
                '/mod/offlinequiz/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('OFFLINEQUIZVIEWBYQ',
                '/mod/offlinequiz/view.php?q=$1', 'offlinequiz');
        $rules[] = new restore_decode_rule('OFFLINEQUIZINDEX',
                '/mod/offlinequiz/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * offlinequiz logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('offlinequiz', 'add',
                'view.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'update',
                'view.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'view',
                'view.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'review',
                'review.php?id={course_module}&resultid={offlinequiz_result_id}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'report',
                'report.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'editquestions',
                'view.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'delete result',
                'report.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'uncheck_participant',
                'participants.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'check_participant',
                'participants.php?id={course_module}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'addcategory',
                'view.php?id={course_module}', '{question_category}');
        $rules[] = new restore_log_rule('offlinequiz', 'view summary',
                'summary.php?result={offlinequiz_attempt_id}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'manualgrade',
                'comment.php?resultid={offlinequiz_attempt_id}&question={question}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'manualgrading',
                'report.php?mode=grading&q={offlinequiz}', '{offlinequiz}');
        $rules[] = new restore_log_rule('offlinequiz', 'preview',
                'view.php?id={course_module}', '{offlinequiz}');

        // All the ones calling to review.php have two rules to handle both old and new urls
        // in any case they are always converted to new urls on restore.
        // TODO: In Moodle 2.x (x >= 5) kill the old rules.
        // Note we are using the 'offlinequiz_attempt_id' mapping because that is the
        // one containing the offlinequiz_attempt->ids old an new for offlinequiz-attempt.
        $rules[] = new restore_log_rule('offlinequiz', 'attempt',
                'review.php?id={course_module}&resultid={offlinequiz_attempt}', '{offlinequiz}',
                null, null, 'review.php?attempt={offlinequiz_attempt}');
        // Old an new for offlinequiz-submit.
        $rules[] = new restore_log_rule('offlinequiz', 'submit',
                'review.php?id={course_module}&attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, null, 'review.php?attempt={offlinequiz_attempt_id}');
        $rules[] = new restore_log_rule('offlinequiz', 'submit',
                'review.php?attempt={offlinequiz_attempt_id}', '{offlinequiz}');
        // Old an new for offlinequiz-review.
        // Old an new for offlinequiz-start attempt.
        $rules[] = new restore_log_rule('offlinequiz', 'start attempt',
                'review.php?id={course_module}&attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, null, 'review.php?attempt={offlinequiz_attempt_id}');
        $rules[] = new restore_log_rule('offlinequiz', 'start attempt',
                'review.php?attempt={offlinequiz_attempt_id}', '{offlinequiz}');
        // Old an new for offlinequiz-close attempt.
        $rules[] = new restore_log_rule('offlinequiz', 'close attempt',
                'review.php?id={course_module}&attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, null, 'review.php?attempt={offlinequiz_attempt_id}');
        $rules[] = new restore_log_rule('offlinequiz', 'close attempt',
                'review.php?attempt={offlinequiz_attempt_id}', '{offlinequiz}');
        // Old an new for offlinequiz-continue attempt.
        $rules[] = new restore_log_rule('offlinequiz', 'continue attempt',
                'review.php?id={course_module}&attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, null, 'review.php?attempt={offlinequiz_attempt_id}');
        $rules[] = new restore_log_rule('offlinequiz', 'continue attempt',
                'review.php?attempt={offlinequiz_attempt_id}', '{offlinequiz}');
        // Old an new for offlinequiz-continue attempt.
        $rules[] = new restore_log_rule('offlinequiz', 'continue attemp',
                'review.php?id={course_module}&attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, 'continue attempt', 'review.php?attempt={offlinequiz_attempt_id}');
        $rules[] = new restore_log_rule('offlinequiz', 'continue attemp',
                'review.php?attempt={offlinequiz_attempt_id}', '{offlinequiz}',
                null, 'continue attempt');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('offlinequiz', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
