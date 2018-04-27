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
 * Creates the Latex forms for offlinequizzes
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
            	$latexforquestions .= '\item %' .  $question->name . "\n" . $questiontext . "\n";
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
    if ($offlinequiz->time) {
        $a['date'] = ', ' . userdate($offlinequiz->time);
    } else {
        $a['date'] = '';
    }
    $a['fontsize'] = $offlinequiz->fontsize;
    if($offlinequiz->printstudycodefield) {
    	$a['printstudycodefield'] = true;
    } else {
    	$a['printstudycodefield'] = false;
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

function offlinequiz_convert_html_to_latex_tagreplace($dom, $tag, $pre, $post) {
	$elements = $dom->getElementsByTagName($tag);
	foreach ($elements as $element) {
		offlinequiz_convert_html_to_latex_single_tag_replace($dom, $pre, $post, $element);
	}
}

function offlinequiz_convert_html_to_latex_paragraph($dom) {
	$elements = $dom->getElementsByTagName('p');
	foreach ($elements as $element) {
		$style= $element->getAttribute("style");
		if ( strpos($style, 'text-align: center') ) {
			$pre = "\\begin{center}\n";
			$post = "\\end{center}\n";
		} elseif ( strpos($style, 'text-align: left') ) {
			$pre = "\\begin{flushleft}\n";
			$post = "\\end{flushleft}\n";
		} elseif ( strpos($style, 'text-align: right') ) {
			$pre = "\\begin{flushright}\n";
			$post = "\\end{flushright}\n";
		} else {
			$pre = "";
			$post = "\n\n";
		}
		offlinequiz_convert_html_to_latex_single_tag_replace($dom, $pre, $post, $element);
	}
}

function offlinequiz_convert_html_to_latex_span($dom) {
	$elements = $dom->getElementsByTagName('span');
	foreach ($elements as $element) {
		$style= $element->getAttribute("style");
		if ( preg_match('/background-color:\s+rgb\((\d+),\s+(\d+),\s+(\d+)\)/', $style, $m) ) {
			$pre = "\\colorbox[RGB]{{$m[1]},{$m[2]},{$m[3]}}{";
			$post = "}";
		} elseif ( preg_match('/color:\s+rgb\((\d+),\s+(\d+),\s+(\d+)\)/', $style, $m) ) {
			$pre = "\\textcolor[RGB]{{$m[1]},{$m[2]},{$m[3]}}{";
			$post = "}";
		} else {
			$pre = "";
			$post = "";
		}
		offlinequiz_convert_html_to_latex_single_tag_replace($dom, $pre, $post, $element);
	}
}

function offlinequiz_convert_html_to_latex_tables($dom) {
	$elements = $dom->getElementsByTagName('table');
	foreach ($elements as $element) {
		$pre = '\begin{tabular}';
		$post = '\end{tabular}';
		$caption = $element->getElementsByTagName('caption')->item(0);
		if ( $caption and $caption->nodeValue !== '' ) {
			$style= $caption->getAttribute("style");
			if ( strpos($style, 'caption-side: bottom') !== false ) {
				$post .= "\n" . "{\large " . $caption->nodeValue . "}\n\n";
			} else {
				$pre = "{\large " . $caption->nodeValue . "}\n\n" . $pre;
			}
			$element->removeChild($caption);
		}
		$rows = $element->getElementsByTagName('tr');
		// TeX needs the number of columns
		$cmax = 0;
		foreach ($rows as $row) {
			$r++ ;
			foreach (array("td", "th") as $item) {
				$cols = $row->getElementsByTagName($item);
				$c = 0;
				foreach ($cols as $col) {
					$c++ ;
					if ($c > 1) {
						$col->nodeValue = "-amp- " . $col->nodeValue; // add & between colums
					}
				}
				$cmax = max($cmax, $c);
			}
			$r++ ;
			if ($r > 1) {
				$row->nodeValue = "\\\\\n" . $row->nodeValue; // add \\ between colums
			}
		}
		$pre .= '{' . str_repeat ("c", $cmax) . '}';
		offlinequiz_convert_html_to_latex_single_tag_replace($dom, $pre, $post, $element);
	}
}

function offlinequiz_convert_html_to_latex_single_tag_replace($dom, $pre, $post, $element) {
	$preNode = $dom->createTextNode($pre);
	$postNode = $dom->createTextNode($post);
	$element->parentNode->insertBefore($preNode, $element);
	$element->appendChild($postNode);
}

/**
 * Return the LaTeX representation of a question or answer text.
 * @param string $text
 */
function offlinequiz_convert_html_to_latex($text) {
	
	$dom = new DOMDocument();
	$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $text);
	// replace tags
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'h3', '{\LARGE ', "}\bigskip\n\n");
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'h4', '{\large ', "}\medskip\n\n");
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'h5', '{\large ', "}\medskip\n\n");
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'b', '\textbf{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'strong', '\textbf{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'i', '\textit{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'u', '\underline{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'strike', '\sout{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'sup', '\textsuperscript{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'sub', '\textsubscript{', '}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'pre', '\begin{verbatim}', '\end{verbatim}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'blockquote', '\begin{quotation}', '\end{quotation}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'center', '\begin{center}', '\end{center}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'ol', '\begin{enumerate}[label=(\arabic*)]', '\end{enumerate}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'ul', '\begin{itemize}', '\end{itemize}');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'li', '\item ', '');
	offlinequiz_convert_html_to_latex_tagreplace($dom, 'br', "\n", ''); // using newline might produce invalid TeX in some cases
	offlinequiz_convert_html_to_latex_tables($dom);
	offlinequiz_convert_html_to_latex_paragraph($dom);
	offlinequiz_convert_html_to_latex_span($dom);
	$text = $dom->saveHTML();
	// replace "$$ ... $$" by "\[ ... \]"
	$text = preg_replace('/\$\$(.*?)\$\$/s','\[\1\]',$text);
	// replace "&amp;" by "&" in math mode
	$text = preg_replace_callback('/(\\\\[\(\[])(.*?)(\\\\[\)\]])/s',function ($m) {
							$tmp = str_replace('&amp;', '&', $m[2]);
							if ( !(strpos($tmp, '\begin{align}') !== false or strpos($tmp, '\begin{align*}') !== false or strpos($tmp, '\begin{eqnarray') !== false) ) { $tmp = $m[1] . $tmp . $m[3]; }
							return $tmp;
						},
						$text);
	$conversiontable = array(
		'&Amul;' => 'Ä',
		'&auml;' => 'ä',
		'&Ouml;' => 'Ö',
		'&ouml;' => 'ö',
		'&Uuml;' => 'Ü',
		'&uuml;' => 'ü',
		'&szlig;' => 'ß',
		'&nbsp;' => '~',
		'&amp;' => '\&',
		'-amp-' => '&', // undo ugly hack to prevent the dom parser from rewriting &
		'#' => '\#',
		'%' => '\%',
		'&gt;' => '>',
		'&lt;' => '<',
		'$' => '\$');
	foreach ($conversiontable as $search => $replace) {
			$text = str_ireplace($search, $replace, $text);
	}
	$text = strip_tags($text);
	return trim($text);
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