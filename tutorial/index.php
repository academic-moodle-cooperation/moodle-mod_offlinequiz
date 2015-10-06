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
 * A simple tutorial for offline quizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../config.php");
require_once("../locallib.php");

$id   = optional_param('id', 0, PARAM_INT); // Course Module ID, or.
$page = optional_param('page', 1, PARAM_INT);  // offlinequiz ID.

if ($id) {
    if (! $cm = get_coursemodule_from_id('offlinequiz', $id)) {
        print_error("There is no coursemodule with id $id");
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error("Course is misconfigured");
    }

    if (! $offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
        print_error("The offlinequiz with id $cm->instance corresponding to this coursemodule $id is missing");
    }
}

// Check login.
if (!empty($course)) {
    require_login($course->id, false, $cm);
} else {
    require_login();
}

offlinequiz_load_useridentification();
$offlinequizconfig = get_config('offlinequiz');

$usernumber = substr($USER->{$offlinequizconfig->ID_field}, strlen($offlinequizconfig->ID_prefix), $offlinequizconfig->ID_digits);

if (strlen($usernumber) < $offlinequizconfig->ID_digits || !intval($usernumber)) {
    $usernumber = substr('0000000000000000000', 0, $offlinequizconfig->ID_digits);
}

for ($i = 0; $i < strlen($usernumber); $i++) {
    if (!is_numeric($usernumber{$i})) {
        $usernumber{$i} = 0;
    }
}

$url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/tutorial/index.php',
        array('id' => $id, 'page' => $page));
$PAGE->set_url($url);

if (!empty($offlinequiz)) {
    $PAGE->set_context(context_module::instance($cm->id));
    $PAGE->set_title(format_string($offlinequiz->name));
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string("tutorial", "offlinequiz"));
}
if (!empty($course)) {
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_heading(get_string("tutorial", "offlinequiz"));
}

$PAGE->set_pagelayout('report');


// Print the header.
$navlinks = array();
$strtutorial = get_string("tutorial", "offlinequiz");
if (empty($offlinequiz)) {
    $PAGE->navbar->add($strtutorial);
}

echo $OUTPUT->header();

echo $OUTPUT->heading($strtutorial, 2);

$lang = current_language();

if (!file_exists($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/page-$page.html")) {
    $lang = 'en';
}

echo '<table cellspacing=4 cellpadding=10 border=0>
          <tr><td width="200px" valign="top">';
require($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/menu.html");
echo '</td><td width="400px">';

$answer = optional_param('answer', null, PARAM_RAW);
if (!empty($answer)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'feedback');
    if ($page == 2 or $page == 3 or $page == 4) {
        include($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/feedback-$page-$answer.html");
    } else if ($page == 5) {
        if ($answer == $usernumber) {
            include($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/feedback-5-1.html");
        } else {
            include($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/feedback-5-2.html");
        }
    }
    echo $OUTPUT->box_end();
}

require($CFG->dirroot."/mod/offlinequiz/tutorial/$lang/page-$page.html");
echo '</td></tr></table>';

// Finish the page.
echo $OUTPUT->footer();
