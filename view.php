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
// Log this request.
$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
$event = \mod_offlinequiz\event\course_module_viewed::create($params);
$event->add_record_snapshot('offlinequiz', $offlinequiz);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

//Start getting Data
$status = [];
$sql = "SELECT og.id, og.groupnumber, count(ogq.id) questions, og.sumgrades
          FROM {offlinequiz_groups} og
     LEFT JOIN {offlinequiz_group_questions} ogq ON og.id = ogq.offlinegroupid
         WHERE og.offlinequizid = :offlinequizid
      GROUP BY og.groupnumber, og.id
      ORDER BY og.groupnumber";
$status['groups'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id ]);
$status['groupswithoutquestions'] = [];
foreach($status['groups'] as $group) {
    if(!$group->questions) {
        $status['groupswithoutquestions'][$group->groupnumber] = true ;
    }
}
$sql = "SELECT u.id
          FROM {user} u
          JOIN {role_assignments} ra ON ra.userid = u.id
          JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
          JOIN {context} c ON c.id = ra.contextid AND contextlevel = 50
     LEFT JOIN {offlinequiz_results} oqr ON oqr.userid = u.id AND status = 'OK' AND oqr.offlinequizid = :offlinequizid
          WHERE c.instanceid = :courseid
          AND oqr.status is null";
$status['missingresults'] = $DB->get_records_sql_menu($sql, ['courseid' => $offlinequiz->course, 'offlinequizid' => $offlinequiz->id]);
$status['docsuploaded'] = $DB->record_exists('offlinequiz_scanned_pages', ['offlinequizid' => $offlinequiz->id]);
$status['correctionerrors'] = $DB->get_records('offlinequiz_scanned_pages', ['offlinequizid' => $offlinequiz->id, 'status' => 'error']);
$status['resultsexist'] = $DB->record_exists('offlinequiz_results', ['offlinequizid' => $offlinequiz->id]);
$sql = "SELECT opl.*,
                    (SELECT count(*)
                    FROM {offlinequiz_participants} op
                    WHERE op.listid = opl.id) participants
          FROM {offlinequiz_p_lists} opl
          WHERE opl.offlinequizid = :offlinequizid
          ORDER BY listnumber";
$status['attendancelists'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
$sql = "SELECT DISTINCT u.id
          FROM {user} u
          JOIN {role_assignments} ra ON ra.userid = u.id
          JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
          JOIN {context} c ON c.id = ra.contextid AND contextlevel = 50
     LEFT JOIN {offlinequiz_p_lists} opl ON opl.offlinequizid = :offlinequizid
     LEFT JOIN {offlinequiz_participants} op ON op.userid = u.id AND op.listid = opl.id
          WHERE c.instanceid = :courseid
          AND op.userid IS null";
$status['missingonattendancelist'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id, 'courseid' => $offlinequiz->course]);
$sql = "SELECT op.userid
          FROM {offlinequiz_p_lists} opl
          JOIN {offlinequiz_participants} op ON opl.id = op.listid
         WHERE opl.offlinequizid = :offlinequizid";
$status['studentsonalist'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
$status['attendanceuploads'] = $DB->count_records('offlinequiz_scanned_p_pages', ['offlinequizid' => $offlinequiz->id]);
$status['docscreated'] = $offlinequiz->docscreated;
$groupnames = [];
$groupnames[1] = 'A';
$groupnames[2] = 'B';
$groupnames[3] = 'C';
$groupnames[4] = 'D';
$groupnames[5] = 'E';
$groupnames[6] = 'F';
$groupnames[7] = 'G';
$groupnames[8] = 'H';


// Prepare teacher view.
$templatedata = [];
$preparationsteps = [];

//Begin edit Question
$editquestion = [];
$editquestion['collapsible'] = true;
$editquestion['unique'] = 'editquestion';
$editquestiondata = [];
$editquestiondata['groups'] = [];
foreach($status['groups'] as $group) {
    $groupobject = [];
    $url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id, 'groupnumber' => $group->groupnumber]);
    $groupobject['link'] = $url->out(false);
    $groupobject['groupnumber'] = $groupnames[$group->groupnumber];
    $groupobject['questioncount'] = $group->questions;
    $editquestiondata['groups'][] = $groupobject;
}
$editquestion['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_editquestion', $editquestiondata);
if($status['docscreated']) {
  $editquestion['status'] = 'done';
} else {
  $editquestion['status'] = 'nextitem';
}
if($editquestion['status'] == 'done') {
    $editquestion['collapsestatus'] = 'collapsed';
} else {
    $editquestion['collapsestatus'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id, 'gradetool' => 0]);
$editquestion['link'] = $url->out(false);
$editquestion['text'] = get_string('editquestions', 'offlinequiz');

//Begin edit grades
$editgrades = [];
$editgrades['collapsible'] = true;
$editgrades['unique'] = 'editgrades';
$editgradesdata = [];
$editgradesdata['groups'] = [];
foreach($status['groups'] as $group) {
    $groupobject = [];
    $url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id, 'groupnumber' => $group->groupnumber]);
    $groupobject['link'] = $url->out(false);
    $groupobject['groupnumber'] = $groupnames[$group->groupnumber];
    $groupobject['maxmark'] = $group->sumgrades;
    if($groupobject['maxmark'] == (int) $groupobject['maxmark']) {
        $groupobject['maxmark'] = (int) $groupobject['maxmark'];
    }
    $editgradesdata['groups'][] = $groupobject;
}
$editgrades['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_editgrades', $editgradesdata);
if($status['docscreated']) {
    $editgrades['status'] = 'done';
} else if ($status['groupswithoutquestions']) {
  $editgrades['status'] = 'open';
} else {
  $editgrades['status'] = 'nextitem';
}

if($editgrades['status'] == 'done') {
    $editgrades['collapsestatus'] = 'collapsed';
} else {
    $editgrades['collapsestatus'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id, 'gradetool' => 1]);

$editgrades['link'] = $url->out(false);
$editgrades['text'] = get_string('editgrades', 'offlinequiz');

$preview = [];
$preview['collapsible'] = false;

$preview['status'] = $editgrades['status'];
$url = new moodle_url('/mod/offlinequiz/createquiz.php', ['q' => $offlinequiz->id]);

$preview['link'] = $url->out(false);
$preview['text'] = get_string('preview', 'offlinequiz');

//Begin download documents.
$downloaddocuments = [];
$downloaddocuments['collapsible'] = false;
if($status['docsuploaded']) {
  $downloaddocuments['status'] = 'done';
} else if($status['docscreated']) {
    $downloaddocuments['status'] = 'nextitem';
} else {
    $downloaddocuments['status'] = $preview['status'];
} 

$url = new moodle_url('/mod/offlinequiz/createquiz.php', ['q' => $offlinequiz->id, 'mode' => 'createpdfs']);

$downloaddocuments['link'] = $url->out(false);
$downloaddocuments['text'] = get_string('createpdfs', 'offlinequiz');


$preparationsteps[] = $editquestion;
$preparationsteps[] = $editgrades;
$preparationsteps[] = $preview;
$preparationsteps[] = $downloaddocuments;
$templatedata['preparationsteps'] = $preparationsteps;


//Start evaluationsteps.
$evaluationsteps = [];

$upload = [];
$upload['collapsible'] = true;
$upload['unique'] = 'upload';
$uploaddata = [];
$uploaddata['userswithoutresult'] = count($status['missingresults']);
$uploaddata['correctionerrors'] = count($status['correctionerrors']);
$upload['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_upload', $uploaddata);

if(!$status['docscreated']) {
  $upload['status'] = 'open';
} else if(!$status['missingresults']) {
    $upload['status'] = 'done';
} else {
    $upload['status'] = 'nextitem';
}
if($upload['status'] == 'done') {
    $upload['collapsestatus'] = 'collapsed';
} else {
    $upload['collapsestatus'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'rimport', 'q' => $offlinequiz->id]);

$upload['link'] = $url->out(false);
$upload['text'] = get_string('upload', 'offlinequiz');


$overview = [];
$overview['collapsible'] = false;
if($status['resultsexist']) {
  $overview['status'] = 'nextitem';
} else {
  $overview['status'] = 'open';
}

$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'overview', 'q' => $offlinequiz->id]);

$overview['link'] = $url->out(false);
$overview['text'] = get_string('reportoverview', 'offlinequiz');

$regrade = [];
$regrade['collapsible'] = false;
$regrade['status'] = $overview['status'];

$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'regrade', 'q' => $offlinequiz->id]);

$regrade['link'] = $url->out(false);
$regrade['text'] = get_string('regrade', 'offlinequiz');

$statistics = [];
$statistics['collapsible'] = true;

$statistics['status'] = $overview['status'];
if($statistics['status'] == 'done') {
    $statistics['collapsestatus'] = 'collapsed';
} else {
    $statistics['collapsestatus'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'id' => $id]);
$statistics['link'] = $url->out(false);
$statistics['unique'] = 'statistics';
$statisticsdata = [];
$statisticsdata['overviewlink'] = $url->out(false);
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'id' => $id, 'statmode' => 'questionstats']);
$statisticsdata['questionanalysislink'] = $url->out(false);
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'id' => $id, 'statmode' => 'questionandanswerstats']);
$statisticsdata['questionandansweranalysislink'] = $url->out(false);
$statistics['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_statistics', $statisticsdata);

$statistics['text'] = get_string('statistics', 'offlinequiz');

$evaluationsteps[] = $upload;
$evaluationsteps[] = $overview;
$evaluationsteps[] = $regrade;
$evaluationsteps[] = $statistics;
$templatedata['evaluationsteps'] = $evaluationsteps;


$participantsliststeps = [];

$createlists = [];
$createlists['collapsible'] = false;
if(!$status['attendancelists']) {
    $createlists['status'] = 'nextitem';
} else {
    $createlists['status'] = 'done';
}
$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'editlists', 'q' => $offlinequiz->id]);

$createlists['link'] = $url->out(false);
$createlists['text'] = get_string('tabparticipantlists', 'offlinequiz');

$editlists = [];
$editlists['collapsible'] = true;
$editlistsdata = [];
$editlistsdata['attendancelists'] = [];
foreach($status['attendancelists'] as $list) {
    $listobject = [];
    $url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'editparticipants', 'action' => 'edit', 'q' => $offlinequiz->id, 'listid' => $list->id]);
    $listobject['link'] = $url->out(false);
    $listobject['name'] = $list->name;
    $listobject['participants'] = $list->participants;
    $editlistsdata['attendancelists'][] = $listobject;
}
$editlistsdata['notonattendancelist'] = count($status['missingonattendancelist']);
$editlists['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_attendancelists', $editlistsdata);

if($createlists['status'] != 'done') {
    $editlists['status'] = 'open';
} else if($createlists['status'] == 'done' && $status['missingonattendancelist']) {
    $editlists['status'] = 'nextitem';
} else {
    $editlists['status'] = 'done';
}
if($editlists['status'] == 'done') {
    $editlists['collapsestatus'] = 'collapsed';
} else {
    $editlists['collapsestatus'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'editparticipants', 'q' => $offlinequiz->id]);

$editlists['link'] = $url->out(false);
$editlists['text'] = get_string('tabeditparticipants', 'offlinequiz');


$downloadattendance = [];
$downloadattendance['collapsible'] = false;
if(!$status['studentsonalist']) {
    $downloadattendance['status'] = 'open';
} else if ($status['attendanceuploads']) {
    $downloadattendance['status'] = 'done';
} else {
    $downloadattendance['status'] = 'nextitem';
}
$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'createpdfs', 'q' => $offlinequiz->id]);

$downloadattendance['link'] = $url->out(false);
$downloadattendance['text'] = get_string('tabdownloadparticipantsforms', 'offlinequiz');

$uploadattendance = [];
$uploadattendance['collapsible'] = false;
if($status['attendanceuploads']) {
  $uploadattendance['status'] = 'done';
} else if($downloadattendance['status'] == 'done' || $downloadattendance['status'] == 'nextitem') {
  $uploadattendance['status'] = 'nextitem';
} else {
  $uploadattendance['status'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'createpdfs', 'q' => $offlinequiz->id]);

$uploadattendance['link'] = $url->out(false);
$uploadattendance['text'] = get_string('upload', 'offlinequiz');

$attendanceoverview = [];
$attendanceoverview['collapsible'] = false;
if($status['attendanceuploads']) {
    $attendanceoverview['status'] = 'nextitem';
} else {
  $attendanceoverview['status'] = 'open';
}
$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'attendances', 'q' => $offlinequiz->id]);

$attendanceoverview['link'] = $url->out(false);
$attendanceoverview['text'] = get_string('attendanceoverview', 'offlinequiz');

$participantsliststeps[] = $createlists;
$participantsliststeps[] = $editlists;
$participantsliststeps[] = $downloadattendance;
$participantsliststeps[] = $uploadattendance;
$participantsliststeps[] = $attendanceoverview;
$templatedata['participantsliststeps'] = $participantsliststeps;

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

if (has_capability('mod/offlinequiz:manage', $context)) {
    echo $OUTPUT->render_from_template('mod_offlinequiz/teacher_view', $templatedata);
} else if (has_capability('mod/offlinequiz:attempt', $context)) {
            $select = "SELECT *
                 FROM {offlinequiz_results} qa
                WHERE qa.offlinequizid = :offlinequizid
                  AND qa.userid = :userid
                  AND qa.status = 'complete'";
            $result = $DB->get_record_sql($select, array('offlinequizid' => $offlinequiz->id, 'userid' => $USER->id));
        if ($result && offlinequiz_results_open($offlinequiz)) {
        $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
        if ($result->timefinish && ($options->attempt == question_display_options::VISIBLE ||
              $options->marks >= question_display_options::MAX_ONLY ||
              $options->sheetfeedback == question_display_options::VISIBLE ||
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
