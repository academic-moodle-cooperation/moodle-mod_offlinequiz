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
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/offlinequiz.class.php');
require_once($CFG->dirroot . '/mod/offlinequiz/addrandomform.php');
require_once($CFG->dirroot . '/question/category_class.php');

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$scrollpos = optional_param('scrollpos', '', PARAM_INT);

// Patch problem with nested forms and category parameter, otherwise question_edit_setup has problems.
if (array_key_exists('savechanges', $_POST) && $_POST['savechanges']) {
    unset($_POST['category']);
}
if (array_key_exists('offlinequizdeleteselected', $_POST) && $_POST['offlinequizdeleteselected']) {
    unset($_POST['category']);
}

list($thispageurl, $contexts, $cmid, $cm, $offlinequiz, $pagevars)
    = question_edit_setup('editq', '/mod/offlinequiz/edit.php', true);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

// See if we do bulk grade editing.
$offlinequizgradetool = optional_param('gradetool', -1, PARAM_BOOL);
if ($offlinequizgradetool > -1) {
    $thispageurl->param('gradetool', $offlinequizgradetool);
    set_user_preference('offlinequiz_gradetab', $offlinequizgradetool);
} else {
    $offlinequizgradetool = get_user_preferences('offlinequiz_gradetab', 0);
}

// Determine groupid.
$groupnumber    = optional_param('groupnumber', 1, PARAM_INT);
if ($groupnumber === -1 and !empty($SESSION->question_pagevars['groupnumber'])) {
    $groupnumber = $SESSION->question_pagevars['groupnumber'];
}

if ($groupnumber === -1) {
    $groupnumber = 1;
}

$offlinequiz->groupnumber = $groupnumber;
$thispageurl->param('groupnumber', $offlinequiz->groupnumber);

// Load the offlinequiz group and set the groupid in the offlinequiz object.
if ($offlinequizgroup = offlinequiz_get_group($offlinequiz, $groupnumber)) {
    $offlinequiz->groupid = $offlinequizgroup->id;
    $groupquestions = offlinequiz_get_group_question_ids($offlinequiz);
    $offlinequiz->questions = $groupquestions;
} else {
    print_error('invalidgroupnumber', 'offlinequiz');
}

$offlinequiz->sumgrades = $offlinequizgroup->sumgrades;

$offlinequizhasattempts = offlinequiz_has_scanned_pages($offlinequiz->id);
$docscreated = $offlinequiz->docscreated;

$PAGE->set_url($thispageurl);

// Get the course object and related bits.
$course = $DB->get_record('course', array('id' => $offlinequiz->course), '*', MUST_EXIST);
$offlinequizobj = new offlinequiz($offlinequiz, $cm, $course);
$structure = $offlinequizobj->get_structure();

if ($warning = optional_param('warning', '', PARAM_TEXT)) {
    $structure->add_warning(urldecode($warning));
}
require_login($course, false, $cm);
// You need mod/offlinequiz:manage in addition to question capabilities to access this page.
require_capability('mod/offlinequiz:manage', $contexts->lowest());

// Log this visit.
$params = array(
    'courseid' => $course->id,
    'context' => $contexts->lowest(),
    'other' => array(
        'offlinequizid' => $offlinequiz->id,
    )
);
$event = \mod_offlinequiz\event\edit_page_viewed::create($params);
$event->trigger();

// Process commands ============================================================.

// Get the list of question ids had their check-boxes ticked.
$selectedslots = array();
$params = (array) data_submitted();
foreach ($params as $key => $value) {
    if (preg_match('!^s([0-9]+)$!', $key, $matches)) {
        $selectedslots[] = $matches[1];
    }
}

$afteractionurl = new moodle_url($thispageurl);
if ($scrollpos) {
    $afteractionurl->param('scrollpos', $scrollpos);
}

// Get the list of question ids had their check-boxes ticked.
$selectedquestionids = array();
$params = (array) data_submitted();
foreach ($params as $key => $value) {
    if (preg_match('!^s([0-9]+)$!', $key, $matches)) {
        $selectedquestionids[] = $matches[1];
    }
}

if (optional_param('offlinequizdeleteselected', false, PARAM_BOOL) &&
        !empty($selectedquestionids) && confirm_sesskey()) {

    offlinequiz_remove_questionlist($offlinequiz, $selectedquestionids);
    offlinequiz_delete_template_usages($offlinequiz);
    $offlinequiz->sumgrades = offlinequiz_update_sumgrades($offlinequiz);
    redirect($afteractionurl);
}

if (optional_param('repaginate', false, PARAM_BOOL) && confirm_sesskey()) {
    // Re-paginate the offlinequiz.
    $structure->check_can_be_edited();
    $questionsperpage = optional_param('questionsperpage', $offlinequiz->questionsperpage, PARAM_INT);
    offlinequiz_repaginate_questions($offlinequiz->id, $offlinequiz->groupid, $questionsperpage );
    offlinequiz_delete_template_usages($offlinequiz);
    redirect($afteractionurl);
}

if (($addquestion = optional_param('addquestion', 0, PARAM_INT)) && confirm_sesskey()) {
    // Add a single question to the current offlinequiz.
    $structure->check_can_be_edited();
    offlinequiz_require_question_use($addquestion);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    // If the question is already in another group, take the maxmark of that.
    if ($maxmarks = $DB->get_fieldset_select('offlinequiz_group_questions', 'maxmark',
            'offlinequizid = :offlinequizid AND questionid = :questionid',
            array('offlinequizid' => $offlinequiz->id, 'questionid' => $addquestion))) {
        offlinequiz_add_offlinequiz_question($addquestion, $offlinequiz, $addonpage, $maxmarks[0]);
    } else {
        offlinequiz_add_offlinequiz_question($addquestion, $offlinequiz, $addonpage);
    }
    offlinequiz_delete_template_usages($offlinequiz);
    offlinequiz_update_sumgrades($offlinequiz);
    $thispageurl->param('lastchanged', $addquestion);
    redirect($afteractionurl);
}

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $structure->check_can_be_edited();
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    // Add selected questions to the current offlinequiz.
    $rawdata = (array) data_submitted();
    foreach ($rawdata as $key => $value) { // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $key = $matches[1];
            offlinequiz_require_question_use($key);
            // If the question is already in another group, take the maxmark of that.
            if ($maxmarks = $DB->get_fieldset_select('offlinequiz_group_questions', 'maxmark',
                    'offlinequizid = :offlinequizid AND questionid = :questionid',
                    array('offlinequizid' => $offlinequiz->id, 'questionid' => $key))) {
                offlinequiz_add_offlinequiz_question($key, $offlinequiz, $addonpage, $maxmarks[0]);
            } else {
                offlinequiz_add_offlinequiz_question($key, $offlinequiz, $addonpage);
            }
        }
    }
    offlinequiz_delete_template_usages($offlinequiz);
    offlinequiz_update_sumgrades($offlinequiz);
    redirect($afteractionurl);
}

if ((optional_param('addrandom', false, PARAM_BOOL)) && confirm_sesskey()) {
    // Add random questions to the offlinequiz.
    $structure->check_can_be_edited();
    $recurse = optional_param('recurse', 0, PARAM_BOOL);
    $preventsamequestion = optional_param('preventsamequestion', 0, PARAM_BOOL);
    $addonpage = optional_param('addonpage', 0, PARAM_INT);
    $categoryid = required_param('categoryid', PARAM_INT);
    $randomcount = required_param('randomcount', PARAM_INT);

    offlinequiz_add_random_questions($offlinequiz, $addonpage, $categoryid, $randomcount, $recurse, $preventsamequestion);

    offlinequiz_delete_template_usages($offlinequiz);
    offlinequiz_update_sumgrades($offlinequiz);
    redirect($afteractionurl);
}

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {

    // Parameter to copy selected questions to another group.
    $copyselectedtogroup = optional_param('copyselectedtogrouptop', 0, PARAM_INT);

    if ($copyselectedtogroup) {

        if (($selectedquestionids) && ($newgroup = offlinequiz_get_group($offlinequiz, $copyselectedtogroup))) {
            $fromofflinegroup = optional_param('fromofflinegroup', 0, PARAM_INT);

            offlinequiz_add_questionlist_to_group($selectedquestionids, $offlinequiz, $newgroup, $fromofflinegroup);

            offlinequiz_update_sumgrades($offlinequiz, $newgroup->id);
            // Delete the templates, just to be sure.
            offlinequiz_delete_template_usages($offlinequiz);
        }
        redirect($afteractionurl);
    }

    // If rescaling is required save the new maximum.
    $maxgrade = str_replace(',', '.', optional_param('maxgrade', -1, PARAM_RAW));
    if (!is_numeric( $maxgrade)) {
        $afteractionurl->param('warning', urlencode(get_string('maxgradewarning', 'offlinequiz')));
    } else {
        $maxgrade = unformat_float($maxgrade);
        if ($maxgrade >= 0) {
            offlinequiz_set_grade($maxgrade, $offlinequiz);
            offlinequiz_update_grades($offlinequiz, 0, true);
        }
    }

    redirect($afteractionurl);
}

$savegrades = optional_param('savegrades', '', PARAM_ALPHA);

if ($savegrades == 'bulksavegrades' && confirm_sesskey()) {
    $rawdata = (array) data_submitted();

    foreach ($rawdata as $key => $value) {
        if (preg_match('!^g([0-9]+)$!', $key, $matches)) {
            if (is_numeric(str_replace(',', '.', $value))) {
                // Parse input for question -> grades.
                $questionid = $matches[1];
                offlinequiz_update_question_instance($offlinequiz, $questionid, unformat_float($value));
            } else {
                $bulkgradewarning = true;
            }
        }
    }

    // Redmine 983: Upgrade sumgrades for all offlinequiz groups.
    if ($groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number',
            '*', 0, $offlinequiz->numgroups)) {
        foreach ($groups as $group) {
            $sumgrade = offlinequiz_update_sumgrades($offlinequiz, $group->id);
        }
    }

    offlinequiz_update_all_attempt_sumgrades($offlinequiz);
    offlinequiz_update_grades($offlinequiz, 0, true);
    redirect($afteractionurl);
}

// Get the question bank view.
$questionbank = new mod_offlinequiz\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $offlinequiz);
$questionbank->set_offlinequiz_has_scanned_pages($docscreated);
$questionbank->process_actions($thispageurl, $cm);

// End of process commands =====================================================.

$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('mod-offlinequiz-edit');
$PAGE->force_settings_menu(true);
$output = $PAGE->get_renderer('mod_offlinequiz', 'edit');

$PAGE->set_title(get_string('editingofflinequizx', 'offlinequiz', format_string($offlinequiz->name)));
$PAGE->set_heading($course->fullname);
$node = $PAGE->settingsnav->find('mod_offlinequiz_edit', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
echo $OUTPUT->header();

// Initialise the JavaScript.
$offlinequizeditconfig = new stdClass();
$offlinequizeditconfig->url = $thispageurl->out(true, array('qbanktool' => '0'));
$offlinequizeditconfig->dialoglisteners = array();
$numberoflisteners = $DB->get_field_sql("
    SELECT COALESCE(MAX(page), 1)
      FROM {offlinequiz_group_questions}
     WHERE offlinequizid = ?
       AND offlinegroupid = ?", array($offlinequiz->id, $offlinequiz->groupid));

for ($pageiter = 1; $pageiter <= $numberoflisteners; $pageiter++) {
    $offlinequizeditconfig->dialoglisteners[] = 'addrandomdialoglaunch_' . $pageiter;
}

$PAGE->requires->data_for_js('offlinequiz_edit_config', $offlinequizeditconfig);
$PAGE->requires->js('/question/qengine.js');

$currenttab = 'editq';
if ($offlinequizgradetool) {
    $mode = 'grade';
} else {
    $mode = 'edit';
}

require_once('tabs.php');

// Questions wrapper start.
if ($mode == 'grade') {
    echo html_writer::start_tag('div', array('class' => 'mod-offlinequiz-edit-content edit_grades'));
} else {
    echo html_writer::start_tag('div', array('class' => 'mod-offlinequiz-edit-content'));
}

$letterstr = 'ABCDEFGHIJKL';
$groupletters = array();

for ($i = 1; $i <= $offlinequiz->numgroups; $i++) {
    $groupletters[$i] = $letterstr[$i - 1];
}

if ($offlinequizgradetool) {
    echo $output->edit_grades_page($offlinequizobj, $structure, $contexts, $thispageurl, $pagevars, $groupletters);
} else {
    echo $output->edit_page($offlinequizobj, $structure, $contexts, $thispageurl, $pagevars, $groupletters);
}

// Questions wrapper end.
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
