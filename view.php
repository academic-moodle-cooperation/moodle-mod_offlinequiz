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
 * Displays the info page of offline quizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir  . '/gradelib.php');
require_once($CFG->libdir  . '/completionlib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or.
$q  = optional_param('q', 0, PARAM_INT);  // Offlinequiz instance ID.
$edit = optional_param('edit', -1, PARAM_BOOL);

if ($id) {
    if (!$cm = get_coursemodule_from_id('offlinequiz', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
} else {
    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $q))) {
        print_error('invalidofflinequizid', 'offlinequiz');
    }
    if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

// Print the page header.
$PAGE->set_url('/mod/offlinequiz/view.php', array('id' => $cm->id));
$PAGE->set_title($offlinequiz->name);
$PAGE->set_heading($course->shortname);
$PAGE->set_pagelayout('report');

// Output starts here.
echo $OUTPUT->header();

// Print the page header.
if ($edit != -1 and $PAGE->user_allowed_editing()) {
    $USER->editing = $edit;
}

// Print the tabs to switch mode.
if (has_capability('mod/offlinequiz:viewreports', $context)) {
    $currenttab = 'info';
    include_once('tabs.php');
}

echo $OUTPUT->heading(format_string($offlinequiz->name));

// If not in all group questions have been output a link to edit.php.
$emptygroups = offlinequiz_get_empty_groups($offlinequiz);

if (has_capability('mod/offlinequiz:manage', $context)) {
    echo '<div class="box generalbox linkbox">';
    if (count($emptygroups) > 0) {
        $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/edit.php',
                array('cmid' => $cm->id, 'groupnumber' => $emptygroups[0], 'noquestions' => 1));
        echo html_writer::link($url, get_string('emptygroups', 'offlinequiz'));
    } else if ($offlinequiz->docscreated) {
        echo get_string('pdfscreated', 'offlinequiz');
    } else {
        echo get_string('nopdfscreated', 'offlinequiz');
    }
    echo '</div>';
}

// Log this request.
$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
$event = \mod_offlinequiz\event\course_module_viewed::create($params);
$event->add_record_snapshot('offlinequiz', $offlinequiz);
$event->trigger();

if (!empty($offlinequiz->time)) {
    echo '<div class="offlinequizinfo">'.userdate($offlinequiz->time).'</div>';
}

if (has_capability('mod/offlinequiz:view', $context)) {
    // Print offlinequiz description.
    if (trim(strip_tags($offlinequiz->intro))) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        echo $OUTPUT->box(format_text($offlinequiz->intro, $offlinequiz->introformat, $formatoptions),
                'generalbox', 'intro');
    }
}

function find_pdf_file($contextid, $listfilename) {
    $fs = get_file_storage();
    if ($pdffile = $fs->get_file($contextid, 'mod_offlinequiz', 'participants', 0, '/', $listfilename)) {
        return $pdffile;
    } else {
        return $fs->get_file($contextid, 'mod_offlinequiz', 'pdfs', 0, '/', $listfilename);
    }
}

if (has_capability('mod/offlinequiz:viewreports', $context)) {

    if (!$students = get_enrolled_users($coursecontext, 'mod/offlinequiz:attempt')) {
        $resultcount = false;
    } else {
        $params = array();
        $params['offlinequizid'] = $offlinequiz->id;

        $select = "SELECT COUNT(DISTINCT(u.id)) as counter
                     FROM {user} u
                LEFT JOIN {offlinequiz_results} qa ON u.id = qa.userid AND qa.offlinequizid = :offlinequizid
                    WHERE qa.userid IS NOT NULL
                      AND qa.status = 'complete'";
        $resultcount  = $DB->count_records_sql($select, $params);
    }
    $select = "SELECT COUNT(id)
                 FROM {offlinequiz_scanned_pages}
                WHERE offlinequizid = :offlinequizid
                  AND status = 'error' ";
    $errorcount  = $DB->count_records_sql($select, array('offlinequizid' => $offlinequiz->id));

    echo '<div class="box generalbox linkbox">';
    if (!empty($resultcount)) {
        $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php',
                array('mode' => 'overview', 'q' => $offlinequiz->id));
        echo html_writer::link($url, get_string('numattempts', 'offlinequiz', $resultcount));
    } else {
        if ($offlinequiz->docscreated) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php',
                    array('mode' => 'rimport', 'q' => $offlinequiz->id));
            echo html_writer::link($url, get_string('noattempts', 'offlinequiz'));
        }
    }
    echo '<br />&nbsp;<br />';

    if (!empty($errorcount)) {
        $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php',
                array('mode' => 'rimport', 'q' => $offlinequiz->id));
        echo html_writer::link($url, get_string('numattemptsverify', 'offlinequiz', $errorcount));
    }
    echo '</div>';

    // Redmine 1971: New info about lists of participants.
    if ($plists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id))) {
        $firstlist = array_shift($plists);
        array_unshift($plists, $firstlist);
        $fs = get_file_storage();

        // Only print info if the PDF files have been created.
        if (property_exists($firstlist, 'filename') && $firstlist->filename &&
            $pdffile = find_pdf_file($context->id, $firstlist->filename)) {

            echo '<br/><div class="box generalbox linkbox">';
            $listids = array();
            foreach ($plists as $listid => $plist) {
                $membercount = $DB->count_records('offlinequiz_participants', array('listid' => $plist->id));
                $listurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                        array('q' => $offlinequiz->id, 'mode' => 'attendances', 'listid' => $plist->id));
                echo html_writer::span(get_string('membersinplist', 'offlinequiz',
                        array('count' => $membercount, 'name' => $plist->name, 'url' => $listurl->out())));
                echo '<br />';
                $listids[] = $plist->id;
            }
            // Only print additional info if there are some scanned participant lists that have be uploaded.
            if ($ppages = $DB->get_records('offlinequiz_scanned_p_pages', array('offlinequizid' => $offlinequiz->id))) {
                // First get all participants on the lists.
                list($lsql, $lparams) = $DB->get_in_or_equal($listids);
                $psql = "SELECT *
                          FROM {offlinequiz_participants}
                         WHERE listid $lsql";
                $participants = $DB->get_records_sql($psql, $lparams);

                $results = $DB->get_records_select('offlinequiz_results',
                        'offlinequizid = :offlinequizid AND status = :status',
                        array('offlinequizid' => $offlinequiz->id,
                                'status' => 'complete'
                        ), '', 'userid');

                $checkedwithresult = 0;
                $checkedwithoutresult = 0;
                $uncheckedwithresult = 0;
                $uncheckedwithoutresult = 0;
                foreach ($participants as $participant) {
                    if ($participant->checked) {
                        if (array_key_exists($participant->userid, $results)) {
                            $checkedwithresult++;
                        } else {
                            $checkedwithoutresult++;
                        }
                    } else {
                        if (array_key_exists($participant->userid, $results)) {
                            $uncheckedwithresult++;
                        } else {
                            $uncheckedwithoutresult++;
                        }
                    }
                }

                $checkedwithoutresulturl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('q' => $offlinequiz->id, 'mode' => 'attendances', 'checkoption' => 1));
                $uncheckedwithresulturl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('q' => $offlinequiz->id, 'mode' => 'attendances', 'checkoption' => 2));

                echo '<br />';
                echo html_writer::span(get_string('partcheckedwithresult', 'offlinequiz', $checkedwithresult));
                echo '<br />';
                echo html_writer::span(get_string('partcheckedwithoutresult', 'offlinequiz',
                        array('count' => $checkedwithoutresult, 'url' => $checkedwithoutresulturl->out())));
                echo '<br />';
                echo html_writer::span(get_string('partuncheckedwithresult', 'offlinequiz',
                        array('count' => $uncheckedwithresult, 'url' => $uncheckedwithresulturl->out())));
                echo '<br />';
                echo html_writer::span(get_string('partuncheckedwithoutresult', 'offlinequiz', $uncheckedwithoutresult));

                foreach ($plists as $listid => $plist) {
                    $membercount = $DB->count_records('offlinequiz_participants', array('listid' => $plist->id));
                }
            }
            echo '</div>';
        }
    }

} else if (has_capability('mod/offlinequiz:attempt', $context)) {
    $select = "SELECT *
                 FROM {offlinequiz_results} qa
                WHERE qa.offlinequizid = :offlinequizid
                  AND qa.userid = :userid
                  AND qa.status = 'complete'";

    if ($result = $DB->get_record_sql($select, array('offlinequizid' => $offlinequiz->id, 'userid' => $USER->id))
            and offlinequiz_results_open($offlinequiz)) {
        $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
        if ($result->timefinish and ($options->attempt == question_display_options::VISIBLE or
              $options->marks >= question_display_options::MAX_ONLY or
              $options->sheetfeedback == question_display_options::VISIBLE or
              $options->gradedsheetfeedback == question_display_options::VISIBLE
              )) {

            echo '<div class="offlinequizinfo">';
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/review.php',
                    array('q' => $offlinequiz->id, 'resultid' => $result->id));
            echo $OUTPUT->single_button($url, get_string('viewresults', 'offlinequiz'));
            echo '</div>';
        }
    } else {
        if (!empty($offlinequiz->time) and $offlinequiz->time < time()) {
            echo '<div class="offlinequizinfo">' . get_string('nogradesseelater', 'offlinequiz', fullname($USER)).'</div>';
        } else if ($offlinequiz->showtutorial) {
            // JZ: UNIVIS-15097.
            echo '<br/><div class="offlinequizinfo">';
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/tutorial/index.php',
                    array('id' => $cm->id));
            echo $OUTPUT->single_button($url, get_string('starttutorial', 'offlinequiz'));
            echo '</div>';
        }
    }
}

// Finish the page.
echo $OUTPUT->footer();
