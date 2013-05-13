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
 * Page to edit offlinequizzes
 *
 * This page generally has two columns:
 * The right column lists all available questions in a chosen category and
 * allows them to be edited or more to be added. This column is only there if
 * the offlinequiz does not already have student attempts
 * The left column lists all questions that have been added to the current offlinequiz.
 * The lecturer can add questions from the right hand list to the offlinequiz or remove them
 *
 * The script also processes a number of actions:
 * Actions affecting a offlinequiz:
 * up and down  Changes the order of questions and page breaks
 * addquestion  Adds a single question to the offlinequiz
 * add          Adds several selected questions to the offlinequiz
 * addrandom    Adds a certain number of random questions to the offlinequiz
 * repaginate   Re-paginates the offlinequiz
 * delete       Removes a question from the offlinequiz
 * savechanges  Saves the order and grades for questions in the offlinequiz
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/editlib.php');
require_once($CFG->dirroot . '/question/category_class.php');


/**
 * Callback function called from question_list() function
 * (which is called from showbank())
 * Displays button in form with checkboxes for each question.
 */
function module_specific_buttons($cmid, $cmoptions) {
    global $OUTPUT;
    if ($cmoptions->hasattempts) {
        $disabled = 'disabled="disabled" ';
    } else {
        $disabled = '';
    }
    $out = '<input type="submit" name="add" value="' . $OUTPUT->larrow() . ' ' .
            get_string('addtoofflinequiz', 'offlinequiz') . '" ' . $disabled . "/>\n";
    return $out;
}

/**
 * Callback function called from question_list() function
 * (which is called from showbank())
 */
function module_specific_controls($totalnumber, $recurse, $category, $cmid, $cmoptions) {
    global $OUTPUT;
    $out = '';
    $catcontext = get_context_instance_by_id($category->contextid);
    if (has_capability('moodle/question:useall', $catcontext)) {
        if ($cmoptions->hasattempts) {
            $disabled = ' disabled="disabled"';
        } else {
            $disabled = '';
        }
        $randomusablequestions =
        question_bank::get_qtype('random')->get_available_questions_from_category(
                $category->id, $recurse);
        $maxrand = count($randomusablequestions);
        if ($maxrand > 0) {
            for ($i = 1; $i <= min(10, $maxrand); $i++) {
                $randomcount[$i] = $i;
            }
            for ($i = 20; $i <= min(100, $maxrand); $i += 10) {
                $randomcount[$i] = $i;
            }
        } else {
            $randomcount[0] = 0;
            $disabled = ' disabled="disabled"';
        }

        //      $out = '<strong><label for="menurandomcount">'.get_string('addrandomfromcategory', 'offlinequiz').
        //                 '</label></strong><br />';
        //      $attributes = array();
        //      $attributes['disabled'] = $disabled ? 'disabled' : null;
        //      $select = html_writer::select($randomcount, 'randomcount', '1', null, $attributes);
        //      $out .= get_string('addrandom', 'offlinequiz', $select);
        //      $out .= '<input type="hidden" name="recurse" value="'.$recurse.'" />';
        //      $out .= '<input type="hidden" name="categoryid" value="' . $category->id . '" />';
        //      $out .= ' <input type="submit" name="addrandom" value="'.
        //      get_string('addtoofflinequiz', 'offlinequiz').'"' . $disabled . ' />';
        // $out .= $OUTPUT->help_icon('addarandomquestion', 'offlinequiz');
    }
    return $out;
}

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$offlinequiz_reordertool = optional_param('reordertool', -1, PARAM_BOOL);
$offlinequiz_qbanktool = optional_param('qbanktool', -1, PARAM_BOOL);
$scrollpos = optional_param('scrollpos', '', PARAM_INT);

list($thispageurl, $contexts, $cmid, $cm, $offlinequiz, $pagevars) =
question_edit_setup('editq', '/mod/offlinequiz/edit.php', true);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

// Determine groupid.
$groupnumber    = optional_param('groupnumber', -1, PARAM_INT);
if ($groupnumber === -1 and !empty($SESSION->question_pagevars['groupnumber'])) {
    $groupnumber = $SESSION->question_pagevars['groupnumber'];
}

if ($groupnumber === -1) {
    $groupnumber = 1;
}

$offlinequiz->groupnumber = $groupnumber;
$thispageurl->param('groupnumber', $offlinequiz->groupnumber);

if ($offlinequiz_group = offlinequiz_get_group($offlinequiz, $groupnumber)) {
    $offlinequiz->groupid = $offlinequiz_group->id;
    $groupquestions = offlinequiz_get_group_questions($offlinequiz);
    $purequestions = offlinequiz_questions_in_offlinequiz($groupquestions);
    // Clean layout. Remove empty pages if there are no questions in the offlinequiz group.
    $offlinequiz->questions = offlinequiz_clean_layout($groupquestions, empty($purequestions));
} else {
    print_error('invalidgroupnumber', 'offlinequiz');
}

$offlinequiz->sumgrades = offlinequiz_get_group_sumgrades($offlinequiz);

// Does the offlinequiz already have complete results (attempts)?
$hasscannedpages = offlinequiz_has_scanned_pages($offlinequiz->id);
$docscreated = $offlinequiz->docscreated;

$PAGE->set_url($thispageurl);

$pagetitle = get_string('editingofflinequiz', 'offlinequiz');
if ($offlinequiz_reordertool) {
    $pagetitle = get_string('orderingofflinequiz', 'offlinequiz');
}
// Get the course object and related bits.
$course = $DB->get_record('course', array('id' => $offlinequiz->course));
if (!$course) {
    print_error('invalidcourseid', 'error');
}

$questionbank = new offlinequiz_question_bank_view($contexts, $thispageurl, $course, $cm, $offlinequiz);
$questionbank->set_offlinequiz_has_attempts($docscreated);

// Log this visit.
add_to_log($cm->course, 'offlinequiz', 'editquestions',
        "view.php?id=$cm->id", "$offlinequiz->id", $cm->id);

// You need mod/offlinequiz:manage in addition to question capabilities to access this page.
require_capability('mod/offlinequiz:manage', $contexts->lowest());

if (empty($offlinequiz->grades)) {
    $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);
}

// Process commands.
// if ($offlinequiz->shufflequestions) {
//  // Strip page breaks before processing actions, so that re-ordering works
//  // as expected when shuffle questions is on.
//  $offlinequiz->questions = offlinequiz_repaginate($offlinequiz->questions, 0);
//  offlinequiz_save_questions($offlinequiz);
// }

// Get the list of question ids had their check-boxes ticked.
$selectedquestionids = array();
$params = (array) data_submitted();
foreach ($params as $key => $value) {
    if (preg_match('!^s([0-9]+)$!', $key, $matches)) {
        $selectedquestionids[] = $matches[1];
    }
}

$afteractionurl = new moodle_url($thispageurl);
if ($scrollpos) {
    $afteractionurl->param('scrollpos', $scrollpos);
}
if (($up = optional_param('up', false, PARAM_INT)) && confirm_sesskey()) {
    $offlinequiz->questions = offlinequiz_move_question_up($offlinequiz->questions, $up);
    offlinequiz_save_questions($offlinequiz);
	offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

if (($down = optional_param('down', false, PARAM_INT)) && confirm_sesskey()) {
    $offlinequiz->questions = offlinequiz_move_question_down($offlinequiz->questions, $down);
    offlinequiz_save_questions($offlinequiz);
	offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

if (optional_param('repaginate', false, PARAM_BOOL) && confirm_sesskey()) {
    // Re-paginate the offlinequiz.
    $questionsperpage = optional_param('questionsperpage', $offlinequiz->questionsperpage, PARAM_INT);
    $offlinequiz->questions = offlinequiz_repaginate($offlinequiz->questions, $questionsperpage );

    offlinequiz_save_questions($offlinequiz);
	offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}


if (($addquestion = optional_param('addquestion', 0, PARAM_INT)) && confirm_sesskey()) {
    // Add a single question to the current offlinequiz.
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    offlinequiz_add_question_to_group($addquestion, $offlinequiz, $addonpage);
    // TODO offlinequiz_delete_previews($offlinequiz);
    $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
    $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);
    $thispageurl->param('lastchanged', $addquestion);
    offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    // Add selected questions to the current offlinequiz.
    $rawdata = (array) data_submitted();
    $questionids = array();
    foreach ($rawdata as $key => $value) {
        // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $questionids[] = $matches[1];
        }
    }
    @raise_memory_limit('128M');
    set_time_limit(120);
    offlinequiz_add_questionlist_to_group($questionids, $offlinequiz);
    $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);

    $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
    offlinequiz_delete_template_usages($offlinequiz);
    //  redirect($afteractionurl);
}

if (optional_param('addnewpagesafterselected', null, PARAM_CLEAN) &&
        !empty($selectedquestionids) && confirm_sesskey()) {
    foreach ($selectedquestionids as $questionid) {
        $offlinequiz->questions = offlinequiz_add_page_break_after($offlinequiz->questions, $questionid);
    }
    offlinequiz_save_questions($offlinequiz);
    offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

$addpage = optional_param('addpage', false, PARAM_INT);
if ($addpage !== false && confirm_sesskey()) {
    $offlinequiz->questions = offlinequiz_add_page_break_at($offlinequiz->questions, $addpage);
    offlinequiz_save_questions($offlinequiz);
    offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

$deleteemptypage = optional_param('deleteemptypage', false, PARAM_INT);
if (($deleteemptypage !== false) && confirm_sesskey()) {
    $offlinequiz->questions = offlinequiz_delete_empty_page($offlinequiz->questions, $deleteemptypage);
    offlinequiz_save_questions($offlinequiz);
    offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

$remove = optional_param('remove', false, PARAM_INT);
if (($remove = optional_param('remove', false, PARAM_INT)) && confirm_sesskey()) {
    offlinequiz_remove_question($offlinequiz, $remove);
    // TODO offlinequiz_delete_previews($offlinequiz);
    $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
    offlinequiz_delete_template_usages($offlinequiz);
    //  redirect($afteractionurl);
}

if (optional_param('offlinequizdeleteselected', false, PARAM_BOOL) &&
        !empty($selectedquestionids) && confirm_sesskey()) {

    offlinequiz_remove_questionlist($offlinequiz, $selectedquestionids);
    offlinequiz_delete_template_usages($offlinequiz);
    $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
    // redirect($afteractionurl);
}

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {
    $deletepreviews = false;
    $recomputesummarks = false;

    $oldquestions = explode(',', $offlinequiz->questions); // the questions in the old order
    $questions = array(); // for questions in the new order
    $rawdata = (array) data_submitted();
    $moveonpagequestions = array();

    $moveselectedonpage = optional_param('moveselectedonpagetop', 0, PARAM_INT);
    if (!$moveselectedonpage) {
        $moveselectedonpage = optional_param('moveselectedonpagebottom', 0, PARAM_INT);
    }

    // Parameter to copy selected questions to another group.
    $copyselectedtogroup = optional_param('copyselectedtogrouptop', 0, PARAM_INT);
    if (!$copyselectedtogroup) {
        $copyselectedtogroup = optional_param('copyselectedtogroupbottom', 0, PARAM_INT);
    }

    // Parameter for copying all questions of a group to another.
    $copytogrouptop = optional_param('copytogrouptop', 0, PARAM_INT);
    // Parameter for copying all questions of a group to another.
    $copytogroupbottom = optional_param('copytogroupbottom', 0, PARAM_INT);

    foreach ($rawdata as $key => $value) {
        if (preg_match('!^g([0-9]+)$!', $key, $matches)) {
            // Parse input for question -> grades
            $questionid = $matches[1];
            $offlinequiz->grades[$questionid] = unformat_float($value); 
            offlinequiz_update_question_instance($offlinequiz->grades[$questionid], $questionid, $offlinequiz);
            $deletepreviews = false;
            $recomputesummarks = true;

        } else if (preg_match('!^o(pg)?([0-9]+)$!', $key, $matches)) {
            // Parse input for ordering info
            $questionid = $matches[2];
            // Make sure two questions don't overwrite each other. If we get a second
            // question with the same position, shift the second one along to the next gap.
            $value = clean_param($value, PARAM_INTEGER);
            while (array_key_exists($value, $questions)) {
                $value++;
            }
            if ($matches[1]) {
                // This is a page-break entry.
                $questions[$value] = 0;
            } else {
                $questions[$value] = $questionid;
            }
            $deletepreviews = true;
        }
    }

    // If ordering info was given, reorder the questions
    if ($questions) {
        ksort($questions);
        $questions[] = 0;
        $offlinequiz->questions = implode(',', $questions);
        offlinequiz_save_questions($offlinequiz);
        $deletepreviews = true;
    }

    // Get a list of questions to move, later to be added in the appropriate
    // place in the string.
    if ($moveselectedonpage) {
        $questions = explode(',', $offlinequiz->questions);
        $newquestions = array();
        // Remove the questions from their original positions first.
        foreach ($questions as $questionid) {
            if (!in_array($questionid, $selectedquestionids)) {
                $newquestions[] = $questionid;
            }
        }
        $questions = $newquestions;

        // Move to the end of the selected page.
        $pagebreakpositions = array_keys($questions, 0);
        $numpages = count($pagebreakpositions);

        // Ensure the target page number is in range.
        for ($i = $moveselectedonpage; $i > $numpages; $i--) {
            $questions[] = 0;
            $pagebreakpositions[] = count($questions) - 1;
        }
        $moveselectedpos = $pagebreakpositions[$moveselectedonpage - 1];

        // Do the move.
        array_splice($questions, $moveselectedpos, 0, $selectedquestionids);
        $offlinequiz->questions = implode(',', $questions);

        // Update the database.
        offlinequiz_save_questions($offlinequiz);
        $deletepreviews = true;
    }

    if ($copyselectedtogroup) {
        if (($selectedquestionids) && ($newgroup = offlinequiz_get_group($offlinequiz, $copyselectedtogroup))) {
            $currentgroupid = $offlinequiz->groupid;
            $currentquestions = $offlinequiz->questions;

            $offlinequiz->groupid = $newgroup->id;
            $groupquestions = offlinequiz_get_group_questions($offlinequiz);
            $purequestions = offlinequiz_questions_in_offlinequiz($groupquestions);
            $offlinequiz->questions = offlinequiz_clean_layout($groupquestions, empty($purequestions));

            offlinequiz_add_questionlist_to_group($selectedquestionids, $offlinequiz);

            $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);

            $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
            offlinequiz_delete_template_usages($offlinequiz);

            $offlinequiz->groupid = $currentgroupid;
            $offlinequiz->questions = $currentquestions;
        }
    }

    // Copy all question of the current group to another group
    if ($copytogrouptop) {
        if ($newgroup = offlinequiz_get_group($offlinequiz, $copytogrouptop)) {
            $currentgroupid = $offlinequiz->groupid;
            $currentquestions = $offlinequiz->questions;
            $questionstoadd = offlinequiz_get_group_questions($offlinequiz, 0, true);
            $currentquestionids = explode(',', $questionstoadd);

            $offlinequiz->groupid = $newgroup->id;
            $groupquestions = offlinequiz_get_group_questions($offlinequiz, 0, true);
            $purequestions = offlinequiz_questions_in_offlinequiz($groupquestions);
            $offlinequiz->questions = offlinequiz_clean_layout($groupquestions, empty($purequestions));

            offlinequiz_add_questionlist_to_group($currentquestionids, $offlinequiz);

            $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);

            $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
            offlinequiz_delete_template_usages($offlinequiz);

            $offlinequiz->groupid = $currentgroupid;
            $offlinequiz->questions = $currentquestions;
        }
    }
    // Copy all question of the current group to another group
    if ($copytogroupbottom) {
        if ($newgroup = offlinequiz_get_group($offlinequiz, $copytogroupbottom)) {
            $currentgroupid = $offlinequiz->groupid;
            $currentquestions = $offlinequiz->questions;
            $questionstoadd = offlinequiz_get_group_questions($offlinequiz, 0, true);
            $currentquestionids = explode(',', $questionstoadd);

            $offlinequiz->groupid = $newgroup->id;
            $groupquestions = offlinequiz_get_group_questions($offlinequiz, 0, true);
            $purequestions = offlinequiz_questions_in_offlinequiz($groupquestions);
            $offlinequiz->questions = offlinequiz_clean_layout($groupquestions, empty($purequestions));

            offlinequiz_add_questionlist_to_group($currentquestionids, $offlinequiz);

            $offlinequiz->grades = offlinequiz_get_all_question_grades($offlinequiz);

            $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
            offlinequiz_delete_template_usages($offlinequiz);

            $offlinequiz->groupid = $currentgroupid;
            $offlinequiz->questions = $currentquestions;
        }
    }

    // If rescaling is required save the new maximum
    $maxgrade = optional_param('maxgrade', -1, PARAM_FLOAT);
    if ($maxgrade >= 0) {
        offlinequiz_set_grade($maxgrade, $offlinequiz);
    }

    if ($deletepreviews) {
        offlinequiz_delete_template_usages($offlinequiz);
    }
    if ($recomputesummarks) {
        $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
        offlinequiz_update_all_attempt_sumgrades($offlinequiz);
        // NOTE: We don't need this because we don't have a module-specific grade table
        // offlinequiz_update_all_final_grades($offlinequiz);
        offlinequiz_update_grades($offlinequiz, 0, true);
    }
    // redirect($afteractionurl);
}

$questionbank->process_actions($thispageurl, $cm);

// End of command processing =====================================================.
$PAGE->requires->skip_link_to('questionbank',
        get_string('skipto', 'access', get_string('questionbank', 'question')));
$PAGE->requires->skip_link_to('offlinequizcontentsblock',
        get_string('skipto', 'access', get_string('questionsinthisofflinequiz', 'offlinequiz')));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$node = $PAGE->settingsnav->find('mod_offlinequiz_edit', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
// $PAGE->requires->css('/mod/offlinequiz/styles.css');

echo $OUTPUT->header();

// Initialise the JavaScript.
$offlinequizeditconfig = new stdClass();
$offlinequizeditconfig->url = $thispageurl->out(true, array('qbanktool' => '0'));
$offlinequizeditconfig->dialoglisteners = array();
$numberoflisteners = max(offlinequiz_number_of_pages($offlinequiz->questions), 1);
for ($pageiter = 1; $pageiter <= $numberoflisteners; $pageiter++) {
    $offlinequizeditconfig->dialoglisteners[] = 'addrandomdialoglaunch_' . $pageiter;
}
$PAGE->requires->data_for_js('offlinequiz_edit_config', $offlinequizeditconfig);
$PAGE->requires->js('/question/qengine.js');
$module = array(
    'name'      => 'mod_offlinequiz_edit',
    'fullpath'  => '/mod/offlinequiz/edit.js',
    'requires'  => array('yui2-dom', 'yui2-event', 'yui2-container'),
    'strings'   => array(),
    'async'     => false,
);
$PAGE->requires->js_init_call('offlinequiz_edit_init', null, false, $module);

// Print the tabs to switch mode.
$currenttab = 'editq';

// See if we are in re-order mode or in edit mode.
if ($offlinequiz_reordertool > -1) {
    $thispageurl->param('reordertool', $offlinequiz_reordertool);
    set_user_preference('offlinequiz_reordertab', $offlinequiz_reordertool);
} else {
    $offlinequiz_reordertool = get_user_preferences('offlinequiz_reordertab', 0);
}

if ($offlinequiz_reordertool) {
    $mode = 'reorder';
} else {
    $mode = 'edit';
}

require_once('tabs.php');

// See if the user wants to see the questionbank or not.
if ($offlinequiz_qbanktool > -1) {
    $thispageurl->param('qbanktool', $offlinequiz_qbanktool);
    set_user_preference('offlinequiz_qbanktool_open', $offlinequiz_qbanktool);
} else {
    $offlinequiz_qbanktool = get_user_preferences('offlinequiz_qbanktool_open', 1);
}


if ($offlinequiz_qbanktool) {
    $bankclass = '';
    $offlinequizcontentsclass = '';
} else {
    $bankclass = 'collapsed ';
    $offlinequizcontentsclass = 'offlinequizwhenbankcollapsed';
}

echo '<div class="questionbankwindow ' . $bankclass . 'block">';
echo '<div class="header"><div class="title"><h2>';
echo get_string('questionbankcontents', 'offlinequiz') .
' <a href="' . $thispageurl->out(true, array('qbanktool' => '1')) .
'" id="showbankcmd">[' . get_string('show').
']</a>
<a href="' . $thispageurl->out(true, array('qbanktool' => '0')) .
'" id="hidebankcmd">[' . get_string('hide').
']</a>';
echo '</h2></div></div><div class="content">';

echo '<span id="questionbank"></span>';
echo '<div class="container">';
echo ' <div id="module" class="module">';
echo '  <div class="bd">';

// Display question bank.
$questionbank->display('editq',
        $pagevars['qpage'],
        $pagevars['qperpage'],
        $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
        $pagevars['qbshowtext']);

echo '  </div>';
echo ' </div>';
echo '</div>';

echo '</div></div>';

echo '<div class="offlinequizcontents ' . $offlinequizcontentsclass . '" id="offlinequizcontentsblock">';

if ($docscreated) {
    echo "<div class=\"noticebox infobox\">\n";
    echo " <a href=\"createquiz.php?mode=createpdfs&amp;q=$offlinequiz->instance\">" .
    get_string('formsexist', 'offlinequiz')."</a><br />" .
    get_string("attemptsexist", "offlinequiz")."<br />".get_string("regradinginfo", "offlinequiz");
    echo "</div><br />\n";
}

if ($offlinequiz->shufflequestions || $offlinequiz->docscreated || $hasscannedpages) {
    $repaginatingdisabledhtml = 'disabled="disabled"';
    $repaginatingdisabled = true;
    $offlinequiz->questions = offlinequiz_clean_layout(offlinequiz_repaginate($offlinequiz->questions, $offlinequiz->questionsperpage), true);
} else {
    $repaginatingdisabledhtml = '';
    $repaginatingdisabled = false;
}
if ($offlinequiz_reordertool) {
    echo '<div class="repaginatecommand"><button id="repaginatecommand" ' .
            $repaginatingdisabledhtml.'>'.
            get_string('repaginatecommand', 'offlinequiz').'...</button>';
    echo '</div>';
}

// Compute the offlinequiz group letters.
$letterstr = 'ABCDEFGHIJKL';
$groupletters = array();
$groupoptions = array();

for ($i=1; $i<=$offlinequiz->numgroups; $i++) {
    $groupletters[$i] = $letterstr[$i-1];
    $groupoptions[$i] = get_string('questionsingroup', 'offlinequiz') . ' ' . $letterstr[$i-1];
}

if ($offlinequiz_reordertool) {
    echo $OUTPUT->heading_with_help(get_string('orderingofflinequiz', 'offlinequiz') . ': ' . $offlinequiz->name. ' (' .
            get_string('group', 'offlinequiz') . ' ' . $groupletters[$offlinequiz->groupnumber] . ')', 'orderandpaging', 'quiz');
} else {
    echo $OUTPUT->heading(get_string('editingofflinequiz', 'offlinequiz') . ': ' . $offlinequiz->name . ' (' .
            get_string('group') . ' ' . $groupletters[$offlinequiz->groupnumber] . ')', 2);
    // echo $OUTPUT->help_icon('editingofflinequiz', 'offlinequiz', get_string('basicideasofofflinequiz', 'offlinequiz'));
}

/* Print the group choice select */

$groupurl = $thispageurl;

echo '<br/><br/>';
echo "<div class=\"groupchoice\">";
echo $OUTPUT->single_select($groupurl, 'groupnumber', $groupoptions, $offlinequiz->groupnumber, array(), 'groupmenu123');
echo '</div><br/>';
/*---------------------------*/

offlinequiz_print_status_bar($offlinequiz);

$tabindex = 0;
offlinequiz_print_grading_form($offlinequiz, $thispageurl, $tabindex);

$notifystrings = array();
if ($hasscannedpages) {
    $reviewlink = offlinequiz_attempt_summary_link_to_reports($offlinequiz, $cm, $contexts->lowest());
    $notifystrings[] = get_string('cannoteditafterattempts', 'offlinequiz', $reviewlink);
}

if ($offlinequiz->shufflequestions) {
    $updateurl = new moodle_url("$CFG->wwwroot/course/mod.php",
            array('return' => 'true', 'update' => $offlinequiz->cmid, 'sesskey' => sesskey()));
    $updatelink = '<a href="'.$updateurl->out().'">' . get_string('updatethis', '',
            get_string('modulename', 'offlinequiz')) . '</a>';
    $notifystrings[] = get_string('shufflequestionsselected', 'offlinequiz', $updatelink);
}
if (!empty($notifystrings)) {
    echo $OUTPUT->box('<p>' . implode('</p><p>', $notifystrings) . '</p>', 'statusdisplay');
}

if ($offlinequiz_reordertool) {
    $perpage = array();
    $perpage[0] = get_string('allinone', 'offlinequiz');
    for ($i = 1; $i <= 50; ++$i) {
        $perpage[$i] = $i;
    }
    $gostring = get_string('go');
    echo '<div id="repaginatedialog"><div class="hd">';
    echo get_string('repaginatecommand', 'offlinequiz');
    echo '</div><div class="bd">';
    echo '<form action="edit.php" method="post">';
    echo '<fieldset class="invisiblefieldset">';
    echo html_writer::input_hidden_params($thispageurl);
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    // YUI does not submit the value of the submit button so we need to add the value.
    echo '<input type="hidden" name="repaginate" value="'.$gostring.'" />';
    $attributes = array();
    $attributes['disabled'] = $repaginatingdisabledhtml ? 'disabled' : null;
    $select = html_writer::select(
            $perpage, 'questionsperpage', $offlinequiz->questionsperpage, null, $attributes);
    print_string('repaginate', 'offlinequiz', $select);
    echo '<div class="offlinequizquestionlistcontrols">';
    echo ' <input type="submit" name="repaginate" value="'. $gostring . '" ' .
            $repaginatingdisabledhtml.' />';
    echo '</div></fieldset></form></div></div>';
}

// Display the list of questions in the offlinequiz group.
if ($offlinequiz_reordertool) {
    echo '<div class="reorder">';
} else {
    echo '<div class="editq">';

}
offlinequiz_print_question_list($offlinequiz, $thispageurl, true,
        $offlinequiz_reordertool, $offlinequiz_qbanktool, $docscreated, $defaultcategoryobj);

echo '</div>';

// Close <div class="offlinequizcontents">.
echo '</div>';

echo $OUTPUT->footer();
