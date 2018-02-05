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
 * Creates the PDF forms for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/filter/tex/filter.php');
require_once($CFG->dirroot . '/mod/offlinequiz/html2text.php');

/**
 * Generates the LaTeX question form for an offlinequiz group.
 *
 * @param question_usage_by_activity $templateusage the template question  usage for this offline group
 * @param object $offlinequiz The offlinequiz object
 * @param object $group the offline group object
 * @param int $courseid the ID of the Moodle course
 * @param object $context the context of the offline quiz.
 * @param boolean correction if true the correction form is generated.
 * @return stored_file instance, the generated PDF file.
 */
function offlinequiz_create_latex_question(question_usage_by_activity $templateusage, $offlinequiz, $group,
                                         $courseid, $context, $correction = false) {
    global $CFG, $DB, $OUTPUT;

    $letterstr = 'abcdefghijklmnopqrstuvwxyz';
    $groupletter = strtoupper($letterstr[$group->number - 1]);

    $coursecontext = context_course::instance($courseid);
    $course = $DB->get_record('course', array('id' => $courseid));

    $title = format_text($offlinequiz->name, FORMAT_HTML);

    $title .= ",  " . get_string('group') . $groupletter;

    // Load all the questions needed for this offline quiz group.
    $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
              FROM {offlinequiz_group_questions} ogq,
                   {question} q,
                   {question_categories} c
             WHERE ogq.offlinequizid = :offlinequizid
               AND ogq.offlinegroupid = :offlinegroupid
               AND q.id = ogq.questionid
               AND q.category = c.id
          ORDER BY ogq.slot ASC ";
    $params = array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id);

    // Load the questions.
    $questions = $DB->get_records_sql($sql, $params);
    if (!$questions) {
        echo $OUTPUT->box_start();
        echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
        echo $OUTPUT->box_end();
        return;
    }

    // Load the question type specific information.
    if (!get_question_options($questions)) {
        print_error('Could not load question options');
    }

    $number = 1;

    // We need a mapping from question IDs to slots, assuming that each question occurs only once.
    $slots = $templateusage->get_slots();
    $latexforquestions = '\begin{enumerate}' . "\n";
    // If shufflequestions has been activated we go through the questions in the order determined by
    // the template question usage.
    if ($offlinequiz->shufflequestions) {
        foreach ($slots as $slot) {
            $slotquestion = $templateusage->get_question($slot);
            $currentquestionid = $slotquestion->id;

            $question = $questions[$currentquestionid];

            $questiontext = offlinequiz_convert_html_to_latex($question->questiontext);

            $latexforquestions .= '\item ' .  $questiontext . "\n";

            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {

                // There is only a slot for multichoice questions.
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order of the answers.

                $latexforquestions .= '\begin{enumerate}' . " \n";
                foreach ($order as $key => $answer) {
                    $latexforquestions .= offlinequiz_get_answer_latex($question, $answer);
                }
                $latexforquestions .= '\end{enumerate}' . "\n";

                $infostr = offlinequiz_get_question_infostring($offlinequiz, $question);
                if ($infostr) {
                    $latexforquestions .= $infostr . "\n";
                }
            }

        }
        $latexforquestions .= '\end{enumerate}' . "\n";

    } else {
        // No shufflequestions, so go through the questions as they have been added to the offlinequiz group.
        // We also have to show description questions that are not in the template.

        // First, compute mapping  questionid -> slotnumber.
        $questionslots = array();
        foreach ($slots as $slot) {
            $questionslots[$templateusage->get_question($slot)->id] = $slot;
        }

        foreach ($questions as $question) {
            $currentquestionid = $question->id;

            $questiontext = $question->questiontext;
            $questiontext = offlinequiz_convert_html_to_latex($question->questiontext);
            if ($question->qtype == 'description') {
                $latexforquestions .= "\n" . '\\ ' . $questiontext . "\n";
            } else {
                $latexforquestions .= '\item ' .  $questiontext . "\n";
            }
            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {

                $slot = $questionslots[$currentquestionid];

                // There is only a slot for multichoice questions.
                $slotquestion = $templateusage->get_question ( $slot );
                $attempt = $templateusage->get_question_attempt ( $slot );
                $order = $slotquestion->get_order ( $attempt ); // Order of the answers.
                $latexforquestions .= '\begin{enumerate}' . " \n";
                foreach ($order as $key => $answer) {
                    $latexforquestions .= offlinequiz_get_answer_latex($question, $answer);
                }
                $latexforquestions .= '\end{enumerate}' . "\n";
                $infostr = offlinequiz_get_question_infostring($offlinequiz, $question);
                if ($infostr) {
                    $latexforquestions .= $infostr . "\n";
                }

            }
        }
        $latexforquestions .= '\end{enumerate}' . "\n";
    }
    $a = array();
    $a['latexforquestions'] = $latexforquestions;
    $a['coursename'] = offlinequiz_convert_html_to_latex($course->fullname);
    $a['groupname'] = $groupletter;
    $a['pdfintrotext'] = offlinequiz_convert_html_to_latex(get_string('pdfintrotext', 'offlinequiz', $a));
    // TODO exceptionhandling?
    if ($offlinequiz->time) {
        $a['date'] = ', ' . userdate($offlinequiz->time);
    } else {
        $a['date'] = '';
    }

    $latex = get_string('questionsheetlatextemplate', 'offlinequiz', $a);

    $fs = get_file_storage();
    $fileprefix = get_string('fileprefixform', 'offlinequiz');

    // Prepare file record object.
    $date = usergetdate(time());
    $timestamp = sprintf('%04d%02d%02d_%02d%02d%02d',
            $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds']);

    $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'mod_offlinequiz',
            'filearea' => 'pdfs',
            'filepath' => '/',
            'itemid' => 0,
            'filename' => $fileprefix . '_' . $groupletter . '_' . $timestamp . '.tex');

    if ($oldfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
        $oldfile->delete();
    }

    $file = $fs->create_file_from_string($fileinfo, $latex);

    return $file;
}

/**
 * Return the LaTeX representation of a question or answer text.
 * @param string $text
 */
function offlinequiz_convert_html_to_latex($text) {
    $conversiontable = array(
            'Ä' => '{\"A}',
            'ä' => '{\"a}',
            'Ö' => '{\"O}',
            'ö' => '{\"o}',
            'Ü' => '{\"U}',
            'ü' => '{\"u}',
            'ß' => '{\ss}',
            '&nbsp;' => ' ',
            '#' => '\#',
            '%' => '\%',
    		'<b>' => '\textbf{',
    		'</b>' => '}',
    		'<i>' => '\textit{',
    		'</i>' => '}',
    		'<u>' => '\underline{',
    		'</u>' => '}',
    		'<br />' => '
 ',
    		'&gt;' => '>',
    		'&lt;' => '<',
        '$$' => '$'
    );
    $text = strip_tags($text,'<br><i><b><u>');
    foreach ($conversiontable as $search => $replace) {
        $text = str_ireplace($search, $replace, $text);
    }
    return $text;
}

/**
 * Return the LaTeX representation of an answer.
 *
 * @param unknown $question
 * @param unknown $answer
 * @return string
 */
function offlinequiz_get_answer_latex($question, $answer) {
    $answertext = $question->options->answers[$answer]->answer;
    $answertext = offlinequiz_convert_html_to_latex($answertext);
    if ($question->options->answers [$answer]->fraction > 0) {
        $result = '\item\answerIs{true} ' . $answertext;
    } else {
        $result = '\item\answerIs{false} ' . $answertext;
    }

    $result .= "\n";
    return $result;
}
