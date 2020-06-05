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
 * Internal library of functions for statisticstables
 *
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function mod_offlinequiz_create_common_statistics_headers(&$headers, &$columns, $s) {
    $columns[] = 's';
    $headers[] = get_string('attempts', 'offlinequiz_statistics');

    if ($s > 1) {
        $columns[] = 'facility';
        $headers[] = get_string('facility', 'offlinequiz_statistics');

        $columns[] = 'sd';
        $headers[] = get_string('standarddeviationq', 'offlinequiz_statistics');
    }

    $columns[] = 'intended_weight';
    $headers[] = get_string('intended_weight', 'offlinequiz_statistics');

    $columns[] = 'effective_weight';
    $headers[] = get_string('effective_weight', 'offlinequiz_statistics');

    $columns[] = 'discrimination_index';
    $headers[] = get_string('discrimination_index', 'offlinequiz_statistics');

    // Redmine 1302: New table columns s.t. the data can be exported.
    $columns[] = 'correct';
    $headers[] = get_string('correct', 'offlinequiz_statistics');
    $columns[] = 'partially';
    $headers[] = get_string('partially', 'offlinequiz_statistics');
    $columns[] = 'wrong';
    $headers[] = get_string('wrong', 'offlinequiz_statistics');
}

function mod_offlinequiz_print_column_stats_name($question, $baseurl, $name, $dubiousquestion) {
    $url = null;
    if ($question->_stats->subquestion) {
        $url = new moodle_url($baseurl, array('qid' => $question->id));
    } else if ($question->_stats->questionid && $question->qtype != 'random') {
        $url = new moodle_url($baseurl, array('questionid' => $question->_stats->questionid));
    }

    if ($url) {
        $name = html_writer::link($url, $name,
            array('title' => get_string('detailedanalysis', 'offlinequiz_statistics')));
    }

    if ($dubiousquestion) {
        $name = html_writer::tag('div', $name, array('class' => 'dubious'));
    }
    return $name;
}
