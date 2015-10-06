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
 * This script lists all the instances of offlinequiz in a particular course
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/mod/offlinequiz/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

// Log this request.
$params = array(
        'context' => $coursecontext
);
$event = \mod_offlinequiz\event\course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strofflinequizzes = get_string("modulenameplural", "offlinequiz");
$streditquestions = '';
$editqcontexts = new question_edit_contexts($coursecontext);
if ($editqcontexts->have_one_edit_tab_cap('questions')) {
    $streditquestions =
            "<form target=\"_parent\" method=\"get\" action=\"$CFG->wwwroot/question/edit.php\">
               <div>
               <input type=\"hidden\" name=\"courseid\" value=\"$course->id\" />
               <input type=\"submit\" value=\"".get_string("editquestions", "offlinequiz")."\" />
               </div>
             </form>";
}

$PAGE->navbar->add($strofflinequizzes);
$PAGE->set_title($strofflinequizzes);
$PAGE->set_button($streditquestions);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Get all the appropriate data.
if (!$offlinequizzes = get_all_instances_in_course('offlinequiz', $course)) {
    notice(get_string('thereareno', 'moodle', $strofflinequizzes), "../../course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    die;
}

$isteacher = has_capability('mod/offlinequiz:viewreports', $coursecontext);

// Check if we need the closing date header.
$showclosingheader = false;
$showfeedback = false;
$therearesome = false;
foreach ($offlinequizzes as $offlinequiz) {
    if ($offlinequiz->timeclose != 0 ) {
        $showclosingheader = true;
    }
    if ($offlinequiz->visible || $isteacher) {
        $therearesome = true;
    }
}

if (!$therearesome) {
    notice(get_string('thereareno', 'moodle', $strofflinequizzes), "../../course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    die;
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

if ($showclosingheader) {
    array_push($headings, get_string('offlinequizcloses', 'offlinequiz'));
    array_push($align, 'left');
}

array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
array_unshift($align, 'center');

$showing = '';

if (has_capability('mod/offlinequiz:viewreports', $coursecontext)) {
    array_push($headings, get_string('results', 'offlinequiz'));
    array_push($align, 'left');
    $showing = 'stats';

} else if (has_capability('mod/offlinequiz:attempt', $coursecontext)) {
    array_push($headings, get_string('grade', 'offlinequiz'));
    array_push($align, 'left');
    if ($showfeedback) {
        array_push($headings, get_string('feedback', 'offlinequiz'));
        array_push($align, 'left');
    }
    $showing = 'grades';
}

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
foreach ($offlinequizzes as $offlinequiz) {
    $cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
    $context = context_module::instance($cm->id);
    $data = array();

    $grades = array();
    if ($showing == 'grades') {
        if ($gradearray = offlinequiz_get_user_grades($offlinequiz, $USER->id)) {
            $grades[$offlinequiz->id] = $gradearray[$USER->id]['rawgrade'];
        } else {
            $grades[$offlinequiz->id] = null;
        }
    }

    // Section number if necessary.
    $strsection = '';
    if ($offlinequiz->section != $currentsection) {
        if ($offlinequiz->section) {
            $strsection = $offlinequiz->section;
            $strsection = get_section_name($course, $offlinequiz->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $offlinequiz->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$offlinequiz->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$offlinequiz->coursemodule\">" .
            format_string($offlinequiz->name, true) . '</a>';

    // Close date.
    if ($offlinequiz->timeclose) {
        $data[] = userdate($offlinequiz->timeclose);
    } else if ($showclosingheader) {
        $data[] = '';
    }

    if ($showing == 'stats') {
        // The $offlinequiz objects returned by get_all_instances_in_course have the necessary $cm
        // fields set to make the following call work.
        $data[] = offlinequiz_attempt_summary_link_to_reports($offlinequiz, $cm, $context);

    } else if ($showing == 'grades') {
        // Grade and feedback.
        list($someoptions, $alloptions) = offlinequiz_get_combined_reviewoptions($offlinequiz);

        $grade = '';
        $feedback = '';
        if ($offlinequiz->grade && array_key_exists($offlinequiz->id, $grades)) {
            if ($alloptions->marks >= question_display_options::MARK_AND_MAX) {
                $a = new stdClass();
                $a->grade = offlinequiz_format_grade($offlinequiz, $grades[$offlinequiz->id]);
                $a->maxgrade = offlinequiz_format_grade($offlinequiz, $offlinequiz->grade);
                $grade = get_string('outofshort', 'offlinequiz', $a);
            }
        }
        $data[] = $grade;
        if ($showfeedback) {
            $data[] = $feedback;
        }
    }

    $table->data[] = $data;
} // End of loop over offlinequiz instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
