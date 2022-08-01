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

require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');

$tab = optional_param('tab', '', PARAM_ALPHA);
$id = required_param('id', PARAM_INT);

list($offlinequiz, $course, $cm) = get_course_objects($id, null);

require_login($course->id, true, $cm);
$context = context_module::instance($cm->id);

$newurl = new moodle_url('/mod/offlinequiz/view.php', ['id' => $id]);

// We redirect students to info.
if (!has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    redirect($newurl);
}




$tabslist = offlinequiz_get_tabs_object($offlinequiz, $cm);
if ($tab == 'tabofflinequizcontent') {
    $sql = "SELECT count(*)
              FROM {offlinequiz_groups} og
         LEFT JOIN {offlinequiz_group_questions} ogq ON ogq.offlinegroupid = og.id
             WHERE ogq.id IS NULL
               AND og.offlinequizid = :id";
    $hasmissinggroupquestions = $DB->count_records_sql($sql, ['id' => $offlinequiz->id]);
    $hasdocumentscreated = $offlinequiz->docscreated;
    if($hasmissinggroupquestions) {
        $newurl = $tabslist['tabeditgroupquestions']['url'];
    } else if (!$hasdocumentscreated) {
        $newurl = $tabslist['tabpreview']['url'];
    } else {
        $newurl = $tabslist['tabdownloadquizforms']['url'];
    }
} else if ($tab == 'tabresults') {
    $hasresults = $DB->record_exists('offlinequiz_results', ['offlinequizid' => $offlinequiz->id]);
    $needscorrections = $DB->record_exists('offlinequiz_scanned_pages', ['offlinequizid' => $offlinequiz->id, 'status' => 'error']);
    if ($needscorrections) {
        $newurl = $tabslist['tabofflinequizupload']['url'];
    } else if ($hasresults) {
        $newurl = $tabslist['tabresultsoverview']['url'];
    } else {
        $newurl = $tabslist['tabofflinequizupload']['url'];
    }
} else if ($tab == 'tabattendances') {
    $existparticipantslists = $DB->record_exists('offlinequiz_p_lists', ['offlinequizid' => $offlinequiz->id]);
    $sql = "SELECT count(*)
              FROM {offlinequiz_p_lists} opl
         LEFT JOIN {offlinequiz_participants} op ON op.listid = opl.id
              WHERE op.id IS NULL
                AND opl. offlinequizid = :id";
    $existslistnoparticipants = $DB->count_records_sql($sql, ['id' => $offlinequiz->id]);
    $sql = "SELECT count(*)
              FROM {offlinequiz_p_lists} opl
              JOIN {offlinequiz_scanned_p_pages} ospp ON opl.id = ospp.listnumber
             WHERE opl.offlinequizid = :id
               AND ospp.status = 'error'";
    $needscorrection = $DB->count_records_sql($sql, ['id' => $offlinequiz->id]);

    $sql = "SELECT count(*)
              FROM {offlinequiz_p_lists} opl
              JOIN {offlinequiz_scanned_p_pages} ospp ON opl.id = ospp.listnumber
             WHERE opl.offlinequizid = :id
               AND ospp.status = 'ok'";
    $hasresults = $DB->count_records_sql($sql, ['id' => $offlinequiz->id]);
    if(!$existparticipantslists) {
        $newurl = $tabslist['tabparticipantlists']['url'];
    } else if ($existslistnoparticipants) {
        $newurl = $tabslist['tabeditparticipants']['url'];
    } else if ($needscorrection) {
        $newurl = $tabslist['tabparticipantsupload']['url'];
    } else if ($hasresults) {
        $newurl = $tabslist['tabattendancesoverview']['url'];
    } else {
        $newurl = $tabslist['tabdownloadparticipantsforms']['url'];
    }
}
redirect($newurl);
