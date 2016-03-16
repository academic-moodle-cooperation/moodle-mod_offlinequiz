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


// //TODO löschen?
// /**
//  * Returns a rendering of the number depending on the answernumbering format.
//  * 
//  * @param int $num The number, starting at 0.
//  * @param string $style The style to render the number in. One of the
//  * options returned by {@link qtype_multichoice:;get_numbering_styles()}.
//  * @return string the number $num in the requested style.
//  */
// function number_in_style($num, $style) {
//         return $number = chr(ord('a') + $num);
// }
/**
 * Generates the PDF question/correction form for an offlinequiz group.
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
	//TODO
    $title = format_text($offlinequiz->name, FORMAT_HTML);
    if (!empty($offlinequiz->time)) {
        $title .= ': ' . userdate($offlinequiz->time);
    }
    $title .= ",  " . get_string('group') . $groupletter;
    if (!$correction) {
    

        // The PDF intro text can be arbitrarily long so we have to catch page overflows.
        if (!empty($offlinequiz->pdfintro)) {
		//TODO
        }
    }

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

            $questiontext = $question->questiontext;


            // Remove all HTML comments (typically from MS Office).
            $questiontext = preg_replace("/<!--.*?--\s*>/ms", "", $questiontext);

            // Remove <font> tags.
            $questiontext = preg_replace("/<font[^>]*>[^<]*<\/font>/ms", "", $questiontext);

            // Remove <script> tags that are created by mathjax preview.
            $questiontext = preg_replace("/<script[^>]*>[^<]*<\/script>/ms", "", $questiontext);
            //print_object($questiontext);
			$questiontext = strip_tags($questiontext);
			//print_object($questiontext);
			//TODO remove images

            $latexforquestions .=  '\item ' .  $questiontext . "\n";
            //TODO Antworttyp Enumerate
            $latexforquestions .= '\begin{enumerate}' . " \n";
            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {

                // There is only a slot for multichoice questions.
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order of the answers.
				print_object($order);
				
                foreach ($order as $key => $answer) {
                    $answertext = $question->options->answers[$answer]->answer;
                    // Remove all HTML comments (typically from MS Office).
                    $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
                    // Remove all paragraph tags because they mess up the layout.
                    $answertext = preg_replace("/<p[^>]*>/ms", "", $answertext);
                    // Remove <script> tags that are created by mathjax preview.
                    $answertext = preg_replace("/<script[^>]*>[^<]*<\/script>/ms", "", $answertext);
                    $answertext = preg_replace("/<\/p[^>]*>/ms", "", $answertext);
                    $answertext = strip_tags($answertext);
                    //TODO remove images
					//TODO Antworten richtig anzeigen
                    //                     if ($correction) {
                    //                         if ($question->options->answers[$answer]->fraction > 0) {
                    //                             $latex .= '</b>';
                    //                         }
                    //                     }
					$answertext = '\item\answerIs{false} ' . $answertext;
                    $latexforquestions .= $answertext;


					
                    $latexforquestions .= "\n";
                }
                $latexforquestions .= '\end{enumerate}' . "\n";
					//TODO showgrades?
//                 if ($offlinequiz->showgrades) {
//                     $pointstr = get_string('points', 'grades');
//                     if ($question->maxmark == 1) {
//                         $pointstr = get_string('point', 'offlinequiz');
//                     }
//                     $latex .= '<br/>(' . ($question->maxmark + 0) . ' ' . $pointstr .')<br/>';
//                 }
            }

        }
        $latexforquestions .= '\end{enumerate}' . "\n";
        
    } else {
//         // No shufflequestions, so go through the questions as they have been added to the offlinequiz group.
//         // We also have to show description questions that are not in the template.

//         // First, compute mapping  questionid -> slotnumber.
//         $questionslots = array();
//         foreach ($slots as $slot) {
//             $questionslots[$templateusage->get_question($slot)->id] = $slot;
//         }
//         $currentpage = 1;
//         foreach($questions as $question) {
//             $currentquestionid = $question->id;
            
//             // Add page break if set explicitely by teacher.
//             if ($question->page > $currentpage) {
//                 $pdf->AddPage();
//                 $pdf->Ln(14);
//                 $currentpage++;
//             }

//             // Add page break if necessary because of overflow.
//             if ($pdf->GetY() > 230) {
//                 $pdf->AddPage();
//                 $pdf->Ln( 14 );
//             }
//             set_time_limit( 120 );
            
//             /**
//              * **************************************************
//              * either we print the question HTML 
//              * **************************************************
//              */
//             $pdf->checkpoint();
            
//             $questiontext = $question->questiontext;
            
//             // Filter only for tex formulas.
//             if (! empty ( $texfilter )) {
//                 $questiontext = $texfilter->filter ( $questiontext );
//             }
            
//             // Remove all HTML comments (typically from MS Office).
//             $questiontext = preg_replace ( "/<!--.*?--\s*>/ms", "", $questiontext );
            
//             // Remove <font> tags.
//             $questiontext = preg_replace ( "/<font[^>]*>[^<]*<\/font>/ms", "", $questiontext );
            
//             // Remove <script> tags that are created by mathjax preview.
//             $questiontext = preg_replace ( "/<script[^>]*>[^<]*<\/script>/ms", "", $questiontext );
            
//             // Remove all class info from paragraphs because TCPDF won't use CSS.
//             $questiontext = preg_replace ( '/<p[^>]+class="[^"]*"[^>]*>/i', "<p>", $questiontext );
            
//             $questiontext = $trans->fix_image_paths ( $questiontext, $question->contextid, 'questiontext', $question->id, 1, 300 );
            
//             $latex = '';
            
//             $latex .= $questiontext . '<br/><br/>';
//             if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                
//                 $slot = $questionslots[$currentquestionid];
//                 // Save the usage slot in the group questions table.
//                 // $DB->set_field('offlinequiz_group_questions', 'usageslot', $slot,
//                 // array('offlinequizid' => $offlinequiz->id,
//                 // 'offlinegroupid' => $group->id, 'questionid' => $question->id));
                
//                 // There is only a slot for multichoice questions.
//                 $slotquestion = $templateusage->get_question ( $slot );
//                 $attempt = $templateusage->get_question_attempt ( $slot );
//                 $order = $slotquestion->get_order ( $attempt ); // Order of the answers.
                
//                 foreach ( $order as $key => $answer ) {
//                     $answertext = $question->options->answers[$answer]->answer;
//                     // Filter only for tex formulas.
//                     if (! empty ( $texfilter )) {
//                         $answertext = $texfilter->filter ( $answertext );
//                     }
                    
//                     // Remove all HTML comments (typically from MS Office).
//                     $answertext = preg_replace ( "/<!--.*?--\s*>/ms", "", $answertext );
//                     // Remove all paragraph tags because they mess up the layout.
//                     $answertext = preg_replace ( "/<p[^>]*>/ms", "", $answertext );
//                     // Remove <script> tags that are created by mathjax preview.
//                     $answertext = preg_replace ( "/<script[^>]*>[^<]*<\/script>/ms", "", $answertext );
//                     $answertext = preg_replace ( "/<\/p[^>]*>/ms", "", $answertext );
//                     $answertext = $trans->fix_image_paths ( $answertext, $question->contextid, 'answer', $answer, 1, 300 );
//                     // Was $pdf->GetK()).
                    
//                     if ($correction) {
//                         if ($question->options->answers[$answer]->fraction > 0) {
//                             $latex .= '<b>';
//                         }
                        
//                         $answertext .= " (" . round ( $question->options->answers[$answer]->fraction * 100 ) . "%)";
//                     }
                    
//                     $latex .= number_in_style ( $key, $question->options->answernumbering ) . ') &nbsp; ';
//                     $latex .= $answertext;
                    
//                     if ($correction) {
//                         if ($question->options->answers[$answer]->fraction > 0) {
//                             $latex .= '</b>';
//                         }
//                     }
//                     $latex .= "<br/>\n";
//                 }
                
//                 if ($offlinequiz->showgrades) {
//                     $pointstr = get_string ( 'points', 'grades' );
//                     if ($question->maxmark == 1) {
//                         $pointstr = get_string ( 'point', 'offlinequiz' );
//                     }
//                     $latex .= '<br/>(' . ($question->maxmark + 0) . ' ' . $pointstr . ')<br/>';
//                 }
//             }
            
//             // Finally print the question number and the HTML string.
//             if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
//                 $pdf->SetFont ( 'FreeSans', 'B', $offlinequiz->fontsize );
//                 $pdf->Cell ( 4, round ( $offlinequiz->fontsize / 2 ), "$number)  ", 0, 0, 'R' );
//                 $pdf->SetFont ( 'FreeSans', '', $offlinequiz->fontsize );
//             }
            
//             $pdf->writeHTMLCell ( 165, round ( $offlinequiz->fontsize / 2 ), $pdf->GetX (), $pdf->GetY () + 0.3, $latex );
//             $pdf->Ln ();
            
//             if ($pdf->is_overflowing ()) {
//                 $pdf->backtrack ();
//                 $pdf->AddPage ();
//                 $pdf->Ln ( 14 );
                
//                 // Print the question number and the HTML string again on the new page.
//                 if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
//                     $pdf->SetFont ( 'FreeSans', 'B', $offlinequiz->fontsize );
//                     $pdf->Cell ( 4, round ( $offlinequiz->fontsize / 2 ), "$number)  ", 0, 0, 'R' );
//                     $pdf->SetFont ( 'FreeSans', '', $offlinequiz->fontsize );
//                 }
                
//                 $pdf->writeHTMLCell ( 165, round ( $offlinequiz->fontsize / 2 ), $pdf->GetX (), $pdf->GetY () + 0.3, $latex );
//                 $pdf->Ln ();
//             }
//             $number += $questions[$currentquestionid]->length;
//        }

    }
    $a = array();
    $a['latexforquestions'] = $latexforquestions;
    //TODO LATEXen
    $a['coursename'] = $course->fullname;
    $a['groupname'] = $groupletter;
    //TODO exceptionhandling?
    $a['date'] = userdate($offlinequiz->time);
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
