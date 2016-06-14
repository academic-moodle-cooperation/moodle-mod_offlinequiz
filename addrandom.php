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
 * Fallback page of /mod/offlinequiz/edit.php add random question dialog,
 * for users who do not use javascript.
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
require_once($CFG->dirroot . '/mod/offlinequiz/addrandomform.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/category_class.php');

list($thispageurl, $contexts, $cmid, $cm, $offlinequiz, $pagevars) =
        question_edit_setup('editq', '/mod/offlinequiz/addrandom.php', true);

// These params are only passed from page request to request while we stay on
// this page otherwise they would go in question_edit_setup.
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$addonpage = optional_param('addonpage', 0, PARAM_INT);
$category = optional_param('category', 0, PARAM_INT);
$scrollpos = optional_param('scrollpos', 0, PARAM_INT);
$groupnumber = optional_param('groupnumber', 1, PARAM_INT);

// Get the course object and related bits.
if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
    print_error('invalidcourseid');
}
// You need mod/offlinequiz:manage in addition to question capabilities to access this page.
// You also need the moodle/question:useall capability somewhere.
require_capability('mod/offlinequiz:manage', $contexts->lowest());
if (!$contexts->having_cap('moodle/question:useall')) {
    print_error('nopermissions', '', '', 'use');
}

if ($groupnumber === -1 and !empty($SESSION->question_pagevars['groupnumber'])) {
    $groupnumber = $SESSION->question_pagevars['groupnumber'];
}

if ($groupnumber === -1) {
    $groupnumber = 1;
}

$offlinequiz->groupnumber = $groupnumber;

// Load the offlinequiz group and set the groupid in the offlinequiz object.
if ($offlinequizgroup = offlinequiz_get_group($offlinequiz, $groupnumber)) {
    $offlinequiz->groupid = $offlinequizgroup->id;
    //$groupquestions = offlinequiz_get_group_question_ids($offlinequiz);
    // Clean layout. Remove empty pages if there are no questions in the offlinequiz group.
    //$offlinequiz->questions = $groupquestions;
} else {
    print_error('invalidgroupnumber', 'offlinequiz');
}


if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/mod/offlinequiz/edit.php',
            array('cmid' => $cmid,
                  'groupnumber' => $offlinequiz->groupnumber
            ));
}
if ($scrollpos) {
    $returnurl->param('scrollpos', $scrollpos);
}

$thispageurl->param('groupnumber', $offlinequiz->groupnumber);
$PAGE->set_url($thispageurl);

$defaultcategoryobj = question_make_default_categories($contexts->all());
$defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;

$qcobject = new question_category_object(
    $pagevars['cpage'],
    $thispageurl,
    $contexts->having_one_edit_tab_cap('categories'),
    $defaultcategoryobj->id,
    $defaultcategory,
    null,
    $contexts->having_cap('moodle/question:add'));

$mform = new offlinequiz_add_random_form(new moodle_url('/mod/offlinequiz/addrandom.php'),
                array('contexts' => $contexts,
                      'cat' => $pagevars['cat'],
                      'groupnumber'=> $offlinequiz->groupnumber
                ));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    if (!empty($data->existingcategory)) {
        list($categoryid) = explode(',', $data->category);
        $includesubcategories = !empty($data->includesubcategories);
        $preventsamequestion = !empty($data->preventsamequestion);
        $returnurl->param('cat', $data->category);

    } else if (!empty($data->newcategory)) {
        list($parentid, $contextid) = explode(',', $data->parent);
        $categoryid = $qcobject->add_category($data->parent, $data->name, '', true);
        $preventsamequestion = !empty($data->preventsamequestion);
        $includesubcategories = 0;

        $returnurl->param('cat', $categoryid . ',' . $contextid);
    } else {
        throw new coding_exception(
                'It seems a form was submitted without any button being pressed???');
    }

    offlinequiz_add_random_questions($offlinequiz, $offlinequizgroup, $categoryid, $data->numbertoadd, $includesubcategories, $preventsamequestion);
    offlinequiz_delete_template_usages($offlinequiz);
    offlinequiz_update_sumgrades($offlinequiz);
    redirect($returnurl);
}

$mform->set_data(array(
    'addonpage' => $addonpage,
    'returnurl' => $returnurl,
    'cmid' => $cm->id,
    'category' => $category,
));

// Setup $PAGE.
$streditingofflinequiz = get_string('editinga', 'moodle', get_string('modulename', 'offlinequiz'));
$PAGE->navbar->add($streditingofflinequiz);
$PAGE->set_title($streditingofflinequiz);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (!$offlinequizname = $DB->get_field($cm->modname, 'name', array('id' => $cm->instance))) {
            print_error('invalidcoursemodule');
}
$groupletters = 'ABCDEFGHIJKL';
echo $OUTPUT->heading(get_string('addrandomquestiontoofflinequiz', 'offlinequiz',
        array('name' => $offlinequizname, 'group' => $groupletters[$offlinequiz->groupnumber - 1])), 2);
$mform->display();
echo $OUTPUT->footer();

