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

define('STATUS_OPEN', 'open');
define('STATUS_NEXT', 'nextitem');
define('STATUS_DONE', 'done');
define('STATUS_NO_ACTION', 'noaction');

if ($id) {
    if (!$cm = get_coursemodule_from_id('offlinequiz', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else {
    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $q))) {
        throw new \moodle_exception('invalidofflinequizid', 'offlinequiz');
    }
    if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
        throw new \moodle_exception('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
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
// Start getting Data
$status = [];
$sql = "SELECT og.id, og.groupnumber, count(ogq.id) questions, og.sumgrades
          FROM {offlinequiz_groups} og
     LEFT JOIN {offlinequiz_group_questions} ogq ON og.id = ogq.offlinegroupid
         WHERE og.offlinequizid = :offlinequizid
      GROUP BY og.groupnumber, og.id, og.sumgrades
        HAVING og.groupnumber - 1 < :numgroups
      ORDER BY og.groupnumber";
$status['groups'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id, 'numgroups' => $offlinequiz->numgroups]);
$status['groupswithoutquestions'] = [];
foreach ($status['groups'] as $group) {
    if (!$group->questions) {
        $status['groupswithoutquestions'][$group->groupnumber] = true;
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
$queues = $DB->get_records('offlinequiz_queue', ['offlinequizid' => $offlinequiz->id]);
$status['pagesinprocessing'] = 0;
foreach ($queues as $queue) {
    if ($queue->status == 'new' || $queue->status == 'processing') {
        $status['pagesinprocessing'] += $DB->count_records('offlinequiz_queue_data', ['queueid' => $queue->id]);
    }
}
$status['docsuploaded'] = $DB->record_exists('offlinequiz_scanned_pages', ['offlinequizid' => $offlinequiz->id]);
$sql = "SELECT *
FROM {offlinequiz_scanned_pages}
WHERE offlinequizid = :offlinequizid
AND (status = 'error'
    OR status = 'suspended'
    OR error = 'missingpages')";
$status['correctionerrors'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
$status['resultscount'] = $DB->count_records('offlinequiz_results', ['offlinequizid' => $offlinequiz->id, 'status' => 'complete']);

$sql = "SELECT opl.*,
                    (SELECT count(*)
                    FROM {offlinequiz_participants} op
                    WHERE op.listid = opl.id) participants
          FROM {offlinequiz_p_lists} opl
          WHERE opl.offlinequizid = :offlinequizid
          ORDER BY listnumber";
$status['attendancelists'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
$status['missingresults'] = $DB->get_records_sql_menu($sql, ['courseid' => $offlinequiz->course, 'offlinequizid' => $offlinequiz->id]);
$sql = "SELECT op.userid
          FROM {offlinequiz_p_lists} opl
          JOIN {offlinequiz_participants} op ON opl.id = op.listid
         WHERE opl.offlinequizid = :offlinequizid";
$status['studentsonalist'] = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
$status['attendancedocscreated'] = !($DB->record_exists('offlinequiz_p_lists', ['offlinequizid' =>$offlinequiz->id, 'filename' => '']));
$status['attendanceuploads'] = $DB->count_records('offlinequiz_scanned_p_pages', ['offlinequizid' => $offlinequiz->id]);
$sql = "SELECT count(*)
          FROM {offlinequiz_p_lists} opl
          JOIN {offlinequiz_participants} op ON op.listid = opl.id 
     LEFT JOIN {offlinequiz_p_choices} opc ON opc.userid = op.userid
         WHERE opl.offlinequizid = :offlinequizid AND opc.userid is null";
$status['missingattendanceresults'] = $DB->count_records_sql($sql,['offlinequizid' => $offlinequiz->id]);
$status['attendanceresultdocsbroken'] = $DB->count_records('offlinequiz_scanned_p_pages',['offlinequizid' => $offlinequiz->id, 'status' => 'error']);
$sql = "SELECT DISTINCT op.userid
          FROM {offlinequiz_participants} op
          JOIN {offlinequiz_p_lists} opl on op.listid = opl.id
     LEFT JOIN {offlinequiz_results} oqr on oqr.userid = op.userid and oqr.offlinequizid = opl.offlinequizid 
         WHERE opl.offlinequizid = :offlinequizid 
           AND oqr.id is not null
           AND op.checked = 1";
$status['attwithresults'] = $DB->get_records_sql($sql,['offlinequizid' => $offlinequiz->id]);
$sql = "SELECT DISTINCT op.userid
          FROM {offlinequiz_participants} op
          JOIN {offlinequiz_p_lists} opl on op.listid = opl.id
     LEFT JOIN {offlinequiz_results} oqr on oqr.userid = op.userid and oqr.offlinequizid = opl.offlinequizid 
         WHERE opl.offlinequizid = :offlinequizid 
           AND oqr.id is null
           AND op.checked = 1";
$status['attwithoutresults'] = $DB->get_records_sql($sql,['offlinequizid' => $offlinequiz->id]);;
$sql = "SELECT DISTINCT op.userid
          FROM {offlinequiz_participants} op
          JOIN {offlinequiz_p_lists} opl on op.listid = opl.id
     LEFT JOIN {offlinequiz_results} oqr on oqr.userid = op.userid and oqr.offlinequizid = opl.offlinequizid 
         WHERE opl.offlinequizid = :offlinequizid 
           AND oqr.id is not null
           AND op.checked = 0";
$status['noattwithresults'] = $DB->get_records_sql($sql,['offlinequizid' => $offlinequiz->id]);;
$sql = "SELECT DISTINCT op.userid
          FROM {offlinequiz_participants} op
          JOIN {offlinequiz_p_lists} opl on op.listid = opl.id
     LEFT JOIN {offlinequiz_results} oqr on oqr.userid = op.userid and oqr.offlinequizid = opl.offlinequizid 
         WHERE opl.offlinequizid = :offlinequizid 
           AND oqr.id is null
           AND op.checked = 0";
$status['noattwithoutresults'] = $DB->get_records_sql($sql,['offlinequizid' => $offlinequiz->id]);;

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

// Begin edit Question
$editquestion = [];
$editquestion['collapsible'] = true;
$editquestion['unique'] = 'editquestion';
$editquestiondata = [];
$editquestiondata['groups'] = [];
foreach ($status['groups'] as $group) {
    $groupobject = [];
    $url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id, 'groupnumber' => $group->groupnumber]);
    $groupobject['link'] = $url->out(false);
    $groupobject['groupnumber'] = $groupnames[$group->groupnumber];
    $groupobject['questioncount'] = $group->questions;
    $editquestiondata['groups'][] = $groupobject;
}
$editquestion['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_editquestion', $editquestiondata);
if (!count($status['groupswithoutquestions'])) {
    $editquestion['status'] = STATUS_DONE;
} else {
    $editquestion['status'] = STATUS_NEXT;
}
$editquestion[$editquestion['status']] = true;
if ($editquestion['status'] == STATUS_DONE) {
    $editquestion['collapsestatus'] = 'collapsed';
} else {
    $editquestion['collapsestatus'] = STATUS_NEXT;
}
$url = new moodle_url('/mod/offlinequiz/edit.php', ['mode' => 'edit', 'cmid' => $id]);
$editquestion['link'] = $url->out(false);
$editquestion['text'] = get_string('editquestions', 'offlinequiz');

$preview = [];
$preview['collapsible'] = false;
if ($status['docscreated']) {
    $preview['status'] = STATUS_DONE;
} else if (!count($status['groupswithoutquestions'])) {
    $preview['status'] = STATUS_NEXT;
} else {
    $preview['status'] = STATUS_OPEN;
}
$preview[$preview['status']] = true;

$url = new moodle_url('/mod/offlinequiz/navigate.php', ['id' => $id, 'tab' => 'tabforms']);

$preview['link'] = $url->out(false);
$preview['text'] = get_string('forms', 'offlinequiz');

$preparationsteps[] = $editquestion;
$preparationsteps[] = $preview;
$templatedata['preparationsteps'] = $preparationsteps;

// Start evaluationsteps.
$evaluationsteps = [];

$upload = [];
$upload['collapsible'] = true;
$upload['unique'] = 'upload';
$uploaddata = [];
$uploaddata['pagesinprocessing'] = $status['pagesinprocessing'];
$uploaddata['resultsavailable'] = $status['resultscount'];
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'correct', 'q' => $offlinequiz->id]);
$uploaddata['correcturl'] = $url->out(false);
$upload['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_upload', $uploaddata);

if (!$status['docscreated']) {
    $upload['status'] = STATUS_OPEN;
} else if (!$status['resultscount'] && !$status['docsuploaded']) {
    $upload['status'] = STATUS_NEXT;
} else {
    $upload['status'] = STATUS_DONE;
}
if ($upload['status'] == STATUS_DONE) {
    $upload['collapsestatus'] = 'collapsed';
} else {
    $upload['collapsestatus'] = STATUS_NEXT;
}

$upload[$upload['status']] = true;

$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'rimport', 'q' => $offlinequiz->id]);

$upload['link'] = $url->out(false);
$upload['text'] = get_string('upload', 'offlinequiz');

$overview = [];
$resultsublistcontext = [];
$resultsublistcontext['resultentry'] = [];
$url = new moodle_url('/mod/offlinequiz/report.php', ['q' => $offlinequiz->id, 'mode' => 'correct']);
$resultsublistcontext['resultentry'][] = ['langstring' => get_string('correctheader', 'offlinequiz'),
                           'number'     => count($status['correctionerrors']),
                           'link'       => $url->out(false)
];
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'overview', 'q' => $offlinequiz->id]);
$resultsublistcontext['resultentry'][] = ['langstring' => get_string('evaluated', 'offlinequiz'),
    'number'     => $status['resultscount'],
    'link'       => $url->out(false)
];
$overview['collapsible'] = true;
$overview['unique'] = 'overview';
$overview['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_resultsublist', $resultsublistcontext);
if ($status['correctionerrors']) {
    $overview['status'] = STATUS_NEXT;
} else {
    $overview['status'] = $status['resultscount'] ? STATUS_DONE : STATUS_OPEN;
}

$overview[$overview['status']] = true;

$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'overview', 'q' => $offlinequiz->id]);

$overview['link'] = $url->out(false);
$overview['text'] = get_string('results', 'offlinequiz');

$evaluationsteps[] = $upload;
$evaluationsteps[] = $overview;
$templatedata['evaluationsteps'] = $evaluationsteps;

$statisticsdata = [];
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'q' => $offlinequiz->id]);
$statisticsdata['overviewlink'] = $url->out(false);
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'id' => $id, 'statmode' => 'questionstats']);
$statisticsdata['questionanalysislink'] = $url->out(false);
$url = new moodle_url('/mod/offlinequiz/report.php', ['mode' => 'statistics', 'id' => $id, 'statmode' => 'questionandanswerstats']);
$statisticsdata['questionandansweranalysislink'] = $url->out(false);
$templatedata['statistics'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_statistics', $statisticsdata);



$editlists = [];
$hasattlistwithoutstudents = false;
if($status['attendancelists']) {
    $editlists['collapsible'] = true;
    $editlists['unique'] = 'editlists';
    $editlistsdata = [];
    $editlistsdata['attendancelists'] = [];
    foreach ($status['attendancelists'] as $list) {
        $listobject = [];
        $url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'editparticipants', 'action' => 'edit', 'q' => $offlinequiz->id, 'listid' => $list->id]);
        $listobject['link'] = $url->out(false);
        $listobject['name'] = $list->name;
        if(!($listobject['participants'] = $list->participants)) {
            $hasattlistwithoutstudents = true;
        }

        
        $editlistsdata['attendancelists'][] = $listobject;
        $editlists['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_attendancelists', $editlistsdata);
    }
} else {
    $editlists['collapsible'] = false;
}

if($status['attendancelists'] && !$hasattlistwithoutstudents) {
    $editlists['collapsestatus'] = STATUS_DONE;
    $editlists['status'] = STATUS_DONE;
} else {
    $editlists['collapsestatus'] = STATUS_NEXT;
    $editlists['status'] = STATUS_NEXT;
}



$editlists[$editlists['status']] = true;

$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'editlists', 'q' => $offlinequiz->id]);

$editlists['link'] = $url->out(false);
$editlists['text'] = get_string('tabparticipantlists', 'offlinequiz');


$downloadattendance = [];
$downloadattendance['collapsible'] = false;
if($editlists['status'] == STATUS_DONE && $status['attendancedocscreated']) {
    $downloadattendance['status'] = STATUS_DONE;
} elseif ($editlists['status'] != STATUS_DONE) {
    $downloadattendance['status'] = STATUS_OPEN;
} else {
    $downloadattendance['status'] = STATUS_NEXT;
}

$downloadattendance[$downloadattendance['status']] = true;

$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'createpdfs', 'q' => $offlinequiz->id]);

$downloadattendance['link'] = $url->out(false);
$downloadattendance['text'] = get_string('tabdownloadparticipantsforms', 'offlinequiz');

$uploadattendance = [];
$uploadattendance['collapsible'] = false;
if($downloadattendance['status'] != STATUS_DONE) {
    $uploadattendance['status'] = STATUS_OPEN;
} elseif ($status['missingattendanceresults']) {
    $uploadattendance['status'] = STATUS_NEXT;
} else {
    $uploadattendance['status'] = STATUS_DONE;
}
$uploadattendance[$uploadattendance['status']] = true;

$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'upload', 'q' => $offlinequiz->id]);

$uploadattendance['link'] = $url->out(false);
$uploadattendance['text'] = get_string('upload', 'offlinequiz');

$attendanceoverview = [];
$attendanceoverview['collapsible'] = true;
$attendanceoverview['unique'] = 'attendanceoverview';
$attendanceoverviewcontext = [
    'correctionnecessary' => $status['attendanceresultdocsbroken'],
    'attwithresults' => count($status['attwithresults']),
    'attwithoutresults' => count($status['attwithoutresults']),
    'noattwithresults' => count($status['noattwithresults']),
    'noattwithoutresults' => count($status['noattwithoutresults']),
];
$attendanceoverview['expandedcontent'] = $OUTPUT->render_from_template('mod_offlinequiz/teacher_view_attendancesummary', $attendanceoverviewcontext);
if($status['attendanceresultdocsbroken']) {
    $attendanceoverview['status'] = STATUS_NEXT;
} elseif (!$uploadattendance['status'] == STATUS_DONE) {
    $attendanceoverview['status'] = STATUS_DONE;
} else {
    $attendanceoverview['status'] = STATUS_OPEN;
}

$attendanceoverview[$attendanceoverview['status']] = true;

$url = new moodle_url('/mod/offlinequiz/participants.php', ['mode' => 'attendances', 'q' => $offlinequiz->id]);

$attendanceoverview['link'] = $url->out(false);
$attendanceoverview['text'] = get_string('attendanceoverview', 'offlinequiz');

$participantslistpreparationsteps = [];
$participantslistpreparationsteps[] = $editlists;
$participantslistpreparationsteps[] = $downloadattendance;
$participantslistevaluationsteps = [];
$participantslistevaluationsteps[] = $uploadattendance;
$participantslistevaluationsteps[] = $attendanceoverview;
$templatedata['participantslistpreparationsteps'] = $participantslistpreparationsteps;
$templatedata['participantslistevaluationsteps'] = $participantslistevaluationsteps;
$templatedata['displayparticipantssteps'] = $offlinequiz->participantsusage;

// Print the page header.
$PAGE->set_url('/mod/offlinequiz/view.php', array('id' => $cm->id));
$PAGE->set_title($offlinequiz->name);
$PAGE->set_heading($course->shortname);
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagelayout('report');
// Output starts here.


if (has_capability('mod/offlinequiz:manage', $context)) {
    echo $OUTPUT->header();
    // Print the page header.
    if ($edit != -1 and $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }
    echo $OUTPUT->render_from_template('mod_offlinequiz/teacher_view', $templatedata);
} else {
    $select = "SELECT *
         FROM {offlinequiz_results} qa
        WHERE qa.offlinequizid = :offlinequizid
          AND qa.userid = :userid
          AND qa.status = 'complete'";
    $result = $DB->get_record_sql($select, array('offlinequizid' => $offlinequiz->id, 'userid' => $USER->id));
    if ($result && offlinequiz_results_open($offlinequiz) && has_capability('mod/offlinequiz:attempt', $context)) {
        $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
        if ($result->timefinish && ($options->attempt == question_display_options::VISIBLE ||
              $options->marks >= question_display_options::MAX_ONLY ||
              $options->sheetfeedback == question_display_options::VISIBLE ||
              $options->gradedsheetfeedback == question_display_options::VISIBLE
              )) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/review.php',
                    array('q' => $offlinequiz->id, 'resultid' => $result->id));
            redirect($url);
            die();
        }
    } else if (has_capability('mod/offlinequiz:attempt', $context) && !empty($offlinequiz->time) && $offlinequiz->time < time()) {
        echo $OUTPUT->header();
        echo '<div class="offlinequizinfo">' . get_string('nogradesseelater', 'offlinequiz', fullname($USER)).'</div>';
    } else if ($offlinequiz->showtutorial) {
        $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/tutorial.php',
            array('id' => $cm->id));
        redirect($url);
    } else {
        echo $OUTPUT->header();
    }

}

// Finish the page.
echo $OUTPUT->footer();
