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
 * Rest endpoint for ajax editing of offline quiz structure.
 *
 * @package   mod_offlinequiz
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/offlinequiz.class.php');

// Initialise ALL the incoming parameters here, up front.
$offlinequizid     = required_param('offlinequizid', PARAM_INT);
$offlinegroupid = required_param('offlinegroupid', PARAM_INT);
$class      = required_param('class', PARAM_ALPHA);
$field      = optional_param('field', '', PARAM_ALPHA);
$instanceid = optional_param('instanceId', 0, PARAM_INT);
$sectionid  = optional_param('sectionId', 0, PARAM_INT);
$previousid = optional_param('previousid', 0, PARAM_INT);
$value      = optional_param('value', 0, PARAM_INT);
$column     = optional_param('column', 0, PARAM_ALPHA);
$id         = optional_param('id', 0, PARAM_INT);
$summary    = optional_param('summary', '', PARAM_RAW);
$sequence   = optional_param('sequence', '', PARAM_SEQUENCE);
$visible    = optional_param('visible', 0, PARAM_INT);
$pageaction = optional_param('action', '', PARAM_ALPHA); // Used to simulate a DELETE command.
$maxmark    = optional_param('maxmark', '', PARAM_RAW);
$page       = optional_param('page', '', PARAM_INT);
$PAGE->set_url('/mod/offlinequiz/edit-rest.php',
        array('offlinequizid' => $offlinequizid, 'class' => $class));

require_sesskey();

$offlinequiz = $DB->get_record('offlinequiz', array('id' => $offlinequizid), '*', MUST_EXIST);
if ($offlinequizgroup = $DB->get_record('offlinequiz_groups', array('id' => $offlinegroupid))) {
    $offlinequiz->groupid = $offlinequizgroup->id;
} else {
    print_error('invalidgroupnumber', 'offlinequiz');
}

$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $offlinequiz->course);
$course = $DB->get_record('course', array('id' => $offlinequiz->course), '*', MUST_EXIST);
require_login($course, false, $cm);

$offlinequizobj = new offlinequiz($offlinequiz, $cm, $course);
$structure = $offlinequizobj->get_structure();
$modcontext = context_module::instance($cm->id);

echo $OUTPUT->header(); // Send headers.

// OK, now let's process the parameters and do stuff
// MDL-10221 the DELETE method is not allowed on some web servers,
// so we simulate it with the action URL param.
$requestmethod = $_SERVER['REQUEST_METHOD'];
if ($pageaction == 'DELETE') {
    $requestmethod = 'DELETE';
}

switch($requestmethod) {
    case 'POST':
    case 'GET': // For debugging.

        switch ($class) {
            case 'section':
                break;

            case 'resource':
                switch ($field) {
                    case 'move':
                        require_capability('mod/offlinequiz:manage', $modcontext);
                        offlinequiz_delete_template_usages($offlinequiz);
                        $structure->move_slot($id, $previousid, $page);
                        echo json_encode(array('visible' => true));
                        break;

                    case 'getmaxmark':
                        require_capability('mod/offlinequiz:manage', $modcontext);
                        $slot = $DB->get_record('offlinequiz_group_questions', array('id' => $id), '*', MUST_EXIST);
                        echo json_encode(array('instancemaxmark'
                                => offlinequiz_format_question_grade($offlinequiz, $slot->maxmark)));
                        break;

                    case 'updatemaxmark':
                        require_capability('mod/offlinequiz:manage', $modcontext);
                        $slot = $structure->get_slot_by_id($id);
                        if (!is_numeric(str_replace(',', '.', $maxmark))) {
                            $summarks = $DB->get_field('offlinequiz_groups', 'sumgrades', array('id' => $offlinequizgroup->id));
                            echo json_encode(array('instancemaxmark' => offlinequiz_format_question_grade
                                             ($offlinequiz, $slot->maxmark),
                                            'newsummarks' => offlinequiz_format_grade($offlinequiz, $summarks)));

                            break;
                        }
                        if ($structure->update_slot_maxmark($slot, $maxmark)) {
                            // Recalculate the sumgrades for all groups.
                            if ($groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id),
                                'number', '*', 0, $offlinequiz->numgroups)) {
                                foreach ($groups as $group) {
                                    $sumgrade = offlinequiz_update_sumgrades($offlinequiz, $group->id);
                                }
                            }

                            // Grade has really changed.
                            offlinequiz_update_question_instance($offlinequiz, $slot->questionid, unformat_float($maxmark));
                            offlinequiz_update_all_attempt_sumgrades($offlinequiz);
                            offlinequiz_update_grades($offlinequiz, 0, true);
                        }
                        $newsummarks = $DB->get_field('offlinequiz_groups', 'sumgrades', array('id' => $offlinequizgroup->id));
                        echo json_encode(array('instancemaxmark' => offlinequiz_format_question_grade($offlinequiz, $slot->maxmark),
                                'newsummarks' => format_float($newsummarks, $offlinequiz->decimalpoints)));
                        break;
                    case 'updatepagebreak':
                        require_capability('mod/offlinequiz:manage', $modcontext);
                        offlinequiz_delete_template_usages($offlinequiz);
                        $slots = $structure->update_page_break($offlinequiz, $id, $value);
                        $json = array();
                        foreach ($slots as $slot) {
                            $json[$slot->slot] = array('id' => $slot->id, 'slot' => $slot->slot,
                                                            'page' => $slot->page);
                        }
                        echo json_encode(array('slots' => $json));
                        break;
                }
                break;

            case 'course':
                break;
        }
        break;

    case 'DELETE':
        switch ($class) {
            case 'resource':
                require_capability('mod/offlinequiz:manage', $modcontext);
                if (!$slot = $DB->get_record('offlinequiz_group_questions',
                        array('offlinequizid' => $offlinequiz->id, 'id' => $id))) {
                    throw new moodle_exception('AJAX commands.php: Bad slot ID '.$id);
                }
                $structure->remove_slot($offlinequiz, $slot->slot);
                offlinequiz_delete_template_usages($offlinequiz);
                offlinequiz_update_sumgrades($offlinequiz);
                echo json_encode(array('newsummarks' => offlinequiz_format_grade($offlinequiz, $offlinequiz->sumgrades),
                            'deleted' => true, 'newnumquestions' => $structure->get_question_count()));
                break;
        }
        break;
}
