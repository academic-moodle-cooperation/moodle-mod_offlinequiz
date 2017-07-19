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
 * This page allows the teacher to enter a manual grade for a particular question.
 * This page is expected to only be used in a popup window.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');

$resultid = required_param('resultid', PARAM_INT); // Result ID.
$slot = required_param('slot', PARAM_INT); // Question number in result.

$PAGE->set_url('/mod/offlinequiz/comment.php', array('resultid' => $resultid, 'slot' => $slot));

// Get all the data from the DB.
if (! $result = $DB->get_record("offlinequiz_results", array("id" => $resultid))) {
    print_error("No such result ID exists");
}
if (! $offlinequiz = $DB->get_record("offlinequiz", array("id" => $result->offlinequizid))) {
    print_error("The offlinequiz with id $result->offlinequiz belonging to result $result is missing");
}
if (! $course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
    print_error("The course with id $offlinequiz->course that the offlinequiz with id $offlinequiz->id belongs to is missing");
}
if (! $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    print_error("The course module for the offlinequiz with id $offlinequiz->id is missing");
}

// Can only grade finished results.
if ($result->status != 'complete') {
    print_error('resultnotcomplete', 'offlinequiz');
}

// Check login and permissions.
require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/offlinequiz:grade', $context);

// Load the questions needed by page.
if (!$quba = question_engine::load_questions_usage_by_activity($result->usageid)) {
    print_error('Could not load question usage');
}

$slotquestion = $quba->get_question($slot);

// Print the page header.
$PAGE->set_pagelayout('popup');
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($slotquestion->name));

// Process any data that was submitted.
if (data_submitted() && confirm_sesskey()) {
    if (optional_param('submit', false, PARAM_BOOL)) {
        // Set the mark in the quba's slot.
        $transaction = $DB->start_delegated_transaction();
        $quba->process_all_actions(time());
        question_engine::save_questions_usage_by_activity($quba);
        $transaction->allow_commit();

        // Set the result's total mark (sumgrades).
        $result->sumgrades = $quba->get_total_mark();
        $result->timemodified = time();
        $DB->update_record('offlinequiz_results', $result);

        // Log this action.
        $params = array(
            'objectid' => $slotquestion->id,
            'courseid' => $course->id,
            'context' => context_module::instance($cm->id),
            'other' => array(
                'offlinequizid' => $offlinequiz->id,
                'resultid' => $result->id,
                'slot' => $slot
            )
        );
        $event = \mod_offlinequiz\event\question_manually_graded::create($params);
        $event->trigger();

        // Update the gradebook.
        offlinequiz_update_grades($offlinequiz);
        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
        close_window(2, true);
        die;
    }
}

// Print the comment form.
echo '<form method="post" class="mform" id="manualgradingform" action="' .
        $CFG->wwwroot . '/mod/offlinequiz/comment.php">';

$options = new mod_offlinequiz_display_options();
$options->hide_all_feedback();
$options->manualcomment = question_display_options::EDITABLE;

if (property_exists($slotquestion, '_number')) {
    echo $quba->render_question($slot, $options, $slotquestion->_number);
} else {
    echo $quba->render_question($slot, $options);
}

?>
  <div>
    <input type="hidden" name="resultid" value="<?php echo $result->id; ?>" />
    <input type="hidden" name="slot" value="<?php echo $slot; ?>" /> <input
        type="hidden" name="slots" value="<?php echo $slot; ?>" /> <input
        type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
  </div>
  <fieldset class="hidden">
    <div>
        <div class="fitem">
            <div class="fitemtitle">
                <div class="fgrouplabel">
                    <label> </label>
                </div>
            </div>
            <fieldset class="felement fgroup">
                <input class="btn btn-secondary" id="id_submitbutton" type="submit" name="submit"
                    value="<?php
                        print_string('save', 'offlinequiz'); ?>" />
            </fieldset>
        </div>
    </div>
  </fieldset>
</form>
<?php

$PAGE->requires->js_init_call('M.mod_offlinequiz.init_comment_popup', null, false, offlinequiz_get_js_module());

// End of the page.
echo $OUTPUT->footer();
