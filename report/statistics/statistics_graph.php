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
 * This script renders the offlinequiz statistics graph.
 *
 * It takes one parameter, the offlinequiz_statistics.id. This is enough to identify the
 * offlinequiz etc.
 *
 * It plots a bar graph showing certain question statistics plotted against
 * question number.
 *
 * @package   offlinequiz_statistics
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/graphlib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/reportlib.php');


/**
 * This helper function returns a sequence of colours each time it is called.
 * Used for chooseing colours for graph data series.
 * @return string colour name.
 */
function graph_get_new_colour() {
    static $colourindex = -1;
    $colours = array('red', 'green', 'yellow', 'orange', 'purple', 'black',
            'maroon', 'blue', 'ltgreen', 'navy', 'ltred', 'ltltgreen', 'ltltorange',
            'olive', 'gray', 'ltltred', 'ltorange', 'lime', 'ltblue', 'ltltblue');

    $colourindex = ($colourindex + 1) % count($colours);

    return $colours[$colourindex];
}

// Get the parameters.
$offlinequizstatisticsid = required_param('id', PARAM_INT);
$groupnumber = optional_param('group', -1, PARAM_INT);

// Load enough data to check permissions.
$offlinequizstatistics = $DB->get_record('offlinequiz_statistics', array('id' => $offlinequizstatisticsid));
$offlinequiz = $DB->get_record('offlinequiz', array('id' => $offlinequizstatistics->offlinequizid), '*', MUST_EXIST);

if ($groupnumber > 0) {
    if ($offlinegroup = offlinequiz_get_group($offlinequiz, $groupnumber)) {
        $offlinequiz->groupid = $offlinegroup->id;
        $groupquestions = offlinequiz_get_group_question_ids($offlinequiz);
        $offlinequiz->questions = $groupquestions;
    } else {
        print_error('invalidgroupnumber', 'offlinequiz');
    }
} else {
    $offlinequiz->groupid = 0;
    // If no group has been chosen we simply take the all questions.
    $sql = "SELECT DISTINCT(questionid)
              FROM {offlinequiz_group_questions}
             WHERE offlinequizid = :offlinequizid";
            
    $questionids = $DB->get_fieldset_sql($sql, array('offlinequizid' => $offlinequiz->id));
    $offlinequiz->questions = $questionids;
}

$cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);

// Check access.
require_login($offlinequiz->course, false, $cm);
$modcontext = context_module::instance($cm->id);
require_capability('offlinequiz/statistics:view', $modcontext);

if (groups_get_activity_groupmode($cm)) {
    $groups = groups_get_activity_allowed_groups($cm);
} else {
    $groups = array();
}
if ($offlinequizstatistics->groupid && !in_array($offlinequizstatistics->groupid, array_keys($groups))) {
    print_error('groupnotamember', 'group');
}

// Load the rest of the required data.
$questions = offlinequiz_report_get_significant_questions($offlinequiz);
$questionstatistics = $DB->get_records_select('offlinequiz_q_statistics',
        'offlinequizstatisticsid = ? AND slot IS NOT NULL', array($offlinequizstatistics->id));

// Create the graph, and set the basic options.
$graph = new graph(800, 600);
$graph->parameter['title']   = '';

$graph->parameter['y_label_left'] = '%';
$graph->parameter['x_label'] = get_string('position', 'offlinequiz_statistics');
$graph->parameter['y_label_angle'] = 90;
$graph->parameter['x_label_angle'] = 0;
$graph->parameter['x_axis_angle'] = 60;

$graph->parameter['legend'] = 'outside-right';
$graph->parameter['legend_border'] = 'black';
$graph->parameter['legend_offset'] = 4;

$graph->parameter['bar_size'] = 1;

$graph->parameter['zero_axis'] = 'grayEE';

// Configure what to display.
$fieldstoplot = array(
    'facility' => get_string('facility', 'offlinequiz_statistics'),
    'discriminativeefficiency' => get_string('discriminative_efficiency', 'offlinequiz_statistics')
);
$fieldstoplotfactor = array('facility' => 100, 'discriminativeefficiency' => 1);

// Prepare the arrays to hold the data.
$xdata = array();
foreach (array_keys($fieldstoplot) as $fieldtoplot) {
    $ydata[$fieldtoplot] = array();
    $graph->y_format[$fieldtoplot] = array(
        'colour' => graph_get_new_colour(),
        'bar' => 'fill',
        'shadow_offset' => 1,
        'legend' => $fieldstoplot[$fieldtoplot]
    );
}

// Fill in the data for each question.
foreach ($questionstatistics as $questionstatistic) {
    $number = $questions[$questionstatistic->slot]->number;
    $xdata[$number] = $number;

    foreach ($fieldstoplot as $fieldtoplot => $notused) {
        $value = $questionstatistic->$fieldtoplot;
        if (is_null($value)) {
            $value = 0;
        }
        $value *= $fieldstoplotfactor[$fieldtoplot];

        $ydata[$fieldtoplot][$number] = $value;
    }
}

// Sort the fields into order.
sort($xdata);
$graph->x_data = array_values($xdata);

foreach ($fieldstoplot as $fieldtoplot => $notused) {
    ksort($ydata[$fieldtoplot]);
    $graph->y_data[$fieldtoplot] = array_values($ydata[$fieldtoplot]);
}
$graph->y_order = array_keys($fieldstoplot);

// Find appropriate axis limits.
$max = 0;
$min = 0;
foreach ($fieldstoplot as $fieldtoplot => $notused) {
    $max = max($max, max($graph->y_data[$fieldtoplot]));
    $min = min($min, min($graph->y_data[$fieldtoplot]));
}

// Set the remaining graph options that depend on the data.
$gridresolution = 10;
$max = ceil($max / $gridresolution) * $gridresolution;
$min = floor($min / $gridresolution) * $gridresolution;
$gridlines = ceil(($max - $min) / $gridresolution) + 1;

$graph->parameter['y_axis_gridlines'] = $gridlines;

$graph->parameter['y_min_left'] = $min;
$graph->parameter['y_max_left'] = $max;
$graph->parameter['y_decimal_left'] = 0;

// Output the graph.
$graph->draw();
