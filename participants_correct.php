<?php
// This file is for Moodle - http://moodle.org/
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
 * The script for correcting errors in scanned participant lists
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/default.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_scanner.php');
require_once($CFG->dirroot . '/mod/offlinequiz/participants/participants_report.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

$pageid     = optional_param('pageid', 0, PARAM_INT);
$overwrite  = optional_param('overwrite', 0, PARAM_INT);
$action     = optional_param('action', 'load', PARAM_TEXT);
$submitpage = optional_param('page', 0, PARAM_INT);

if (!$scannedpage = $DB->get_record('offlinequiz_scanned_p_pages', array('id' => $pageid))) {
    print_error('noscannedpage', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, $pageid);
}

if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $scannedpage->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, $scannedpage->offlinequizid);
}

if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, array('course' => $offlinequiz->course,
         'offlinequiz' => $offlinequiz->id));
}
if (!$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id)) {
    print_error('cmmissing', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, $offlinequiz->id);
}

require_login($course->id, false, $cm);

$context = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);
require_capability('mod/offlinequiz:viewreports', $context);

$url = new moodle_url('/mod/offlinequiz/participants_correct.php', array('pageid' => $scannedpage->id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

$offlinequiz->penaltyscheme = 0;
$offlinequiz->timelimit = 0;
$offlinequiz->timeclose = 0;
offlinequiz_load_useridentification();
$report = new participants_report();
// if (!$filename = optional_param('filename', '', PARAM_RAW)) $filename = $report->get_pic_name($log->rawdata);

$action = optional_param('action', '', PARAM_TEXT);
$error = false;
$list = null;

// Load corner values if readjusted
$ul_x = optional_param('ul_x', 0, PARAM_INT);
$ul_y = optional_param('ul_y', 0, PARAM_INT);
$ur_x = optional_param('ur_x', 0, PARAM_INT);
$ur_y = optional_param('ur_y', 0, PARAM_INT);
$ll_x = optional_param('ll_x', 0, PARAM_INT);
$ll_y = optional_param('ll_y', 0, PARAM_INT);
$lr_x = optional_param('lr_x', 0, PARAM_INT);
$lr_y = optional_param('lr_y', 0, PARAM_INT);


echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo ".barcodeselect {width:200px; height:24px; border:solid 2px;}\n";
echo ".barcodeerror {border-color: red;}\n";
echo "</style>\n";

// Initialise a page scanner
$scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
$corners = array();
$sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);

if ($sheetloaded) {
    $corners = $scanner->export_corners(OQ_IMAGE_WIDTH);
}

if ($action == 'update') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    $OUTPUT->heading(get_string('participantslist', 'offlinequiz'));
    $listid = required_param('listid', PARAM_INT);
    $newparticipants = required_param('participants', PARAM_RAW);
    $userid = required_param('userid', PARAM_RAW);

    // Maybe old errors have been fixed
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    if ($list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid))) {
        $scannedpage->listnumber = intval($list->number);
    }

} else if ($action == 'setlist') {
        $upperleft = new oq_point(required_param('c-0-x', PARAM_INT) + 8, required_param('c-0-y', PARAM_INT) + 8);
        $upperright = new oq_point(required_param('c-1-x', PARAM_INT) + 8, required_param('c-1-y', PARAM_INT) + 8);
        $lowerleft = new oq_point(required_param('c-2-x', PARAM_INT) + 8, required_param('c-2-y', PARAM_INT) + 8);
        $lowerright = new oq_point(required_param('c-3-x', PARAM_INT) + 8, required_param('c-3-y', PARAM_INT) + 8);
        $ul_x = $upperleft->x;
        $ul_y = $upperleft->y;
        $ur_x = $upperright->x;
        $ur_y = $upperright->y;
        $ll_x = $lowerleft->x;
        $ll_y = $lowerleft->y;
        $lr_x = $lowerright->x;
        $lr_y = $lowerright->y;
        
        // maybe old errors have been fixed
        $scannedpage->status = 'ok';
        $scannedpage->error = '';
        $listid = required_param('listid', PARAM_INT);
        $list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid));
        $scannedpage->listnumber = intval($list->number);
        $listnumber = $list->number;
    
        $offlinequizconfig->papergray = $offlinequiz->papergray;
        $sheetloaded = $scanner->load_stored_image($scannedpage->filename, array($upperleft, $upperright, $lowerleft, $lowerright));
        $scannedpage = offlinequiz_check_scanned_participants_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

} else if ($action == 'readjust') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }
    
    $upperleft = new oq_point(required_param('c-0-x', PARAM_INT) + 7, required_param('c-0-y', PARAM_INT) + 7);
    $upperright = new oq_point(required_param('c-1-x', PARAM_INT) + 7, required_param('c-1-y', PARAM_INT) + 7);
    $lowerleft = new oq_point(required_param('c-2-x', PARAM_INT) + 7, required_param('c-2-y', PARAM_INT) + 7);
    $lowerright = new oq_point(required_param('c-3-x', PARAM_INT) + 7, required_param('c-3-y', PARAM_INT) + 7);
    $ul_x = $upperleft->x;
    $ul_y = $upperleft->y;
    $ur_x = $upperright->x;
    $ur_y = $upperright->y;
    $ll_x = $lowerleft->x;
    $ll_y = $lowerleft->y;
    $lr_x = $lowerright->x;
    $lr_y = $lowerright->y;
    $offlinequizconfig->papergray = $offlinequiz->papergray;
     
    $corners = array($upperleft, $upperright, $lowerleft, $lowerright);

    // Create a completely new scanner and load the image with the submitted corners.
    $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
    $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);

    // maybe old errors have been fixed
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    // we reset all user information s.t. it is retrieved again from the scanner
    $listid = optional_param('listid', 0, PARAM_INT);
    if (!$listid) {
        $listnumber = $scannedpage->listnumber;
    } else {
        $list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid));
        $scannedpage->listnumber = $list->number;
        $listnumber = $list->number;
    }
    $scannedpage->participants = null;

} else if ($action == 'rotate') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    $upperleft = new oq_point(853 - required_param('c-3-x', PARAM_INT), 1208 - required_param('c-3-y', PARAM_INT));
    $upperright = new oq_point(853 - required_param('c-2-x', PARAM_INT), 1208 - required_param('c-2-y', PARAM_INT));
    $lowerleft = new oq_point(853 - required_param('c-1-x', PARAM_INT), 1208 - required_param('c-1-y', PARAM_INT));
    $lowerright = new oq_point(853 - required_param('c-0-x', PARAM_INT), 1208 - required_param('c-0-y', PARAM_INT));
    $ul_x = $upperleft->x;
    $ul_y = $upperleft->y;
    $ur_x = $upperright->x;
    $ur_y = $upperright->y;
    $ll_x = $lowerleft->x;
    $ll_y = $lowerleft->y;
    $lr_x = $lowerright->x;
    $lr_y = $lowerright->y;

    $scannedpage->status = 'ok';
    $scannedpage->error = '';
    $scannedpage->listnumber = 0;

    $offlinequizconfig->papergray = $offlinequiz->papergray;

    if ($newfile = $scanner->rotate_180()) {
        $scannedpage->filename = $newfile->get_filename();
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);

        // create a completely new scanner
        $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);

        // load the stored picture file.
        $sheetloaded = $scanner->load_stored_image($scannedpage->filename, array($upperleft, $upperright, $lowerleft, $lowerright));
        $listnumber = $scanner->get_list();
        if (!$listnumber) {
            $error = true;
        }
        $participants = $scanner->get_participants();
    }
} else if ($action == 'setpage') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

    $error = OFFLINEQUIZ_PART_FATAL_ERROR==$log->error;
    //  $participants = $report->get_participants($log->rawdata);
    $listid = required_param('listid', PARAM_INT);
    if (!$listid) {
        $listnumber = $scannedpage->listnumber;
    } else {
        $list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid));
        $scannedpage->listnumber = intval($list->number);
        $scannedpage->status = 'ok';
        $scannedpage->error = '';
    }
}
$offlinequizconfig->papergray = $offlinequiz->papergray;

// now we check the scanned page with potentially updated information
$scannedpage = offlinequiz_check_scanned_participants_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);

$listnumber = $scannedpage->listnumber;
if ($listnumber > 0) {
    $list = $DB->get_record('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id, 'number' => $listnumber));
}

$participants = $scanner->get_participants();

if ($scannedpage->status == 'ok') {
    $scannedpage = offlinequiz_process_scanned_participants_page($offlinequiz, $scanner, $scannedpage, $USER->id, $coursecontext);
}
$listnumber = $scannedpage->listnumber;

if ($action == 'update') {
    @raise_memory_limit('128M');
    set_time_limit(120);
    $OUTPUT->box_start();
    $dbparticipants = $DB->get_records('offlinequiz_p_choices', array('scannedppageid' => $scannedpage->id));

    // Compute the db data indexed by userid
    $dbdata = array();
    foreach ($dbparticipants as $data) {
        $dbdata[$data->userid] = $data;
    }
    $insecuremarkings = false;
    $unknownuser = false;

    foreach ($newparticipants as $key => $value) {
        if (!isset($userid[$key]) || $userid[$key] < 1) {
            $unknownuser = true;
            continue;
        }
        if (!isset($dbdata[$userid[$key]])) {
            $choice = new StdClass();
            $choice->scannedppageid = $scannedpage->id;
            $choice->userid = $userid[$key];
            $choice->id = $DB->insert_record('offlinequiz_p_choices', $choice);
            $dbdata[$userid[$key]] = $choice;
        } else {
            $dbdata[$userid[$key]]->userid = $userid[$key];
        }
        if ($value == 'marked') {
            $dbdata[$userid[$key]]->value = 1;
            $DB->set_field('offlinequiz_p_choices', 'value', 1,
                    array('scannedppageid' => $scannedpage->id, 'userid' => $userid[$key]));
        } else if ($value == 'empty') {
            $dbdata[$userid[$key]]->value = 0;
            $DB->set_field('offlinequiz_p_choices', 'value', 0,
                    array('scannedppageid' => $scannedpage->id, 'userid' => $userid[$key]));
        } else {
            $dbdata[$userid[$key]]->value = -1;
            $DB->set_field('offlinequiz_p_choices', 'value', -1,
                    array('scannedppageid' => $scannedpage->id, 'userid' => $userid[$key]));
            $insecuremarkings = true;
        }
    }

    if (!$insecuremarkings && !$unknownuser) {
        $scannedpage = offlinequiz_submit_scanned_participants_page($offlinequiz, $scannedpage, $dbdata);
        if ($scannedpage->status == 'submitted') {
            echo get_string('pageimported', 'offlinequiz')."<br /><br />";
            $DB->set_field('offlinequiz_scanned_p_pages', 'error', '', array('id' => $pageid));
        } else {
            echo get_string('couldnotimportpage', 'offlinequiz')."<br /><br />";
            $scannedpage->status = 'error';
            $scannedpage->error = 'insecuremarkings';
            $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
        }
        echo "<div align=\"center\"><input type=\"button\" value=\"".get_string('closewindow')."\" onClick=\"window.opener.location.reload(1); self.close();return false;\">";
        echo "</div>\n";
        die();
    } else if ($unknownuser) {
        $scannedpage->status = 'error';
        $scannedpage->error = 'insecuremarkings';
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
    }
}
// Print JavaScript-includes
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/jquery-1.4.3.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.core.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.widget.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.mouse.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.draggable.js');

echo "<script language=\"JavaScript\">\n";
echo "function checkinput() {\n";
echo "   for (i=1; i<=".count($participants)."; i++) {\n";
echo "      if (document.forms.cform.elements['participants['+i+']'].value == 'X') {\n";
echo "          alert(\"".addslashes(get_string('insecuremarkings', 'offlinequiz'))."\");\n";
echo "          return false;\n";
echo "      }\n";
echo "      if (document.forms.cform.elements['participants['+i+']'].value == '1' && document.forms.cform.elements['userid['+i+']'].value == 0) {\n";
echo "          document.getElementById('b'+i).setAttribute('class', 'barcodeselect barcodeerror');\n";
echo "          alert(\"".addslashes(get_string('missinguserid', 'offlinequiz'))."\");\n";
echo "          return false;\n";
echo "      }\n";
echo "   }\n";
echo "}\n";

echo "function set_list(n) {\n";
echo "   document.forms.cform.elements['action'].value='setlist'\n";
echo "   document.forms.cform.submit();\n";
echo "}\n";

echo "function set_participant(image, x) {\n";
echo "   if (document.forms.cform.elements['participants['+x+']'].value=='marked') {\n";
echo "       image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\"\n";
echo "       document.forms.cform.elements['participants['+x+']'].value = 'empty';\n";
echo "   } else {\n";
echo "       image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\"\n";
echo "       document.forms.cform.elements['participants['+x+']'].value = 'marked';\n";
echo "   }\n";
echo "}\n";

echo "function submitReadjust() {\n";
echo "   changed = false;\n";
echo "   for (i=0; i<=3; i++) {\n";
echo "       corner = document.getElementById('c-'+i);\n";
echo "       document.forms.cform.elements['c-'+i+'-x'].value = corner.style.left.replace('px','');\n";
echo "       document.forms.cform.elements['c-'+i+'-y'].value = corner.style.top.replace('px','');\n";
echo "       if (document.forms.cform.elements['c-'+i+'-x'].value != document.forms.cform.elements['c-old-'+i+'-x'].value) {\n";
echo "           changed = true;\n";
echo "       }\n";
echo "       if (document.forms.cform.elements['c-'+i+'-y'].value != document.forms.cform.elements['c-old-'+i+'-y'].value) {\n";
echo "           changed = true;\n";
echo "       }\n";
echo "   }\n";
echo "   if (!changed) {\n";
echo "       alert('".get_string('movecorners', 'offlinequiz')."')\n";
echo "   } else {\n";
echo "       document.forms.cform.elements['action'].value='readjust'\n";
echo "       document.forms.cform.submit();\n";
echo "   }\n";
echo "}\n";

echo "function submitRotated() {\n";
echo "   document.forms.cform.elements['action'].value='rotate'\n";
echo "   document.forms.cform.submit();\n";
echo "}\n";

echo "</script>\n\n";

echo "<div style=\"position:absolute; top:10px; left:" . (OQ_IMAGE_WIDTH + 10) . "px; width:280px\">\n";
echo "<form method=\"post\" action=\"participants_correct.php\" id=\"cform\">\n";
echo "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
echo "<input type=\"hidden\" name=\"show\" value=\"0\">\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"". sesskey() . "\">\n";
echo "<input type=\"hidden\" name=\"ul_x\" value=\"$ul_x\">\n";
echo "<input type=\"hidden\" name=\"ul_y\" value=\"$ul_y\">\n";
echo "<input type=\"hidden\" name=\"ur_x\" value=\"$ur_x\">\n";
echo "<input type=\"hidden\" name=\"ur_y\" value=\"$ur_y\">\n";
echo "<input type=\"hidden\" name=\"ll_x\" value=\"$ll_x\">\n";
echo "<input type=\"hidden\" name=\"ll_y\" value=\"$ll_y\">\n";
echo "<input type=\"hidden\" name=\"lr_x\" value=\"$lr_x\">\n";
echo "<input type=\"hidden\" name=\"lr_y\" value=\"$lr_y\">\n";

foreach ($participants as $key => $participant) {
    if (empty($participant->userid)) {
        $participant->userid = 0;
    }
    echo "<input type=\"hidden\" name=\"participants[$key]\" value=\"{$participant->value}\">\n";
    echo "<input type=\"hidden\" name=\"userid[$key]\" value=\"{$participant->userid}\">\n";
}

if (!empty($corners)) {
    foreach ($corners as $key => $hotspot) {
        echo "<input type=\"hidden\" name=\"c-old-$key-x\" value=\"".($hotspot->x-7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-old-$key-y\" value=\"".($hotspot->y-7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-$key-x\" value=\"".($hotspot->x-7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-$key-y\" value=\"".($hotspot->y-7)."\">\n";
    }
}

echo "<div style=\"margin:4px;margin-bottom:8px\"><u>";
print_string('actions');
echo ":</u></div>";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('cancel').
"\" name=\"submitbutton4\" onClick=\"window.opener.location.reload(1); self.close(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('rotate', 'offlinequiz').
"\" name=\"submitbutton5\" onClick=\"submitRotated(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('readjust', 'offlinequiz').
"\" name=\"submitbutton3\" onClick=\"submitReadjust(); return false;\"><br />";

if ($scannedpage->status == 'error' && $scannedpage->error != 'insecuremarkings') {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz')."\" name=\"submitbutton1\" disabled=\"disabled\">";
} else {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz').
    "\" name=\"submitbutton1\" onClick=\"return checkinput()\">";
}
echo "</div>\n";

echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" style=\"position:absolute; top:1260px; left:0px\">";

$fs = get_file_storage();
$imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename);

// ==================================================
// print image of the form sheet
echo '<img name="formimage" src="' . $CFG->wwwroot . "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" .
  $imagefile->get_filename() .'" border="1" width="' . OQ_IMAGE_WIDTH . '" style="position:absolute; top:0px; left:0px; display: block;">';

if ($scannedpage->status == 'error') {
    echo "<div style=\"position:absolute; top: 20px; left: 130px\">\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong style=\"color: red\">(" . get_string('error' . $scannedpage->error, 'offlinequiz_rimport') . ")</strong>\n";
    echo "</div>\n";
}

// ==================================================
// print list select box
if ($scannedpage->error == 'invalidlistnumber' && !$scanner->ontop) {
    echo "<div style=\"position:absolute; top:300px; left:180px; width:500px; background-color:#eee; padding:20px; border:2px red solid; text-align: center\">";
    print_string('listnotdetected', 'offlinequiz');
    echo "<br />";
    print_string('selectlist', 'offlinequiz');
    echo "<br />";
    echo "<select class=\"imagebutton\" name=\"listid\" onchange=\"set_list(this.value); true;\">\n";
    
    echo '<option value="0">'.get_string('choose').'...</option>';
    if ($lists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id), 'name ASC')) {
        foreach ($lists as $item) {
            echo '<option value="' .  $item->id . '">' . $item->name . '</option>';
        }
    }
    echo "</select>\n";
    echo "</div>";
} else if ($list) {
    echo "<input type=\"hidden\" name=\"listid\" value=\"" . $list->id . "\">\n";
}

echo "</form>\n";

// Print hotspots
if ($sheetloaded) {
    foreach ($scanner->export_hotspots_participants(860) as $key => $hotspot) {
        $x = substr($key, 1);
        if (isset($participants[$x])) {
            if ($participants[$x]->value == 'unknown') {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            } else if ($participants[$x]->value == 'marked') {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            } else {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            }
        }
    }
    // Get userid select items
    if ($list) {
        $sql = "SELECT DISTINCT p.id, p.userid, u." . $offlinequizconfig->ID_field . ", u.firstname, u.lastname
                FROM {user} u,
                     {offlinequiz_participants} p,
                     {offlinequiz_p_lists} pl
               WHERE p.userid = u.id
                 AND p.listid = :listid
                 AND p.listid = pl.id
                 AND pl.offlinequizid = :offlinequizid
            ORDER BY u.lastname, u.firstname";

        $params['offlinequizid'] = $offlinequiz->id;
        $params['listid'] = $list->id;

        $users = $DB->get_records_sql($sql, $params);
        foreach ($scanner->export_hotspots_barcodes(860) as $key => $hotspot) {
            $x = substr($key, 1);
            echo "<select name=\"userids[$x]\" class=\"barcodeselect";
            if (!isset($participants[$x]) or $participants[$x]->userid == 0) {
                echo " barcodeerror";
            }
            echo "\" id=\"b$x\" style=\"position:absolute; top:".($hotspot->y - 10)."px; left:".($hotspot->x - 100).
            "px\" onChange=\"document.forms.cform.elements['userid['+$x+']'].value = this.value;
             if (this.value==0) {this.setAttribute('class', 'barcodeselect barcodeerror');} else {this.setAttribute('class', 'barcodeselect');};\">\n>";
            echo "<option value=\"0\"></option>\n";
            foreach ($users as $user) {
                echo "<option value=\"{$user->userid}\"";
                if (isset($participants[$x]) and $participants[$x]->userid == $user->userid) {
                    echo ' selected="selected"';
                }
                echo ">" . substr($user->{$offlinequizconfig->ID_field}, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits) .
                ', ' . $user->lastname . ' ' . $user->firstname . "</option>\n";
            }
            echo "</select>\n";
        }
    }
    echo "<form name=\"cornersform\" action=\"participants_correct.php\">\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"readjust\">\n";
    echo "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\">\n";
    foreach ($participants as $key => $participant) {
        echo "<input type=\"hidden\" name=\"item[$key]\" value=\"{$participant->value}\">\n";
        echo "<input type=\"hidden\" name=\"userid[$key]\" value=\"{$participant->userid}\">\n";
    }
    foreach ($scanner->export_corners(OQ_IMAGE_WIDTH) as $key => $hotspot) {
        echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/corner.gif\" border=\"0\" name=\"c-$key\" id=\"c-$key\" style=\"position:absolute; top:".
        ($hotspot->y-7)."px; left:".($hotspot->x-7)."px; cursor:pointer;\">";
    }
    echo "</form>\n";
    ?>

    <script>
    $(function() {
        $( "#c-0" ).draggable();
        $( "#c-1" ).draggable();
        $( "#c-2" ).draggable();
        $( "#c-3" ).draggable();
    });
    </script>

    <?php
}
