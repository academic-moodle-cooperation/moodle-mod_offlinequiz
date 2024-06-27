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
 * The students tutorial how to cross out
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <thomas.wedekind@univie.ac.at>
 * @copyright     2024 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 4.4+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once('locallib.php');

$id = optional_param('id', 0, PARAM_INT);
if (!$cm = get_coursemodule_from_id('offlinequiz', $id)) {
    throw new \moodle_exception('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new \moodle_exception('coursemisconf');
}
if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
    throw new \moodle_exception('invalidcoursemodule');
}
require_login($course, false, $cm);
$page = optional_param('page', 1, PARAM_INT);
$answer = optional_param('answer', 0, PARAM_TEXT);
$thisurl = new moodle_url('/mod/offlinequiz/tutorial.php',['page' => $page,'answer' => $answer, 'id' => $id]);

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

$usernumber = substr($USER->{$offlinequizconfig->ID_field}, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);
$generatedusernumber = false;
if (!intval($usernumber)) {
    // The user doesn't have an idnumber, let's generate a random one.
    if(property_exists($SESSION,'offlinequiztutorialusernumber')) {
        $usernumber = $SESSION->offlinequiztutorialusernumber;
    } else {
        $usernumber = random_int(0, str_repeat('9', $offlinequizconfig->ID_digits)) . '';
        while(strlen($usernumber) < $offlinequizconfig->ID_digits) {
            $usernumber = '0' . $usernumber;
        }
        $SESSION->offlinequiztutorialusernumber = $usernumber;
    }
    $generatedusernumber = true;
} else if(strlen($usernumber) < $offlinequizconfig->ID_digits) {
    $usernumber = substr(round($usernumber) . '0000000000000000000', 0, $offlinequizconfig->ID_digits);
}
$unarray = [];
for ($i=0; $i<strlen($usernumber); $i++) {
    $unarray[$i]['digit'] = substr($usernumber,$i,1);
    $unarray[$i]['digitcountc'] = $i+1;
}
$digitsarray = [];
for ($i=0; $i<=9; $i++) {
    $digitsarray[$i]['digitcountr'] = $i;
}

$correctanswers = [
    1 => 2, 
    2 => 2,
    3 => 3,
    4 => $usernumber,
];
$notification = '';

if ($answer) {
    $a = [ 'correctusernumber' => $usernumber,
        'selectedusernumber' => $answer];

    if($page < 4) {
        $feedbackstring = get_string('tutorial:feedback:' . $page . ':' . $answer, 'offlinequiz');
    } else {
        if($correctanswers[$page] == $answer) {
            $feedbackstring = get_string('tutorial:feedback:4:1', 'offlinequiz');
        } else {
            $feedbackstring = get_string('tutorial:feedback:4:0', 'offlinequiz', $a);
        }
    }
    if ($correctanswers[$page] == $answer) {
        $feedbackstring = '<b>' . get_string('tutorial:feedback:correct', 'offlinequiz') . ' ' . $feedbackstring;
        $notification = $OUTPUT->notification($feedbackstring, 'notifysuccess');
        //The user guessed right, we show him the next page
        $page = $page+1;
    } else {
        $feedbackstring = '<b>' . get_string('tutorial:feedback:wrong', 'offlinequiz') . '</b> ' . $feedbackstring;
        $notification = $OUTPUT->notification($feedbackstring, 'notifyerror');
    }
    
}


$templatedata = [
    'usernumber' => $unarray,
    'id' => $id,
    'usernumberlength' => $offlinequizconfig->ID_digits,
    'generatedusernumber' => $generatedusernumber,
    'digitsarray' => $digitsarray,
    'notification' => $notification,
];


// Output of file
// --------------------------------- 
$PAGE->set_pagelayout('incourse');
$PAGE->set_pagetype('mod-offlinequiz-tutorial');
$PAGE->activityheader->disable();
$PAGE->force_settings_menu(true);
$PAGE->set_url($thisurl);

$PAGE->set_title(get_string('tutorial:title', 'offlinequiz', format_string($offlinequiz->name)));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
$thisurl->remove_params(['page','answer']);
echo '<div class="container">';
echo "<h2>" . get_string('tutorial', 'offlinequiz') . "</h2>";

echo '<div class="row">';
echo $OUTPUT->render_from_template('mod_offlinequiz/tutorial_navigation',['url' => $thisurl->out()]);
echo $OUTPUT->render_from_template('mod_offlinequiz/tutorial_page-' . $page, $templatedata);
echo '</div></div>';
echo $OUTPUT->footer();