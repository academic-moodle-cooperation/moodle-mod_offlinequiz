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
 * The script for correcting errors in scanned participant lists
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
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
$listchosen = optional_param('listchosen', 0, PARAM_INT);

if (!$scannedpage = $DB->get_record('offlinequiz_scanned_p_pages', array('id' => $pageid))) {
    print_error('noscannedpage', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, $pageid);
}

if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $scannedpage->offlinequizid))) {
    print_error('noofflinequiz', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course,
                $scannedpage->offlinequizid);
}

if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('nocourse', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course,
                array('course' => $offlinequiz->course,
                      'offlinequiz' => $offlinequiz->id));
}
if (!$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id)) {
    print_error('cmmissing', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' . $offlinequiz->course, $offlinequiz->id);
}

// Fix string listnumber delivered smallint by DB.
if (property_exists($scannedpage, 'listnumber') && $scannedpage->listnumber && is_string($scannedpage->listnumber)) {
    $scannedpage->listnumber = intval($scannedpage->listnumber);
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

$list = null;

// Load corner values if readjusted.
$ulx = optional_param('ul_x', 0, PARAM_INT);
$uly = optional_param('ul_y', 0, PARAM_INT);
$urx = optional_param('ur_x', 0, PARAM_INT);
$ury = optional_param('ur_y', 0, PARAM_INT);
$llx = optional_param('ll_x', 0, PARAM_INT);
$lly = optional_param('ll_y', 0, PARAM_INT);
$lrx = optional_param('lr_x', 0, PARAM_INT);
$lry = optional_param('lr_y', 0, PARAM_INT);


echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo ".barcodeselect {width:200px; height:24px; border:solid 2px;}\n";
echo ".barcodeerror {border-color: red;}\n";
echo "</style>\n";

// Initialise a page scanner.
$scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
$corners = array();
$sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);
$scanner->get_list();

if ($sheetloaded) {
    $corners = $scanner->export_corners(OQ_IMAGE_WIDTH);
}

if ($action == 'load') {
    // Remember initial data for cancel action.
    $origfilename = $scannedpage->filename;
    $origlistnumber = $scannedpage->listnumber;
    $origstatus = $scannedpage->status;
    $origerror = $scannedpage->error;
    $origtime = $scannedpage->time;
} else {
    $origfilename = required_param('origfilename', PARAM_FILE);
    $origlistnumber = required_param('origlistnumber', PARAM_INT);
    $origstatus = required_param('origstatus', PARAM_ALPHA);
    $origerror = required_param('origerror', PARAM_ALPHA);
    $origtime = required_param('origtime', PARAM_INT);
}

// -------------------------------------------------------------
// Action cancel.
// -------------------------------------------------------------
if ($action == 'cancel') {
    $scannedpage->filename = $origfilename;
    $scannedpage->listnumber = $origlistnumber;
    $scannedpage->status = $origstatus;
    $scannedpage->error = $origerror;
    $scannedpage->time = $origtime;
    $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);

    // Display a button to close the window and die.
    echo '<html>';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
    echo "<center><input class=\"imagebutton\" type=\"submit\" value=\"" .
        get_string('closewindow', 'offlinequiz')."\" name=\"submitbutton4\" onClick=\"self.close(); return false;\"></center>";
    echo '</html>';
    die;

} else if ($action == 'update') {
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
    $ulx = $upperleft->x;
    $uly = $upperleft->y;
    $urx = $upperright->x;
    $ury = $upperright->y;
    $llx = $lowerleft->x;
    $lly = $lowerleft->y;
    $lrx = $lowerright->x;
    $lry = $lowerright->y;

    // Initialise a new page scanner.
    $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
    $sheetloaded = $scanner->load_stored_image($scannedpage->filename, array($upperleft, $upperright, $lowerleft, $lowerright));
    // The following calibrates the scanner.
    $scanner->get_list();

    $OUTPUT->heading(get_string('participantslist', 'offlinequiz'));
    $listid = required_param('listid', PARAM_INT);
    // Get the values chosen by the user.
    $newparticipants = required_param_array('participants', PARAM_RAW);
    $userid = required_param_array('userid', PARAM_RAW);

    // Maybe old errors have been fixed.
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    if ($list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid))) {
        $scannedpage->listnumber = intval($list->number);
    }

    // -------------------------------------------------------------
    // Action setlist
    // -------------------------------------------------------------
} else if ($action == 'setlist') {
        $upperleft = new oq_point(required_param('c-0-x', PARAM_INT) + 8, required_param('c-0-y', PARAM_INT) + 8);
        $upperright = new oq_point(required_param('c-1-x', PARAM_INT) + 8, required_param('c-1-y', PARAM_INT) + 8);
        $lowerleft = new oq_point(required_param('c-2-x', PARAM_INT) + 8, required_param('c-2-y', PARAM_INT) + 8);
        $lowerright = new oq_point(required_param('c-3-x', PARAM_INT) + 8, required_param('c-3-y', PARAM_INT) + 8);
        $ulx = $upperleft->x;
        $uly = $upperleft->y;
        $urx = $upperright->x;
        $ury = $upperright->y;
        $llx = $lowerleft->x;
        $lly = $lowerleft->y;
        $lrx = $lowerright->x;
        $lry = $lowerright->y;

        // Maybe old errors have been fixed.
        $scannedpage->status = 'ok';
        $scannedpage->error = '';
        $listid = required_param('listid', PARAM_INT);
        $list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid));
        $scannedpage->listnumber = intval($list->number);
        $listnumber = $list->number;

        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);

        $offlinequizconfig->papergray = $offlinequiz->papergray;
        // Initialise a new page scanner.
        $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
        $sheetloaded = $scanner->load_stored_image($scannedpage->filename,
                                                   array($upperleft, $upperright, $lowerleft, $lowerright));

        // The following calibrates the scanner.
        $scanner->get_list();
    if ($scannedpage->listnumber) {
        $listchosen = 1;
    }

        // -------------------------------------------------------------
        // Action readjust
        // -------------------------------------------------------------
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
    $ulx = $upperleft->x;
    $uly = $upperleft->y;
    $urx = $upperright->x;
    $ury = $upperright->y;
    $llx = $lowerleft->x;
    $lly = $lowerleft->y;
    $lrx = $lowerright->x;
    $lry = $lowerright->y;
    $offlinequizconfig->papergray = $offlinequiz->papergray;

    $corners = array($upperleft, $upperright, $lowerleft, $lowerright);

    // Create a completely new scanner and load the image with the submitted corners.
    $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);
    $sheetloaded = $scanner->load_stored_image($scannedpage->filename, $corners);
    // The following calibrates the scanner.
    $scanner->get_list();

    // Maybe old errors have been fixed.
    $scannedpage->status = 'ok';
    $scannedpage->error = '';

    // We reset all user information s.t. it is retrieved again from the scanner.
    $listid = optional_param('listid', 0, PARAM_INT);

    if (!$listid) {
        $listnumber = $scannedpage->listnumber;
    } else if (!$listchosen) {
        $list = $DB->get_record('offlinequiz_p_lists', array('id' => $listid));
        $scannedpage->listnumber = intval($list->number);
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
    $ulx = $upperleft->x;
    $uly = $upperleft->y;
    $urx = $upperright->x;
    $ury = $upperright->y;
    $llx = $lowerleft->x;
    $lly = $lowerleft->y;
    $lrx = $lowerright->x;
    $lry = $lowerright->y;

    $scannedpage->status = 'ok';
    $scannedpage->error = '';
    $scannedpage->listnumber = 0;

    $offlinequizconfig->papergray = $offlinequiz->papergray;

    if ($newfile = $scanner->rotate_180()) {
        $scannedpage->filename = $newfile->get_filename();
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);

        // Create a completely new scanner.
        $scanner = new offlinequiz_participants_scanner($offlinequiz, $context->id, 0, 0);

        // Load the stored picture file.
        $sheetloaded = $scanner->load_stored_image($scannedpage->filename,
                                                   array($upperleft, $upperright, $lowerleft, $lowerright));
        // The following calibrates the scanner.
        $scanner->get_list();
        $participants = $scanner->get_participants();
    }
} else if ($action == 'setpage') {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
        echo "<input class=\"imagebutton\" type=\"submit\" value=\"" . get_string('cancel')."\" name=\"submitbutton4\"
onClick=\"self.close(); return false;\"><br />";
        die;
    }

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

// Now we check the scanned page with potentially updated information.
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

    // Compute the db data indexed by userid.
    $dbdata = array();
    foreach ($dbparticipants as $data) {
        $dbdata[$data->userid] = $data;
    }
    $insecuremarkings = false;
    $unknownuser = false;

    foreach ($newparticipants as $key => $value) {
        // Continue if the checkbox has been marked but no userid has been chosen.
        if ($value == 'marked' && (!isset($userid[$key]) || $userid[$key] < 1)) {
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
            // Should not happen because we can only save the page when all checkboxes are either empty or marked.
            $dbdata[$userid[$key]]->value = -1;
            $DB->set_field('offlinequiz_p_choices', 'value', -1,
                    array('scannedppageid' => $scannedpage->id, 'userid' => $userid[$key]));
            $insecuremarkings = true;
        }
    }

    if (!$insecuremarkings && !$unknownuser) {
        $scannedpage = offlinequiz_submit_scanned_participants_page($offlinequiz, $scannedpage, $dbdata);
        echo '<html>';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
        if ($scannedpage->status == 'submitted') {
            echo get_string('pageimported', 'offlinequiz')."<br /><br />";
            $DB->set_field('offlinequiz_scanned_p_pages', 'error', '', array('id' => $pageid));
        } else {
            echo get_string('couldnotimportpage', 'offlinequiz')."<br /><br />";
            $scannedpage->status = 'error';
            $scannedpage->error = 'insecuremarkings';
            $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
        }
        echo "<div align=\"center\"><input type=\"button\" value=\"" .
            get_string('closewindow')."\" onClick=\"window.opener.location.reload(1); self.close();return false;\">";
        echo "</div></body>\n";
        echo "</html>\n";
        die();
    } else if ($unknownuser) {
        $scannedpage->status = 'error';
        $scannedpage->error = 'insecuremarkings';
        $DB->update_record('offlinequiz_scanned_p_pages', $scannedpage);
    }
}

// -------------------------------------------------------------
// OUTPUT THE PAGE HTML.
// -------------------------------------------------------------
echo '<html>';
echo "<style>\n";
echo "body {margin:0px; font-family:Arial,Verdana,Helvetica,sans-serif;}\n";
echo ".imagebutton {width:250px; height:24px; text-align:left; margin-bottom:10px;}\n";
echo "</style>\n";
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';

// Print JavaScript-includes.
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/jquery-1.4.3.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.core.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.widget.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.mouse.js');
echo html_writer::script('', $CFG->wwwroot.'/mod/offlinequiz/lib/jquery/ui/jquery.ui.draggable.js');

$javascript = "<script language=\"JavaScript\">
 function checkinput(alert) {
   insecuremarkings = false;
   missinguserid = false;
   for (i=1; i<=" . count($participants) . "; i++) {
      checkbox = $('input[name^=\"participants[' + i + '\"]');
      userid = $('input[name^=\"userid[' + i + '\"]');
      if (checkbox.attr('value') == 'X') {
          insecuremarkings = true;
          if (alert) {
              alert(\"" . addslashes(get_string('insecuremarkings', 'offlinequiz')) . "\");
          }
      }
      if (checkbox.attr('value') == '1' && userid.attr('value') == '0') {
          document.getElementById('b'+i).setAttribute('class', 'barcodeselect barcodeerror');
          missinguserid = true;
          if (alert) {
              alert(\"" . addslashes(get_string('missinguserid', 'offlinequiz')) . "\");
          }
      }
   }
   if (insecuremarkings || missinguserid) {
       return false;
   } else {
       if (alert) {

       } else {
         $('input[name^=\"submitbutton1\"]').removeAttr('disabled');
       }
   }
}

function set_list(n) {
   document.forms.cform.elements['action'].value='setlist';
   document.forms.cform.submit();
}

function set_participant(image, x) {
   if (document.forms.cform.elements['participants['+x+']'].value=='marked') {
       image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\";
       document.forms.cform.elements['participants['+x+']'].value = 'empty';
   } else {
       image.src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\";
       document.forms.cform.elements['participants['+x+']'].value = 'marked';
   }
   return checkinput(false);
}

function select_user(element, x, value) {
   document.forms.cform.elements['userid['+ x + ']'].value = value;
   if (value == 0) {
       element.setAttribute('class', 'barcodeselect barcodeerror');
   } else {
       element.setAttribute('class', 'barcodeselect');
   }
   return checkinput(false);
}

function submitCancel() {
  document.forms.cform.elements['action'].value='cancel';
  document.forms.cform.submit();
}

function submitReadjust() {
   changed = false;
   for (i=0; i<=3; i++) {
       corner = document.getElementById('c-'+i);
       document.forms.cform.elements['c-'+i+'-x'].value = corner.style.left.replace('px','');
       document.forms.cform.elements['c-'+i+'-y'].value = corner.style.top.replace('px','');
       if (document.forms.cform.elements['c-'+i+'-x'].value != document.forms.cform.elements['c-old-'+i+'-x'].value) {
           changed = true;
       }
       if (document.forms.cform.elements['c-'+i+'-y'].value != document.forms.cform.elements['c-old-'+i+'-y'].value) {
           changed = true;
       }
   }
   if (!changed) {
       alert('".get_string('movecorners', 'offlinequiz')."');
   } else {
       document.forms.cform.elements['action'].value='readjust';
       document.forms.cform.submit();
   }
}

function submitRotated() {
   document.forms.cform.elements['action'].value='rotate';
   document.forms.cform.submit();
}

</script>";

echo $javascript;

echo "<div style=\"position:absolute; top:10px; left:" . (OQ_IMAGE_WIDTH + 10) . "px; width:280px\">\n";
echo "<form method=\"post\" action=\"participants_correct.php\" id=\"cform\">\n";
echo "<input type=\"hidden\" name=\"pageid\" value=\"$pageid\">\n";
echo "<input type=\"hidden\" name=\"listchosen\" value=\"$listchosen\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
echo "<input type=\"hidden\" name=\"show\" value=\"0\">\n";
echo "<input type=\"hidden\" name=\"sesskey\" value=\"". sesskey() . "\">\n";
echo "<input type=\"hidden\" name=\"ul_x\" value=\"$ulx\">\n";
echo "<input type=\"hidden\" name=\"ul_y\" value=\"$uly\">\n";
echo "<input type=\"hidden\" name=\"ur_x\" value=\"$urx\">\n";
echo "<input type=\"hidden\" name=\"ur_y\" value=\"$ury\">\n";
echo "<input type=\"hidden\" name=\"ll_x\" value=\"$llx\">\n";
echo "<input type=\"hidden\" name=\"ll_y\" value=\"$lly\">\n";
echo "<input type=\"hidden\" name=\"lr_x\" value=\"$lrx\">\n";
echo "<input type=\"hidden\" name=\"lr_y\" value=\"$lry\">\n";

echo "<input type=\"hidden\" name=\"origfilename\" value=\"$origfilename\">\n";
echo "<input type=\"hidden\" name=\"origlistnumber\" value=\"$origlistnumber\">\n";
echo "<input type=\"hidden\" name=\"origstatus\" value=\"$origstatus\">\n";
echo "<input type=\"hidden\" name=\"origerror\" value=\"$origerror\">\n";
echo "<input type=\"hidden\" name=\"origtime\" value=\"$origtime\">\n";

foreach ($participants as $key => $participant) {
    if (empty($participant->userid)) {
        $participant->userid = 0;
    }
    echo "<input type=\"hidden\" name=\"participants[$key]\" value=\"{$participant->value}\">\n";
    echo "<input type=\"hidden\" name=\"userid[$key]\" value=\"{$participant->userid}\">\n";
}

if (!empty($corners)) {
    foreach ($corners as $key => $hotspot) {
        echo "<input type=\"hidden\" name=\"c-old-$key-x\" value=\"".($hotspot->x - 7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-old-$key-y\" value=\"".($hotspot->y - 7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-$key-x\" value=\"".($hotspot->x - 7)."\">\n";
        echo "<input type=\"hidden\" name=\"c-$key-y\" value=\"".($hotspot->y - 7)."\">\n";
    }
}

echo "<div style=\"margin:4px;margin-bottom:8px\"><u>";
print_string('actions');
echo ":</u></div>";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('cancel').
"\" name=\"submitbutton4\" onClick=\"submitCancel(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('rotate', 'offlinequiz').
"\" name=\"submitbutton5\" onClick=\"submitRotated(); return false;\"><br />";
echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('readjust', 'offlinequiz').
"\" name=\"submitbutton3\" onClick=\"submitReadjust(); return false;\"><br />";

if ($scannedpage->status == 'error' && ($scannedpage->error == 'insecuremarkings' || $scannedpage->error == 'invalidlistnumber')) {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz') .
        "\" name=\"submitbutton1\" disabled=\"disabled\">";
} else {
    echo "<input class=\"imagebutton\" type=\"submit\" value=\"".get_string('save', 'offlinequiz') .
    "\" name=\"submitbutton1\" onClick=\"return checkinput(true)\">";
}
echo "</div>\n";

echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" border=\"0\" style=\"position:absolute; top:1260px; left:0px\">";

$fs = get_file_storage();
$imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename);

// Print image of the form sheet.
echo '<img name="formimage" src="' . $CFG->wwwroot . "/pluginfile.php/$context->id/mod_offlinequiz/imagefiles/0/" .
$imagefile->get_filename() .'" border="1" width="' . OQ_IMAGE_WIDTH .
'" style="position:absolute; top:0px; left:0px; display: block;">';

if ($scannedpage->status == 'error') {
    echo "<div style=\"position:absolute; top: 20px; left: 130px\">\n";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;<strong style=\"color: red\">(" .
        get_string('error' . $scannedpage->error, 'offlinequiz_rimport') . ")</strong>\n";
    echo "</div>\n";
}

// Print list select box.
if ($scannedpage->error == 'invalidlistnumber' && !$scanner->ontop && !$listchosen) {
    echo "<div style=\"position:absolute; top:300px; left:180px; width:500px; background-color:#eee; padding:20px; " .
        " border:2px red solid; text-align: center\">";
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

// Print hotspots.
if ($sheetloaded) {
    foreach ($scanner->export_hotspots_participants(860) as $key => $hotspot) {
        $x = substr($key, 1);
        if (isset($participants[$x])) {
            if ($participants[$x]->value == 'unknown') {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/blue.gif\" " .
                    " border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                    $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            } else if ($participants[$x]->value == 'marked') {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/green.gif\" " .
                    " border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                    $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            } else {
                echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/spacer.gif\" " .
                    " border=\"0\" id=\"p$x\" style=\"position:absolute; top:".
                    $hotspot->y."px; left:".$hotspot->x."px; cursor:pointer\" onClick=\"set_participant(this, $x)\">\n";
            }
        }
    }
    // Get userid select items.
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
             if (this.value==0) {this.setAttribute('class', 'barcodeselect barcodeerror');} else {this.setAttribute('c\
lass', 'barcodeselect');}; checkinput(false);\">\n>";
            echo "<option value=\"0\"></option>\n";
            foreach ($users as $user) {
                echo "<option value=\"{$user->userid}\"";
                if (isset($participants[$x]) and $participants[$x]->userid == $user->userid) {
                    echo ' selected="selected"';
                }
                echo ">" . substr($user->{$offlinequizconfig->ID_field},
                                  strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits) .
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
        echo "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/corner.gif\" border=\"0\" name=\"c-$key\" id=\"c-$key\" " .
            " style=\"position:absolute; top:" .
            ($hotspot->y - 7) . "px; left:" . ($hotspot->x - 7) . "px; cursor:pointer;\">";
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
