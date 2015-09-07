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
 * This defines the tabs for oflinequizzes. The file is included by various frontend scripts.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (empty($offlinequiz)) {
    print_error('Offline offlinequiz not defined for tab navigation');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($offlinequizcm)) {
    $offlinequizcm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
}

$context = context_module::instance($offlinequizcm->id);

if (!isset($contexts)) {
    $contexts = new question_edit_contexts($context);
}
$tabs = array();
$row  = array();
$inactive = array();
$activated = array();

if (has_capability('mod/offlinequiz:view', $context)) {
    $row[] = new tabobject('info', "$CFG->wwwroot/mod/offlinequiz/view.php?q=$offlinequiz->id", get_string('info', 'offlinequiz'));
}
if (has_capability('mod/offlinequiz:manage', $context)) {
    $row[] = new tabobject('editq', "$CFG->wwwroot/mod/offlinequiz/edit.php?cmid=$cm->id",
            get_string('groupquestions', 'offlinequiz'));
}
if (has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    $row[] = new tabobject('createofflinequiz', "$CFG->wwwroot/mod/offlinequiz/createquiz.php?q=$offlinequiz->id",
            get_string('createofflinequiz', 'offlinequiz'));
    $row[] = new tabobject('participants', "$CFG->wwwroot/mod/offlinequiz/participants.php?q=$offlinequiz->id",
            get_string('participantslists', 'offlinequiz'));
}
if (has_capability('mod/offlinequiz:viewreports', $context)) {
    $row[] = new tabobject('reports', "$CFG->wwwroot/mod/offlinequiz/report.php?q=$offlinequiz->id",
            get_string('results', 'offlinequiz'));
}
if (has_capability('mod/offlinequiz:viewreports', $context) &&
        has_capability('offlinequiz/statistics:view', $context)) {
    $row[] = new tabobject('statistics', "$CFG->wwwroot/mod/offlinequiz/report.php?q=$offlinequiz->id&mode=statistics",
            get_string('statisticsplural', 'offlinequiz'));
}

if ($currenttab != 'info' || count($row) != 1) {
    $tabs[] = $row;
}

if ($currenttab == 'reports' && isset($mode)) {
    $inactive[] = 'reports';
    $activated[] = 'reports';

    $allreports = core_component::get_plugin_list('offlinequiz');

    $reportlist = array('overview', 'rimport'); // Standard reports we want to show first.

    foreach ($allreports as $key => $path) {
        if (!in_array($key, $reportlist)) {
            $reportlist[] = $key;
        }
    }

    $row  = array();
    $currenttab = '';
    foreach ($reportlist as $report) {
        if ($report != 'statistics') {
            $row[] = new tabobject($report, "$CFG->wwwroot/mod/offlinequiz/report.php?q=$offlinequiz->id&amp;mode=$report",
                    get_string($report, 'offlinequiz'));
            if ($report == $mode) {
                $currenttab = $report;
            }
        }
    }
    $tabs[] = $row;
}

if ($currenttab == 'createofflinequiz' and isset($mode)) {
    $inactive[] = 'createofflinequiz';
    $activated[] = 'createofflinequiz';

    $createlist = array ('preview', 'createpdfs');

    $row  = array();
    $currenttab = '';
    foreach ($createlist as $createtab) {
        $row[] = new tabobject($createtab,
                "$CFG->wwwroot/mod/offlinequiz/createquiz.php?q=$offlinequiz->id&amp;mode=$createtab",
        get_string($createtab, 'offlinequiz'));
        if ($createtab == $mode) {
            $currenttab = $createtab;
        }
    }
    if ($currenttab == '') {
        $currenttab = 'preview';
    }
    $tabs[] = $row;
}

if ($currenttab == 'participants' and isset($mode)) {
    $inactive[] = 'participants';
    $activated[] = 'participants';

    $participantstabs = array ('editlists', 'editparticipants', 'attendances', 'createpdfs', 'upload');

    $row  = array();
    $currenttab = '';
    foreach ($participantstabs as $participantstab) {
        $row[] = new tabobject($participantstab,
                "$CFG->wwwroot/mod/offlinequiz/participants.php?q=$offlinequiz->id&amp;mode=$participantstab",
        get_string($participantstab, 'offlinequiz'));
        if ($participantstab == $mode) {
            $currenttab = $participantstab;
        }
    }
    $tabs[] = $row;
}

if ($currenttab == 'editq' and isset($mode)) {
    $inactive[] = 'editq';
    $activated[] = 'editq';

    $row  = array();
    $currenttab = $mode;

    $strofflinequizzes = get_string('modulenameplural', 'offlinequiz');
    $strofflinequiz = get_string('modulename', 'offlinequiz');
    $streditingofflinequiz = get_string("editinga", "moodle", $strofflinequiz);
    $strupdate = get_string('updatethis', 'moodle', $strofflinequiz);

    $row[] = new tabobject('edit', new moodle_url($thispageurl,
            array('gradetool' => 0)), get_string('editingofflinequiz', 'offlinequiz'));
    $row[] = new tabobject('grade', new moodle_url($thispageurl,
            array('gradetool' => 1)), get_string('gradingofflinequiz', 'offlinequiz'));

    $tabs[] = $row;
}

if ($currenttab == 'statistics' and isset($statmode)) {
    $inactive[] = 'statistics';
    $activated[] = 'statistics';

    $row  = array();
    $currenttab = $statmode;
    $offlinegroup = optional_param('offlinegroup', -1, PARAM_INT);

    $row[] = new tabobject('statsoverview', new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id,
            array('mode' => 'statistics', 'statmode' => 'statsoverview', 'offlinegroup' => $offlinegroup)),
            get_string('statsoverview', 'offlinequiz_statistics'));
    $row[] = new tabobject('questionstats', new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id,
            array('mode' => 'statistics', 'statmode' => 'questionstats', 'offlinegroup' => $offlinegroup)),
            get_string('questionstats', 'offlinequiz_statistics'));
    $row[] = new tabobject('questionandanswerstats',
                           new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id,
                                          array('mode' => 'statistics',
                                                'statmode' => 'questionandanswerstats',
                                                'offlinegroup' => $offlinegroup)),
                           get_string('questionandanswerstats', 'offlinequiz_statistics'));

    $tabs[] = $row;
}

print_tabs($tabs, $currenttab, $inactive, $activated);
