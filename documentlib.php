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
 *
 * Functions for checking and evaluting scanned answer forms and lists of participants.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <thomas.wedekind@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function offlinequiz_get_question_infostring($offlinequiz, $question) {
    if ($offlinequiz->showgrades || $offlinequiz->showquestioninfo) {
        $infostr = '(';
        $questioninfo = offlinequiz_get_questioninfo($offlinequiz, $question);
        if ($questioninfo) {
            $infostr .= $questioninfo;
            if ($offlinequiz->showgrades) {
                $infostr .= ', ';
            }
        }

        if ($offlinequiz->showgrades) {
            $pointstr = get_string('points', 'grades');
            if ($question->maxmark == 1) {
                $pointstr = get_string('point', 'offlinequiz');
            }
            $infostr = $infostr . format_float($question->maxmark, $offlinequiz->decimalpoints) . ' '. $pointstr;
        }

        $infostr = $infostr . ')';
        return  $infostr;

    }
    return null;
}

function offlinequiz_get_questioninfo($offlinequiz, $question) {
    if ($offlinequiz->showquestioninfo == OFFLINEQUIZ_QUESTIONINFO_QTYPE) {
        if ($question->qtype == 'multichoice') {

            if ($question->options->single) {
                $questioninfo = get_string('singlechoice', 'offlinequiz');
            } else {
                $questioninfo = get_string('multichoice', 'offlinequiz');
            }
        } else if ($question->qtype == 'multichoiceset') {
            $questioninfo = get_string('allornothing', 'offlinequiz');
        }
        return $questioninfo;

    } else if ($offlinequiz->showquestioninfo == OFFLINEQUIZ_QUESTIONINFO_ANSWERS) {
        $amount = offlinequiz_get_amount_correct_answers($question);
        $questioninfo = $amount . ' ' . get_string('questioninfocorrectanswers', 'offlinequiz');
        if ($amount == 1) {
            $questioninfo = $amount . ' ' . get_string('questioninfocorrectanswer', 'offlinequiz', $amount);
        }
        return $questioninfo;
    } else {
        return null;
    }
}

function offlinequiz_get_amount_correct_answers($question) {
    $answers = $question->options->answers;
    $amount = 0;
    foreach ($answers as $answer) {
        if ($answer->fraction > 0) {
            $amount = $amount + 1;
        }
    }
    return $amount;
}