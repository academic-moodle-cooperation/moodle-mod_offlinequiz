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
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_offlinequiz\document\create\pdf\answer_pdf;
use mod_offlinequiz\document\create\pdf\barcodewriter;
use mod_offlinequiz\document\create\pdf\participants_pdf;
use mod_offlinequiz\document\create\pdf\question_pdf;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/lib/pdflib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/mod/offlinequiz/html2text.php');
require_once($CFG->dirroot . '/mod/offlinequiz/documentlib.php');

/**
 * Returns a rendering of the number depending on the answernumbering format.
 *
 * @param int $num The number, starting at 0.
 * @param string $style The style to render the number in. One of the
 * options returned by qtype_multichoice:get_numbering_styles()
 * @return string the number $num in the requested style.
 */
function number_in_style($num, $style) {
        return chr(ord('a') + $num);
}

/**
 * prints the question to the pdf
 * @param mixed $pdf
 * @param mixed $question
 * @param mixed $texfilters
 * @param mixed $trans
 * @param mixed $offlinequiz
 * @return string
 */
function offlinequiz_print_question_html($pdf, $question, $texfilters, $trans, $offlinequiz) {
    $pdf->checkpoint();

    $questiontext = $question->questiontext;

    // Filter only for tex formulas.
    $questiontext = offlinequiz_apply_filters($questiontext, $texfilters);

    if ($question->questiontextformat == FORMAT_PLAIN) {
        $questiontext = s($questiontext);
    }
    // Remove all HTML comments (typically from MS Office).
    $questiontext = preg_replace("/<!--.*?--\s*>/ms", "", $questiontext);

    // Remove <font> tags.
    $questiontext = preg_replace("/<font[^>]*>[^<]*<\/font>/ms", "", $questiontext);

    // Remove <script> tags that are created by mathjax preview.
    $questiontext = preg_replace("/<script[^>]*>[^<]*<\/script>/ms", "", $questiontext);
    // Remove all class info from paragraphs because TCPDF won't use CSS.
    // JPC: Exclude pre tags.
    $questiontext = preg_replace('/<p\\b[^>]+class="[^"]*"[^>]*>/i', "<p>", $questiontext);

    $questiontext = $trans->fix_image_paths(
        $questiontext,
        $question->contextid,
        'questiontext',
        $question->id,
        1,
        300,
        $offlinequiz->disableimgnewlines
    );

    $html = '';

    $html .= $questiontext . '<br/><br/>';
    return $html;
}

/**
 * get the html of this offlinequiz
 * @param mixed $offlinequiz
 * @param mixed $templateusage
 * @param mixed $slot
 * @param mixed $question
 * @param mixed $texfilters
 * @param mixed $trans
 * @param mixed $correction
 * @return string
 */
function offlinequiz_get_answers_html(
    $offlinequiz,
    $templateusage,
    $slot,
    $question,
    $texfilters,
    $trans,
    $correction
) {
    $html = '';
    $slotquestion = $templateusage->get_question($slot);
    // There is only a slot for multichoice questions.
    $attempt = $templateusage->get_question_attempt($slot);
    $order = $slotquestion->get_order($attempt);  // Order of the answers.

    foreach ($order as $key => $answer) {
        $answertext = $question->options->answers[$answer]->answer;
        // Filter only for tex formulas.
        $answertext = offlinequiz_apply_filters($answertext, $texfilters);
        // If the answer is in plain text, escape it.
        if ($question->options->answers[$answer]->answerformat != FORMAT_HTML) {
            $answertext = s($answertext);
        }
        // Remove all HTML comments (typically from MS Office).
        $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
        // Remove all paragraph tags because they mess up the layout.
        $answertext = preg_replace("/<p\\b[^>]*>/ms", "", $answertext);
        // Remove <script> tags that are created by mathjax preview.
        $answertext = preg_replace("/<script[^>]*>[^<]*<\/script>/ms", "", $answertext);
        $answertext = $trans->fix_image_paths(
            $answertext,
            $question->contextid,
            'answer',
            $answer,
            1,
            300,
            $offlinequiz->disableimgnewlines
        );

        if ($correction) {
            if ($question->options->answers[$answer]->fraction > 0) {
                $html .= '<b>';
            }

            $answertext .= " (" . round($question->options->answers[$answer]->fraction * 100) . "%)";
        }

        $html .= number_in_style($key, $question->options->answernumbering) . ') &nbsp; ';
        $html .= $answertext;

        if ($correction) {
            if ($question->options->answers[$answer]->fraction > 0) {
                $html .= '</b>';
            }
        }

        $html .= "<br/>\n";
    }

    $infostring = offlinequiz_get_question_infostring($offlinequiz, $question);
    if ($infostring) {
        $html .= '<br/>' . $infostring . '<br/>';
    }
    return $html;
}
/**
 * write the question htmls into the pdf file
 * @param mixed $pdf
 * @param mixed $fontsize
 * @param mixed $questiontype
 * @param mixed $html
 * @param mixed $number
 * @return void
 */
function offlinequiz_write_question_to_pdf($pdf, $fontsize, $questiontype, $html, $number) {

    $pdf->writeHTMLCell(165, round($fontsize / 2), $pdf->GetX(), $pdf->GetY() + 0.3, $html);
    $pdf->Ln();

    if ($pdf->is_overflowing()) {
        $font = offlinequiz_get_pdffont();
        $pdf->backtrack();
        $pdf->AddPage();
        $pdf->Ln(14);

        // Print the question number and the HTML string again on the new page.
        if ($questiontype == 'multichoice' || $questiontype == 'multichoiceset') {
            $pdf->SetFont($font, 'B', $fontsize);
            $pdf->Cell(4, round($fontsize / 2), "$number)  ", 0, 0, 'R');
            $pdf->SetFont($font, '', $fontsize);
        }

        $pdf->writeHTMLCell(165, round($fontsize / 2), $pdf->GetX(), $pdf->GetY() + 0.3, $html);
        $pdf->Ln();
    }
}
/**
 * Generates the PDF question/correction form for an offlinequiz group.
 *
 * @param question_usage_by_activity $templateusage the template question  usage for this offline group
 * @param stdClass $offlinequiz The offlinequiz object
 * @param stdClass $group the offline group object
 * @param int $courseid the ID of the Moodle course
 * @param object $context the context of the offline quiz.
 * @param bool $correction if true the correction form is generated.
 * @return stored_file|null the generated PDF file.
 */
function offlinequiz_create_pdf_question(
    question_usage_by_activity $templateusage,
    $offlinequiz,
    $group,
    $courseid,
    $context,
    $correction = false
) {
    global $CFG, $DB, $OUTPUT;

    $letterstr = 'abcdefghijklmnopqrstuvwxyz';
    $groupletter = strtoupper($letterstr[$group->groupnumber - 1]);
    $font = offlinequiz_get_pdffont($offlinequiz);
    $coursecontext = context_course::instance($courseid);

    $pdf = new question_pdf('P', 'mm', 'A4');
    $trans = new offlinequiz_html_translator();

    $title = offlinequiz_str_html_pdf($offlinequiz->name);
    if (!empty($offlinequiz->time)) {
        $title .= ": " . offlinequiz_str_html_pdf(userdate($offlinequiz->time));
    }
    $title .= ",  " . offlinequiz_str_html_pdf(get_string('group', 'offlinequiz') . " $groupletter");
    $pdf->set_title($title);
    $pdf->SetMargins(15, 28, 15);
    $pdf->SetAutoPageBreak(false, 25);
    $pdf->AddPage();

    // Print title page.
    $pdf->SetFont($font, 'B', 14);
    $pdf->Ln(4);
    if (!$correction) {
        $pdf->Cell(0, 4, offlinequiz_str_html_pdf(get_string('questionsheet', 'offlinequiz')), 0, 0, 'C');
        if ($offlinequiz->printstudycodefield) {
            $pdf->Rect(34, 42, 137, 50, 'D');
        } else {
            $pdf->Rect(34, 42, 137, 40, 'D');
        }
        $pdf->SetFont($font, '', 10);
        // Line breaks to position name string etc. properly.
        $pdf->Ln(14);
        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('name')) . ":", 0, 0, 'R');
        $pdf->Rect(76, 54, 80, 0.3, 'F');
        $pdf->Ln(10);
        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('idnumber', 'offlinequiz')) . ":", 0, 0, 'R');
        $pdf->Rect(76, 64, 80, 0.3, 'F');
        $pdf->Ln(10);
        if ($offlinequiz->printstudycodefield) {
            $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('studycode', 'offlinequiz')) . ":", 0, 0, 'R');
            $pdf->Rect(76, 74, 80, 0.3, 'F');
            $pdf->Ln(10);
        }
        $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('signature', 'offlinequiz')) . ":", 0, 0, 'R');
        if ($offlinequiz->printstudycodefield) {
            $pdf->Rect(76, 84, 80, 0.3, 'F');
        } else {
            $pdf->Rect(76, 74, 80, 0.3, 'F');
        }
        $pdf->Ln(25);
        $pdf->SetFont($font, '', $offlinequiz->fontsize);

        // The PDF intro text can be arbitrarily long so we have to catch page overflows.
        if (!empty($offlinequiz->pdfintro)) {
            $oldx = $pdf->GetX();
            $oldy = $pdf->GetY();

            $pdf->checkpoint();
            $pdf->writeHTMLCell(165, round($offlinequiz->fontsize / 2), $pdf->GetX(), $pdf->GetY(), $offlinequiz->pdfintro);
            $pdf->Ln();

            if ($pdf->is_overflowing()) {
                $pdf->backtrack();
                $pdf->SetX($oldx);
                $pdf->SetY($oldy);
                $paragraphs = preg_split('/<p>/', $offlinequiz->pdfintro);

                foreach ($paragraphs as $paragraph) {
                    if (!empty($paragraph)) {
                        $sentences = preg_split('/<br\s*\/>/', $paragraph);
                        foreach ($sentences as $sentence) {
                            $pdf->checkpoint();
                            $pdf->writeHTMLCell(
                                165,
                                round($offlinequiz->fontsize / 2),
                                $pdf->GetX(),
                                $pdf->GetY(),
                                $sentence . '<br/>'
                            );
                            $pdf->Ln();
                            if ($pdf->is_overflowing()) {
                                $pdf->backtrack();
                                $pdf->AddPage();
                                $pdf->Ln(14);
                                $pdf->writeHTMLCell(165, round($offlinequiz->fontsize / 2), $pdf->GetX(), $pdf->GetY(), $sentence);
                                $pdf->Ln();
                            }
                        }
                    }
                }
            }
        }
        $pdf->AddPage();
        $pdf->Ln(2);
    }
    $pdf->SetMargins(15, 15, 15);

    // Load all the questions needed for this offline quiz group.
    $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
              FROM {offlinequiz_group_questions} ogq
              JOIN {question} q ON q.id = ogq.questionid
              JOIN {question_versions} qv ON qv.questionid = q.id
              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
              JOIN {question_categories} c ON c.id = qbe.questioncategoryid
             WHERE ogq.offlinequizid = :offlinequizid
               AND ogq.offlinegroupid = :offlinegroupid
          ORDER BY ogq.slot ASC ";
    $params = ['offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id];

    // Load the questions.
    $questions = $DB->get_records_sql($sql, $params);
    if (!$questions) {
        echo $OUTPUT->box_start();
        echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
        echo $OUTPUT->box_end();
        return null;
    }

    // Load the question type specific information.
    if (!get_question_options($questions)) {
        throw new \moodle_exception('Could not load question options');
    }

    // Restore the question sessions to their most recent states.
    // Creating new sessions where required.
    $number = 1;

    // We need a mapping from question IDs to slots, assuming that each question occurs only once.
    $slots = $templateusage->get_slots();

    $texfilters = offlinequiz_get_math_filters($context, null);

    // If shufflequestions has been activated we go through the questions in the order determined by
    // the template question usage.
    if ($offlinequiz->shufflequestions) {
        foreach ($slots as $slot) {
            $slotquestion = $templateusage->get_question($slot);
            $currentquestionid = $slotquestion->id;

            // Add page break if necessary because of overflow.
            if ($pdf->GetY() > 230) {
                $pdf->AddPage();
                $pdf->Ln(14);
            }
            set_time_limit(120);
            $question = $questions[$currentquestionid];

            $html = offlinequiz_print_question_html($pdf, $question, $texfilters, $trans, $offlinequiz);

            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                $html = $html . offlinequiz_get_answers_html(
                    $offlinequiz,
                    $templateusage,
                    $slot,
                    $question,
                    $texfilters,
                    $trans,
                    $correction
                );
            }
            if ($offlinequiz->disableimgnewlines) {
                // This removes span attribute added by TEX filter which created extra line break after every LATEX formula.
                $html = preg_replace("/(<span class=\"MathJax_Preview\">.+?)+(title=\"TeX\" >)/ms", "", $html);
                $html = preg_replace("/<\/a><\/span>/ms", "", $html);
                $html = preg_replace("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/ms", "", $html);
            }
            // Finally print the question number and the HTML string.
            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                $pdf->SetFont($font, 'B', $offlinequiz->fontsize);
                $pdf->Cell(4, round($offlinequiz->fontsize / 2), "$number)  ", 0, 0, 'R');
                $pdf->SetFont($font, '', $offlinequiz->fontsize);
            }
            offlinequiz_write_question_to_pdf($pdf, $offlinequiz->fontsize, $question->qtype, $html, $number);
            $number += $questions[$currentquestionid]->length;
        }
    } else {
        // No shufflequestions, so go through the questions as they have been added to the offlinequiz group.
        // We also have to show description questions that are not in the template.

        // First, compute mapping  questionid -> slotnumber.
        $questionslots = [];
        foreach ($slots as $slot) {
            $questionslots[$templateusage->get_question($slot)->id] = $slot;
        }
        $currentpage = 1;
        foreach ($questions as $question) {
            $currentquestionid = $question->id;

            // Add page break if set explicitely by teacher.
            if ($question->page > $currentpage) {
                $pdf->AddPage();
                $pdf->Ln(14);
                $currentpage++;
            }

            // Add page break if necessary because of overflow.
            if ($pdf->GetY() > 230) {
                $pdf->AddPage();
                $pdf->Ln(14);
            }
            set_time_limit(120);

            // Either we print the question HTML.
            $html = offlinequiz_print_question_html($pdf, $question, $texfilters, $trans, $offlinequiz);

            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                $slot = $questionslots[$currentquestionid];

                $html = $html . offlinequiz_get_answers_html(
                    $offlinequiz,
                    $templateusage,
                    $slot,
                    $question,
                    $texfilters,
                    $trans,
                    $correction
                );
            }

            // Finally print the question number and the HTML string.
            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                $pdf->SetFont($font, 'B', $offlinequiz->fontsize);
                $pdf->Cell(4, round($offlinequiz->fontsize / 2), "$number)  ", 0, 0, 'R');
                $pdf->SetFont($font, '', $offlinequiz->fontsize);
            }

            // This removes span attribute added by TEX filter which created extra line break after every LATEX formula.
            if ($offlinequiz->disableimgnewlines) {
                $html = preg_replace("/(<span class=\"MathJax_Preview\">.+?)+(title=\"TeX\" >)/ms", "", $html);
                $html = preg_replace("/<\/a><\/span>/ms", "", $html);
                $html = preg_replace("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/ms", "", $html);
            }

            offlinequiz_write_question_to_pdf($pdf, $offlinequiz->fontsize, $question->qtype, $html, $number);
            $number += $questions[$currentquestionid]->length;
        }
    }

    $fs = get_file_storage();

    $fileprefix = get_string('fileprefixform', 'offlinequiz');
    if ($correction) {
        $fileprefix = get_string('fileprefixcorrection', 'offlinequiz');
    }

    // Prepare file record object.
    $date = usergetdate(time());
    $timestamp = sprintf(
        '%04d%02d%02d_%02d%02d%02d',
        $date['year'],
        $date['mon'],
        $date['mday'],
        $date['hours'],
        $date['minutes'],
        $date['seconds']
    );

    $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_offlinequiz',
            'filearea' => 'pdfs',
            'filepath' => '/',
            'itemid' => 0,
            'filename' => $fileprefix . '_' . $groupletter . '_' . $timestamp . '.pdf'];

    if (
        $oldfile = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )
    ) {
        $oldfile->delete();
    }
    $pdfstring = $pdf->Output('', 'S');

    $file = $fs->create_file_from_string($fileinfo, $pdfstring);
    $trans->remove_temp_files();

    return $file;
}


/**
 * Generates the PDF answer form for an offlinequiz group.
 *
 * @param int $maxanswers the maximum number of answers in all question of the offline group
 * @param question_usage_by_activity $templateusage the template question  usage for this offline group
 * @param object $offlinequiz The offlinequiz object
 * @param object $group the offline group object
 * @param int $courseid the ID of the Moodle course
 * @param object $context the context of the offline quiz.
 * @return stored_file instance, the generated PDF file.
 */
function offlinequiz_create_pdf_answer($maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context) {
    global $CFG, $DB, $OUTPUT, $USER;

    $letterstr = ' abcdefghijklmnopqrstuvwxyz';
    $groupletter = strtoupper($letterstr[$group->groupnumber]);

    $pdf = new answer_pdf('P', 'mm', 'A4');

    $pdf->add_answer_page($maxanswers, $templateusage, $offlinequiz, $group, $courseid, $context, $groupletter);

    $DB->update_record('offlinequiz_groups', $group);

    $fs = get_file_storage();

    // Prepare file record object.
    $date = usergetdate(time());
    $timestamp = sprintf(
        '%04d%02d%02d_%02d%02d%02d',
        $date['year'],
        $date['mon'],
        $date['mday'],
        $date['hours'],
        $date['minutes'],
        $date['seconds']
    );

    $fileprefix = get_string('fileprefixanswer', 'offlinequiz');
    $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_offlinequiz',
            'filearea' => 'pdfs',
            'filepath' => '/',
            'itemid' => 0,
            'filename' => $fileprefix . '_' . $groupletter . '_' . $timestamp . '.pdf'];

    if (
        $oldfile = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )
    ) {
        $oldfile->delete();
    }
    $pdfstring = $pdf->Output('', 'S');
    $file = $fs->create_file_from_string($fileinfo, $pdfstring);
    return $file;
}

/**
 * Creates a PDF document for a list of participants
 *
 * @param stdClass $offlinequiz
 * @param int $courseid
 * @param stdClass $list
 * @param context_module $context
 * @return boolean|stored_file
 */
function offlinequiz_create_pdf_participants($offlinequiz, $courseid, $list, $context) {
    global $CFG, $DB;

    $coursecontext = context_course::instance($courseid); // Course context.
    $systemcontext = context_system::instance();

    $offlinequizconfig = get_config('offlinequiz');
    $listname = $list->name;

    // First get roleids for students.
    if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW, $systemcontext)) {
        throw new \moodle_exception("No roles with capability 'mod/offlinequiz:attempt' defined in system context");
    }

    $roleids = [];
    foreach ($roles as $role) {
        $roleids[] = $role->id;
    }

    [$csql, $cparams] = $DB->get_in_or_equal($coursecontext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'ctx');
    [$rsql, $rparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');
    $params = array_merge($cparams, $rparams);

    $sql = "SELECT DISTINCT u.id, u." . $offlinequizconfig->ID_field . ", u.firstname, u.lastname
              FROM {user} u,
                   {offlinequiz_participants} p,
                   {role_assignments} ra,
                   {offlinequiz_p_lists} pl
             WHERE ra.userid = u.id
               AND p.listid = :listid
               AND p.listid = pl.id
               AND pl.offlinequizid = :offlinequizid
               AND p.userid = u.id
               AND ra.roleid $rsql AND ra.contextid $csql
          ORDER BY u.lastname, u.firstname";

    $params['offlinequizid'] = $offlinequiz->id;
    $params['listid'] = $list->id;

    $participants = $DB->get_records_sql($sql, $params);

    if (empty($participants)) {
        return false;
    }

    $pdf = new participants_pdf('P', 'mm', 'A4');
    $pdf->listno = $list->listnumber;
    $title = offlinequiz_str_html_pdf($offlinequiz->name);
    // Add the list name to the title.
    $title .= ', ' . offlinequiz_str_html_pdf($listname);
    $pdf->set_title($title);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->Ln(9);

    $position = 1;

    $pdf->SetFont(offlinequiz_get_pdffont(), '', 10);
    foreach ($participants as $participant) {
        $pdf->Cell(9, 3.5, "$position. ", 0, 0, 'R');
        $pdf->Cell(1, 3.5, '', 0, 0, 'C');
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y + 0.6, 3.5, 3.5);
        $pdf->Cell(3, 3.5, '', 0, 0, 'C');

        $pdf->Cell(6, 3.5, '', 0, 0, 'C');
        $userkey = substr(
            $participant->{$offlinequizconfig->ID_field},
            strlen($offlinequizconfig->ID_prefix),
            $offlinequizconfig->ID_digits
        );
        $pdf->Cell(13, 3.5, $userkey, 0, 0, 'R');
        $pdf->Cell(12, 3.5, '', 0, 0, 'L');
        if ($pdf->GetStringWidth($participant->firstname) > 40) {
            $participant->firstname = substr($participant->firstname, 0, 20);
        }
        if ($pdf->GetStringWidth($participant->lastname) > 55) {
            $participant->lastname = substr($participant->lastname, 0, 25);
        }
        $pdf->Cell(55, 3.5, $participant->lastname, 0, 0, 'L');
        $pdf->Cell(40, 3.5, $participant->firstname, 0, 0, 'L');
        $pdf->Cell(10, 3.5, '', 0, 1, 'R');
        // Print barcode.
        $y = $pdf->GetY() - 3.5;
        $x = 170;
        barcodewriter::print_barcode($pdf, $participant->id, $x, $y);
        $pdf->Rect($x, $y, 0.2, 3.7, 'F');
        $pdf->Rect(15, ($pdf->GetY() + 1), 175, 0.2, 'F');
        if ($position % NUMBERS_PER_PAGE != 0) {
            $pdf->Ln(3.6);
        } else {
            $pdf->AddPage();
            $pdf->Ln(9);
        }
        $position++;
    }

    $fs = get_file_storage();

    // Prepare file record object.
    $date = usergetdate(time());
    $timestamp = sprintf(
        '%04d%02d%02d_%02d%02d%02d',
        $date['year'],
        $date['mon'],
        $date['mday'],
        $date['hours'],
        $date['minutes'],
        $date['seconds']
    );

    $fileprefix = get_string('fileprefixparticipants', 'offlinequiz');
    $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_offlinequiz',
            'filearea' => 'participants',
            'filepath' => '/',
            'itemid' => 0,
            'filename' => $fileprefix . '_' . $list->id . '_' . $timestamp . '.pdf'];

    if (
        $oldfile = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        )
    ) {
        $oldfile->delete();
    }

    $pdfstring = $pdf->Output('', 'S');
    $file = $fs->create_file_from_string($fileinfo, $pdfstring);
    return $file;
}


/**
 * Function to transform Moodle HTML code of a question into proprietary markup that only supports italic, underline and bold.
 *
 * @param string $input The input text.
 * @param boolean $stripalltags Whether all tags should be stripped.
 * @param int $questionid The ID of the question the text stems from.
 * @param int $coursecontextid The course context ID.
 * @return mixed
 */
function offlinequiz_str_html_pdf($input, $stripalltags = true, $questionid = null, $coursecontextid = null) {
    global $CFG;

    $output = $input;
    $fs = get_file_storage();

    // Replace linebreaks.
    $output = preg_replace('!<br>!i', "\n", $output);
    $output = preg_replace('!<br />!i', "\n", $output);
    $output = preg_replace('!</p>!i', "\n", $output);

    if (!$stripalltags) {
        $output = preg_replace('data:image\/[a-z]*;base64,', '@', $output);
        // First replace the plugin image tags.
        $output = str_replace('[', '(', $output);
        $output = str_replace(']', ')', $output);
        $strings = preg_split("/<img/i", $output);
        $output = array_shift($strings);
        foreach ($strings as $string) {
            $output .= '[*p ';
            $imagetag = substr($string, 0, strpos($string, '>'));
            $attributes = explode(' ', $imagetag);
            foreach ($attributes as $attribute) {
                $valuepair = explode('=', $attribute);
                if (strtolower(trim($valuepair[0])) == 'src') {
                    $pluginfilename = str_replace('"', '', str_replace("'", '', $valuepair[1]));
                    $pluginfilename = str_replace('@@PLUGINFILE@@/', '', $pluginfilename);
                    $file = $fs->get_file($coursecontextid, 'question', 'questiontext', $questionid, '/', $pluginfilename);
                    // Copy file to temporary file.
                    $output .= $file->get_id() . ']';
                }
            }
            $output .= substr($string, strpos($string, '>') + 1);
        }
        $strings = preg_split("/<span/i", $output);
        $output = array_shift($strings);
        foreach ($strings as $string) {
            $tags = preg_split("/<\/span>/i", $string);
            $styleinfo = explode('>', $tags[0]);
            $style = [];
            if (stripos($styleinfo[0], 'bold')) {
                $style[] = '[*b]';
            }
            if (stripos($styleinfo[0], 'italic')) {
                $style[] = '[*i]';
            }
            if (stripos($styleinfo[0], 'underline')) {
                $style[] = '[*u]';
            }
            sort($style);
            array_shift($styleinfo);
            $output .= implode($style) . implode('>', $styleinfo);
            rsort($style);
            $output .= implode($style);
            if (!empty($tags[1])) {
                $output .= $tags[1];
            }
        }

        $search  = ['/<i[ ]*>(.*?)<\/i[ ]*>/smi', '/<b[ ]*>(.*?)<\/b[ ]*>/smi', '/<em[ ]*>(.*?)<\/em[ ]*>/smi',
                '/<strong[ ]*>(.*?)<\/strong[ ]*>/smi', '/<u[ ]*>(.*?)<\/u[ ]*>/smi',
                '/<sub[ ]*>(.*?)<\/sub[ ]*>/smi', '/<sup[ ]*>(.*?)<\/sup[ ]*>/smi' ];
        $replace = ['[*i]\1[*i]', '[*b]\1[*b]', '[*i]\1[*i]',
                '[*b]\1[*b]', '[*u]\1[*u]',
                '[*l]\1[*l]', '[*h]\1[*h]'];
        $output = preg_replace($search, $replace, $output);
    }
    $output = strip_tags($output);
    $search  = ['&quot;', '&amp;', '&gt;', '&lt;'];
    $replace = ['"', '&', '>', '<'];
    $result = str_ireplace($search, $replace, $output);

    return $result;
}
