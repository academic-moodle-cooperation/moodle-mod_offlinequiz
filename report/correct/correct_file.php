<?php
// This file is part of Moodle - http://moodle.org/
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
 * TODO describe file correct_file
 *
 * @package    offlinequiz_correct
 * @copyright  2026 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');

require_login();
use offlinequiz_correct\controller\dataloader;
$pageid = required_param('pageid', PARAM_INT);

$offlinequizid = $DB->get_field('offlinequiz_scanned_pages', 'offlinequizid', ['id' => $pageid]);

//TODO rights management
if (!$offlinequizid) {
    throw new \moodle_exception('invalidpageid', 'offlinequiz_correct');
}
if (!$offlinequiz = $DB->get_record('offlinequiz', ['id' => $offlinequizid])) {
    throw new \moodle_exception('invalidofflinequizid', 'offlinequiz');
}
if (!$course = $DB->get_record('course', ['id' => $offlinequiz->course])) {
    throw new \moodle_exception('invalidcourseid');
}
if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
    throw new \moodle_exception('invalidcoursemodule');
}

$url = new moodle_url('/mod/offlinequiz/report/correct/correct_file.php', ['pageid' => $pageid]);
$correctinfo = [];
$nextpageid = 0;
$previouspageid = 0;
$PAGE->set_url($url);
$PAGE->set_context(context_module::instance($cm->id));
$PAGE->set_course($course);
$PAGE->set_cm($cm);

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$correctrenderer = $PAGE->get_renderer('offlinequiz_correct', 'correct');
$dataloader = new dataloader($pageid);
$data = $dataloader->get_data();
$correctrenderer->render_correct_page($offlinequiz, $cm, $data);
echo $OUTPUT->footer();
