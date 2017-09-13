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
 * Defines the interface for participant lists
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_listform.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_uploadform.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_report.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_scanner.php');

define("MAX_USERS_PER_PAGE", 5000);

$id = optional_param('id', 0, PARAM_INT);               // Course Module ID.
$q = optional_param('q', 0, PARAM_INT);                 // Or offlinequiz ID.
$forcenew = optional_param('forcenew', 0, PARAM_INT);
$mode = optional_param('mode', 'editparticipants', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$download = optional_param('download', false, PARAM_ALPHA);

$letterstr = 'ABCDEFGHIJKL';

if ($id) {
    if (!$cm = get_coursemodule_from_id('offlinequiz', $id)) {
        print_error("There is no coursemodule with id $id");
    }
    if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
        print_error("Course is misconfigured");
    }
    if (!$offlinequiz = $DB->get_record("offlinequiz", array('id' => $cm->instance))) {
        print_error("The offlinequiz with id $cm->instance corresponding to this coursemodule $id is missing");
    }

} else {
    if (!$offlinequiz = $DB->get_record("offlinequiz", array('id' => $q))) {
        print_error("There is no offlinequiz with id $q");
    }
    if (!$course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
        print_error("The course with id $offlinequiz->course that the offlinequiz with id $q belongs to is missing");
    }
    if (!$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id)) {
        print_error("The course module for the offlinequiz with id $q is missing");
    }
}

require_login($course->id, false, $cm);

if (!offlinequiz_partlist_created($offlinequiz) and $mode != 'editlists') {
    $mode = 'editparticipants';
}

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($cm->course);
$systemcontext = context_system::instance();

// We redirect students to info.
if (!has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    redirect('view.php?q='.$offlinequiz->id);
}

$strpreview = get_string('participantslist', 'offlinequiz');
$strofflinequizzes = get_string("modulenameplural", "offlinequiz");

$thispageurl = new moodle_url('/mod/offlinequiz/participants.php',
                              array('id' => $id, 'q' => $q, 'mode' => $mode, 'forcenew' => $forcenew));

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('admin');
$node = $PAGE->settingsnav->find('mod_offlinequiz_participants', navigation_node::TYPE_SETTING);
$PAGE->force_settings_menu(true);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('editparticipants', 'offlinequiz');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->requires->yui_module('moodle-mod_offlinequiz-toolboxes',
		'M.mod_offlinequiz.init_resource_toolbox',
		array(array(
				'courseid' => $course->id,
				'offlinequizid' => $offlinequiz->id
		))
		);


offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

function find_pdf_file($contextid, $listfilename) {
    $fs = get_file_storage();
    if ($pdffile = $fs->get_file($contextid, 'mod_offlinequiz', 'participants', 0, '/', $listfilename)) {
        return $pdffile;
    } else {
        return $fs->get_file($contextid, 'mod_offlinequiz', 'pdfs', 0, '/', $listfilename);
    }
}

switch($mode) {
    case 'editlists':
        // Only print headers and tabs if not asked to download data.
        if (!$download && $action != 'savelist') {
            echo $OUTPUT->header();
            // Print the tabs.
            $currenttab = 'participants';
            include('tabs.php');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
        }

        switch ($action) {
            case 'edit':
                // Print the heading.
                echo $OUTPUT->heading_with_help(get_string('editlists', 'offlinequiz'), 'participants', 'offlinequiz');
                $list = $DB->get_record('offlinequiz_p_lists', array('id' => required_param('listid', PARAM_INT)));
                $list->listid = $list->id;
                $myform = new offlinequiz_participantslistform($offlinequiz->id, get_string('editlist', 'offlinequiz'));
                $myform->set_data($list);
                break;
            case 'delete':
                // Print the heading.
                echo $OUTPUT->heading_with_help(get_string('editlists', 'offlinequiz'), 'participants', 'offlinequiz');
                if (confirm_sesskey()) {
                    if ($list = $DB->get_record('offlinequiz_p_lists', array('id' => required_param('listid', PARAM_INT)))) {
                        $DB->delete_records('offlinequiz_participants', array('listid' => $list->id));
                        $DB->delete_records('offlinequiz_p_lists', array('id' => $list->id));
                    }
                }
                $myform = new offlinequiz_participantslistform($offlinequiz->id, get_string('addlist', 'offlinequiz'));
                break;
            case 'savelist':
                $myform = new offlinequiz_participantslistform($offlinequiz->id, get_string('addlist', 'offlinequiz'));
                if ($data = $myform->get_data()) {
                    if (empty($data->listid)) {
                        $record = new stdClass();
                        $record->offlinequizid = $offlinequiz->id;
                        $record->name = $data->name;
                        $sql = "SELECT max(number) as maxlist
                                  FROM {offlinequiz_p_lists}
                                 WHERE offlinequizid = :offlinequizid";
                        $last = $DB->get_record_sql($sql, array('offlinequizid' => $offlinequiz->id));
                        $record->number = $last->maxlist + 1;
                        $DB->insert_record('offlinequiz_p_lists', $record);
                    } else {
                        $DB->set_field('offlinequiz_p_lists', 'name', $data->name, array('id' => $data->listid));
                    }
                }
                redirect('participants.php?mode=editlists&amp;q='.$offlinequiz->id, get_string('changessaved'));
                break;
            default:
                // Print the heading.
                echo $OUTPUT->heading_with_help(get_string('editlists', 'offlinequiz'), 'participants', 'offlinequiz');
                $myform = new offlinequiz_participantslistform($offlinequiz->id, get_string('addlist', 'offlinequiz'));
                break;
        }

        echo $OUTPUT->box_start('boxaligncenter generalbox boxwidthnormal');
        $lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id));
        echo '<ul>';
        foreach ($lists as $list) {
            $sql = "SELECT COUNT(*)
                      FROM {offlinequiz_participants} p, {offlinequiz_p_lists} pl
                     WHERE p.listid = pl.id
                       AND pl.offlinequizid = :offlinequizid
                       AND p.listid = :listid";
            $params = array('offlinequizid' => $offlinequiz->id, 'listid' => $list->id);

            $numusers = $DB->count_records_sql($sql, $params);
            echo '<li>';
            $listname = '<b>' . $list->name . '(' . $numusers . ')</b>';
            $listurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('mode' => 'editparticipants', 'q' => $offlinequiz->id ,'listid' => $list->id));
            echo html_writer::link($listurl, $listname);
            $streditlist = get_string('editthislist', 'offlinequiz');
            $imagehtml = $OUTPUT->pix_icon('i/edit',  $streditlist);
            $editurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('mode' => 'editlists', 'action' => 'edit' , 'q' => $offlinequiz->id ,'listid' => $list->id));
            echo html_writer::link($editurl, $imagehtml, array('class' => 'editlistlink'));

            $strdeletelist = get_string('deletethislist', 'offlinequiz');
            $imagehtml = $OUTPUT->pix_icon('t/delete',  $strdeletelist);
            $deleteurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('mode' => 'editlists',
                          'action' => 'delete',
                          'q' => $offlinequiz->id,
                          'listid' => $list->id,
                          'sesskey' => sesskey()));
            echo html_writer::link($deleteurl, $imagehtml,array('onClick' =>
                            'return confirm(\'' . addslashes(get_string('deletelistcheck', 'offlinequiz')) . '\');',
                            'class' => 'deletelistlink'
            ));
            echo '</li>';
        }
        echo '</ul>';
        echo $OUTPUT->box_end();
        echo $OUTPUT->box_start('boxaligncenter generalbox boxwidthnormal');
        $myform->display();
        echo $OUTPUT->box_end();

        break;
    case 'editparticipants':
        // Print the heading.
        $strsearch = get_string('search');
        $strshowall = get_string('showall');
        $searchtext = optional_param('searchtext', '', PARAM_RAW);
        $listid = optional_param('listid', 0, PARAM_INT);
        $group = optional_param('group', 0, PARAM_INT);
        $addselect = optional_param_array('addselect', array(), PARAM_INT);
        $removeselect = optional_param_array('removeselect', array(), PARAM_RAW);

        if (!$list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid))) {
            if (!$lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id), ' number ASC ')) {
                $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                        array('q' => $offlinequiz->id, 'mode' => 'editlists'));
                redirect($url);
            } else {
                $list = array_pop($lists);
            }
        }
        // Only print headers and tabs if not asked to download data.
        if (!$download) {
            echo $OUTPUT->header();
            // Print the tabs.
            $currenttab = 'participants';
            include('tabs.php');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
        }

        echo $OUTPUT->heading_with_help(get_string('participantsinlists', 'offlinequiz'), 'participants', 'offlinequiz');
        if (!empty($addselect) && confirm_sesskey()) {
            $record = new stdClass();
            $record->listid = $list->id;
            $record->checked = 0;

            foreach ($addselect as $userid) {
                $record->userid = $userid;
                if (!$DB->get_record('offlinequiz_participants',
                        array('listid' => $record->listid, 'userid' => $record->userid))) {
                    $DB->insert_record('offlinequiz_participants', $record);
                }
            }
        }
        if (!empty($removeselect) && confirm_sesskey()) {
            list($dsql, $dparams) = $DB->get_in_or_equal($removeselect, SQL_PARAMS_NAMED, 'remove');
            $sql = "SELECT p.*
                      FROM {offlinequiz_participants} p,
                           {offlinequiz_p_lists} pl
                     WHERE p.listid = pl.id
                       AND p.userid $dsql
                       AND pl.offlinequizid = :offlinequizid
                       AND p.listid  = :listid";
            $dparams['offlinequizid'] = $offlinequiz->id;
            $dparams['listid'] = $list->id;

            $todelete = $DB->get_records_sql($sql, $dparams);
            foreach ($todelete as $deleteuser) {
                $DB->delete_records('offlinequiz_participants',
                        array('listid' => $list->id,
                                'userid' => $deleteuser->userid));
            }
        }
        $groups = groups_get_all_groups($course->id);
        $groupoptions = '';
        if (!empty($groups)) {
            foreach ($groups as $item) {
                $groupoptions .= '<option value="' . $item->id . '"';
                if ($group == $item->id) {
                    $groupoptions .= ' selected="selected"';
                }
                $groupoptions .= '>'.$item->name.'</option>';
            }
        }
        $listoptions = '';
        if ($lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id), 'name ASC')) {
            foreach ($lists as $item) {
                $listoptions .= '<option value="' . $item->id . '"';
                if ($list->id == $item->id) {
                    $listoptions .= ' selected="selected"';
                }
                $listoptions .= '>'.$item->name.'</option>';
            }
        }

        // First get roleids for students from leagcy.
        if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
            print_error("No roles with capability 'mod/offlinequiz:attempt' defined in system context");
        }

        $roleids = array();
        foreach ($roles as $role) {
            $roleids[] = $role->id;
        }
        $rolelist = implode(',', $roleids);

        list($csql, $cparams) = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');

        $membersoptions = '';

        list($rsql, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
        $params = array_merge($cparams, $rparams);

        $sql = "SELECT DISTINCT u.id, u." . $offlinequizconfig->ID_field . ", u.firstname, u.lastname,
                                u.alternatename, u.middlename, u.firstnamephonetic, u.lastnamephonetic
                  FROM {user} u, {offlinequiz_participants} p, {role_assignments} ra, {offlinequiz_p_lists} pl
                 WHERE ra.userid = u.id
                   AND p.listid = :listid
                   AND p.listid = pl.id
                   AND pl.offlinequizid = :offlinequizid
                   AND p.userid = u.id
                   AND ra.roleid $rsql AND ra.contextid $csql
                   ORDER BY u.lastname, u.firstname";

        $params['offlinequizid'] = $offlinequiz->id;
        $params['listid'] = $list->id;

        $memberids = array();
        if ($members = $DB->get_records_sql($sql, $params)) {
            $memberscount = count($members);
            foreach ($members as $member) {
                $membersoptions .= '<option value="' . $member->id.'">' . fullname($member) .
                ' (' . $member->{$offlinequizconfig->ID_field} . ')</option>';
                $memberids[] = $member->id;
            }
        } else {
            $memberscount = 0;
        }

        $potentialmembersoptions = '';
        $memberlist = implode(',', $memberids);

        if (!empty($group)) {
            $groupmembers = groups_get_members($group);
        }

        $potentialmemberscount = 0;
        $params = array_merge($rparams, $cparams);

        if ($potentialmembers = get_enrolled_users($coursecontext, 'mod/offlinequiz:attempt')) {
            foreach ($potentialmembers as $member) {
                if (empty($members[$member->id]) and (empty($group) or !empty($groupmembers[$member->id]))) {
                    $potentialmembersoptions .= '<option value="' . $member->id . '">' . fullname($member) .
                    ' (' . $member->{$offlinequizconfig->ID_field} . ')</option>';
                    $potentialmemberscount++;
                }
            }
        }

        include('participants/members.html');
        break;
    case 'attendances':
        // Only print headers and tabs if not asked to download data.
        if (!$download) {
            echo $OUTPUT->header();
            // Print the tabs.
            $currenttab = 'participants';
            include('tabs.php');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
            echo $OUTPUT->heading_with_help(get_string('attendances', 'offlinequiz'), 'participants', 'offlinequiz');
            if (!$lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id), 'name ASC')) {
                error('No list created for offlinequiz');
            }
            $options = array('0' => get_string('alllists', 'offlinequiz'));
            foreach ($lists as $option) {
                $options[$option->id] = $option->name;
            }
            $listid = optional_param('listid', 0, PARAM_INT);
            echo '<div align="center">' . get_string('participantslist', 'offlinequiz') . ':&nbsp;';
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/participants.php',
                    array('q' => $offlinequiz->id, 'mode' => 'attendances'));
            echo $OUTPUT->single_select($url, 'listid', $options, $listid);
            echo '<br />&nbsp;<br /></div>';
        }
        if ($action == 'uncheck' and $participantids = optional_param_array('participantid', array(), PARAM_INT)) {
            foreach ($participantids as $participantid) {
                if ($participantid) {
                    $DB->set_field('offlinequiz_participants', 'checked', 0, array('id' => $participantid));
                }

                // Log this event.
                $userid = $DB->get_field('offlinequiz_participants', 'userid', array('id' => $participantid));
                $params = array (
                           'objectid' => $userid,
                           'context' => context_module::instance( $cm->id ),
                           'other' => array (
                                   'mode' => 'attendances',
                                   'offlinequizid' => $offlinequiz->id,
                                   'type' => 'absent from'
                           )
                   );
                $event = \mod_offlinequiz\event\participant_manually_marked::create($params);
                $event->trigger();
            }
        }
        if ($action == 'check' and $participantids = optional_param_array('participantid', array(), PARAM_INT)) {
            foreach ($participantids as $participantid) {
                if ($participantid) {
                    $DB->set_field('offlinequiz_participants', 'checked', 1, array('id' => $participantid));
                }

                // Log this event.
                $userid = $DB->get_field('offlinequiz_participants', 'userid', array('id' => $participantid));
                $params = array (
                           'objectid' => $userid,
                           'context' => context_module::instance( $cm->id ),
                           'other' => array (
                                   'mode' => 'attendances',
                                   'offlinequizid' => $offlinequiz->id,
                                   'type' => 'present at'
                           )
                   );
                $event = \mod_offlinequiz\event\participant_manually_marked::create($params);
                $event->trigger();
            }
        }
        // We redirect if no list has been created.
        if (!offlinequiz_partlist_created($offlinequiz)) {
            redirect('participants.php?q='.$offlinequiz->id, get_string('createlistfirst', 'offlinequiz'));
        } else {
            if ($download) {
                offlinequiz_download_partlist($offlinequiz, $download, $coursecontext, $systemcontext);
            } else {
                offlinequiz_print_partlist($offlinequiz, $coursecontext, $systemcontext);
            }
        }
        break;
    case 'createpdfs':
        // We redirect if no list has been created.
        if (!offlinequiz_partlist_created($offlinequiz)) {
            redirect('participants.php?q='.$offlinequiz->id, get_string('createlistfirst', 'offlinequiz'));
        }
        // Only print headers and tabs if not asked to download data.
        if (!$download) {
            echo $OUTPUT->header();
            // Print the tabs.
            $currenttab = 'participants';
            include('tabs.php');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
            echo $OUTPUT->heading_with_help(get_string('createpdfsparticipants', 'offlinequiz'), 'participants', 'offlinequiz');
        }
        // Show update button.
        ?>

        <div class="singlebutton" align="center">
	        <form action="<?php echo "$CFG->wwwroot/mod/offlinequiz/participants.php" ?>" method="post">
		        <div>
			        <input type="hidden" name="q" value="<?php echo $offlinequiz->id ?>" />
			        <input type="hidden" name="forcenew" value="1" />
			        <input type="hidden" name="mode" value="createpdfs" />
			        <button type="submit"
				    onClick='return confirm("<?php echo get_string('reallydeleteupdatepdf', 'offlinequiz') ?>")' 
				    class="btn btn-secondary">
				    	<?php echo get_string('deleteupdatepdf', 'offlinequiz') ?>
				    </button>
                </div>
	        </form>
	        <br>&nbsp;<br>
        </div>
        <?php

        echo $OUTPUT->box_start('boxaligncenter generalbox boxwidthnormal');

        $sql = "SELECT id, name, number, filename
                  FROM {offlinequiz_p_lists}
                 WHERE offlinequizid = :offlinequizid
              ORDER BY name ASC";

        $lists = $DB->get_records_sql($sql, array('offlinequizid' => $offlinequiz->id));

        foreach ($lists as $list) {
            $fs = get_file_storage();

            // Delete existing pdf if forcenew.
            if ($forcenew && property_exists($list, 'filename') && $list->filename
                    && $file = find_pdf_file($context->id, $list->filename)) {
                $file->delete();
                $list->filename = null;
            }

            $pdffile = null;
            // Create PDF file if necessary.
            if (!property_exists($list, 'filename') ||  !$list->filename ||
                    !$pdffile = find_pdf_file($context->id, $list->filename)) {
                $pdffile = offlinequiz_create_pdf_participants($offlinequiz, $course->id, $list, $context);
                if (!empty($pdffile)) {
                    $list->filename = $pdffile->get_filename();
                }
                $DB->update_record('offlinequiz_p_lists', $list);
            }

            // Show downloadlink.
            if ($pdffile) {
                $url = "$CFG->wwwroot/pluginfile.php/" . $pdffile->get_contextid() . '/' . $pdffile->get_component() . '/' .
                    $pdffile->get_filearea() . '/' . $pdffile->get_itemid() . '/' . $pdffile->get_filename() .
                    '?forcedownload=1';
                echo $OUTPUT->action_link($url, trim(format_text(get_string('downloadpartpdf', 'offlinequiz', $list->name))));

                $list->filename = $pdffile->get_filename();
                $DB->update_record('offlinequiz_p_lists', $list);
            } else {
                echo $OUTPUT->notification(format_text(get_string('createpartpdferror', 'offlinequiz', $list->name)));
            }
            echo '<br />&nbsp;<br />';
        }
        echo $OUTPUT->box_end();
        break;
    case 'upload':
        // We redirect if no list created.
        if (!offlinequiz_partlist_created($offlinequiz)) {
            redirect('participants.php?q='.$offlinequiz->id, get_string('createlistfirst', 'offlinequiz'));
        }

        $lists = $DB->get_records_sql("
                SELECT *
                  FROM {offlinequiz_p_lists}
                 WHERE offlinequizid = :offlinequizid
              ORDER BY name ASC",
                array('offlinequizid' => $offlinequiz->id));

        $fs = get_file_storage();

        // We redirect if all pdf files are missing.
        $redirect = true;
        foreach ($lists as $list) {
            if ($list->filename && $file = find_pdf_file($context->id, $list->filename)) {
                $redirect = false;
            }
        }

        if ($redirect) {
            redirect('participants.php?mode=createpdfs&amp;q=' . $offlinequiz->id, get_string('createpdffirst', 'offlinequiz'));
        }

        // Only print headers and tabs if not asked to download data.
        if (!$download) {
            echo $OUTPUT->header();
            // Print the tabs.
            $currenttab = 'participants';
            include('tabs.php');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
            echo $OUTPUT->heading_with_help(get_string('uploadpart', 'offlinequiz'), 'partimportnew', 'offlinequiz');
        }
        $report = new participants_report();
        $importform = new offlinequiz_participants_upload_form($thispageurl);

        $first = optional_param('first', 0, PARAM_INT);                // Index of the last imported student.
        $numimports = optional_param('numimports', 0, PARAM_INT);
        $tempdir = optional_param('tempdir', 0, PARAM_PATH);

        if ($newfile = optional_param('newfile', '', PARAM_INT)) {
            if ($fromform = $importform->get_data()) {

                @raise_memory_limit('128M');
                $offlinequizconfig->papergray = $offlinequiz->papergray;

                $fileisgood = false;

                // Work out if this is an uploaded file
                // or one from the filesarea.
                $realfilename = $importform->get_new_filename('newfile');
                // Create a unique temp dir.
                srand(microtime() * 1000000);
                $unique = str_replace('.', '', microtime(true) . rand(0, 100000));
                $tempdir = "{$CFG->tempdir}/offlinequiz/import/$unique";
                check_dir_exists($tempdir, true, true);

                $importfile = $tempdir . '/' . $realfilename;

                if (!$result = $importform->save_file('newfile', $importfile, true)) {
                    throw new moodle_exception('uploadproblem');
                }

                $files = array();
                $mimetype = mimeinfo('type', $importfile);
                if ($mimetype == 'application/zip') {
                    $fp = get_file_packer('application/zip');
                    $files = $fp->extract_to_pathname($importfile, $tempdir);
                    if ($files) {
                        unlink($importfile);
                        $files = get_directory_list($tempdir);
                    } else {
                        echo $OUTPUT->notification(get_string('couldnotunzip', 'offlinequiz_rimport', $realfilename),
                                                   'notifyproblem');

                    }
                } else if (preg_match('/^image/' , $mimetype)) {
                    $files[] = $realfilename;
                }
            }

            if (empty($files)) {
                $files = get_directory_list($tempdir);
            }

            $numpages = count($files);
            $last = $first + OFFLINEQUIZ_IMPORT_NUMUSERS - 1;
            if ($last > $numpages - 1) {
                $last = $numpages - 1;
            }
            $a = new stdClass();
            $a->from = $first + 1;
            $a->to = $last + 1;
            $a->total = $numpages;
            echo $OUTPUT->box_start();
            print_string('importfromto', 'offlinequiz', $a);
            echo "<br />";
            echo $OUTPUT->box_end();
            echo $OUTPUT->box_start();

            $offlinequizconfig->papergray = $offlinequiz->papergray;

            for ($j = $first; $j <= $last; $j++) {
                $file = $files[$j];
                $filename = $tempdir . '/' . $file;
                set_time_limit(120);
                $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
                if ($scannedpage = $scanner->load_image($filename)) {
                    if ($scannedpage->status == 'ok') {
                        list($scanner, $scannedpage) =
                            offlinequiz_check_scanned_participants_page($offlinequiz, $scanner, $scannedpage,
                                                                        $USER->id, $coursecontext, true);
                    }
                    if ($scannedpage->status == 'ok') {
                        $scannedpage =
                            offlinequiz_process_scanned_participants_page($offlinequiz, $scanner, $scannedpage,
                                                                          $USER->id, $coursecontext);
                    }
                    if ($scannedpage->status == 'ok') {
                        $choicesdata = $DB->get_records('offlinequiz_p_choices', array('scannedppageid' => $scannedpage->id));
                        $scannedpage = $scannedpage =
                            offlinequiz_submit_scanned_participants_page($offlinequiz, $scannedpage, $choicesdata);
                        if ($scannedpage->status == 'submitted') {
                            echo get_string('pagenumberimported', 'offlinequiz', $j)."<br /><br />";
                        }
                    }
                } else {
                    if ($scanner->ontop) {
                        $scannedpage->status = 'error';
                        $scannedpage->error = 'upsidedown';
                    }
                }
            }
            echo $OUTPUT->box_end();
            if ($last == $numpages - 1 or $numpages == 0) {
                if ($numimports) {
                    $OUTPUT->notification(get_string('numpages', 'offlinequiz', $numimports), 'notifysuccess');
                } else {
                    $OUTPUT->notification(get_string('nopages', 'offlinequiz'));
                }
                remove_dir($tempdir);
                echo $OUTPUT->continue_button("$CFG->wwwroot/mod/offlinequiz/participants.php?q=$offlinequiz->id&amp;mode=upload");
                $OUTPUT->footer();
                die;
            } else {
                $first = $last + 1;
                redirect("$CFG->wwwroot/mod/offlinequiz/participants.php?q=$offlinequiz->id&amp;mode=upload&amp;" .
                        "action=upload&amp;tempdir=$tempdir&amp;first=$first&amp;numimports=$numimports&amp;sesskey=".sesskey());
            }
            $importform->display();
        } else if ($action == 'delete') {
            // Some pages need to be deleted.
            $pageids = optional_param_array('pageid', array(), PARAM_INT);
            foreach ($pageids as $pageid) {
                if ($pageid && $todelete = $DB->get_record('offlinequiz_scanned_p_pages', array('id' => $pageid))) {
                    $DB->delete_records('offlinequiz_scanned_p_pages', array('id' => $pageid));
                    $DB->delete_records('offlinequiz_p_choices', array('scannedppageid' => $pageid));
                }
            }
            $report->error_report($offlinequiz, $course->id);
            $importform->display();
        } else {
            $report->error_report($offlinequiz, $course->id);
            $importform->display();
        }
        break;
}

// Finish the page.
if (!$download) {
    echo $OUTPUT->footer();
}
