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

$tab = optional_param('tab', '', PARAM_ALPHAEXT);
$id = required_param('id', PARAM_INT);

list($offlinequiz, $course, $cm) = get_course_objects($id, null);

require_login($course->id, true, $cm);
$context = context_module::instance($cm->id);

$defaulturl = new moodle_url('/mod/offlinequiz/view.php', ['id' => $id]);

// We redirect students to info.
if (!has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    redirect($defaulturl);
}

$PAGE->set_url(new moodle_url('/mod/offlinequiz/navigate.php', ['id' => $id, 'tab' => $tab]));

$navigation = offlinequiz_get_tabs_object($offlinequiz, $cm);

// Give plugins a chance for routing the request.
$newurl = '';
$subplugins = \core_component::get_plugin_list('offlinequiz');
foreach ($subplugins as $subplugin => $subpluginpath) {
    // Instantiate class.
    $reportclass = offlinequiz_instantiate_report_class($subplugin);
    if (!$reportclass) {
        continue;
    }
    $askedroute = $reportclass->route($offlinequiz, $cm, $course, $tab);
    if ($askedroute) {
        $newurl = $navigation->find($askedroute, null)->action();
        break;
    }
}
// If no plugin has a redirection, check the default ones.
if ($newurl == '') {
    if ($tab == 'mod_offlinequiz_edit') {
        $sql = "SELECT count(*)
                  FROM {offlinequiz_groups} og
             LEFT JOIN {offlinequiz_group_questions} ogq ON ogq.offlinegroupid = og.id
                 WHERE ogq.id IS NULL
                   AND og.offlinequizid = :id";
        $hasmissinggroupquestions = $DB->count_records_sql($sql, ['id' => $offlinequiz->id]);
        if ($hasmissinggroupquestions) {
            $newurl = $navigation->find('tabeditgroupquestions', null)->action();
        } else {
            $newurl = $navigation->find('tabpreview', null)->action();
        }
    } else if ($tab == 'mod_offlinequiz_results') { // TODO: Move to plugin Route tab..
        $hasresults = $DB->record_exists('offlinequiz_results', ['offlinequizid' => $offlinequiz->id]);
        $needscorrections = $DB->record_exists('offlinequiz_scanned_pages', ['offlinequizid' => $offlinequiz->id, 'status' => 'error']);
        if ($needscorrections) {
            $newurl = $navigation->find('tabofflinequizupload', null)->action();

        } else if ($hasresults) {
            $newurl = $navigation->find('tabresultsoverview', null)->action();
        } else {
            $newurl = $navigation->find('tabofflinequizupload', null)->action();
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
        if (!$existparticipantslists) {
            $newurl = $navigation->find('tabparticipantlists', null)->action();
        } else if ($existslistnoparticipants) {
            $newurl = $navigation->find('tabeditparticipants', null)->action();
        } else if ($needscorrection) {
            $newurl = $navigation->find('tabparticipantsupload', null)->action();
        } else if ($hasresults) {
            $newurl = $navigation->find('tabattendancesoverview', null)->action();
        } else {
            $newurl = $navigation->find('tabdownloadparticipantsforms', null)->action();
        }
    } else if ($tab == 'tabforms') {
        if ($offlinequiz->docscreated) {
            $newurl = new moodle_url('/mod/offlinequiz/createquiz.php', ['q' => $offlinequiz->id,
                                                                         'mode' => 'createpdfs',
                                                                         'tab' => 'forms']);
        } else {
            $newurl = new moodle_url('/mod/offlinequiz/createquiz.php', ['q' => $offlinequiz->id,
                                                                         'tab' => 'forms']);
        }
    }
}
redirect($newurl);
