<?php
// This file is for Moodle - http://moodle.org/
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
 * Creates the DOCX question sheets for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/lib/PHPWord.php');
require_once($CFG->dirroot . '/mod/offlinequiz/html2text.php');

/**
 * Function to print all blocks (parts) of a question or answer text in one textrun.
 * Blocks can be strings or images.
 * 
 * @param unknown_type $section The section the textrun is located in.
 * @param array $blocks The array of blocks that should be printed. 
 */
function offlinequiz_print_blocks_docx(PHPWord_Section $section, $blocks) {
     
    $textrun = $section->createTextRun();
    foreach($blocks as $block) {
        if ($block['type'] == 'string') {
            if (!empty($block['style'])) {
                $textrun->addText($block['value'], $block['style']);
            } else {
                $textrun->addText($block['value']);
            }
        } else if ($block['type'] == 'newline') {
            $textrun = $section->createTextRun();
        } else if ($block['type'] == 'listitem') {
            $listStyle = array('listType' => PHPWord_Style_ListItem::TYPE_SMALL_LETTER);
//            print_object($block['value']);
            $section->addListItem($block['value'], 1, null, $listStyle);
        } else if ($block['type'] == 'image') {
            $style = array();
            $style['width'] = 200;
            $style['height'] = 100;
            $style['align'] = 'center';

            if ($block['width']) {
                $style['width'] = intval($block['width']);
            }
            if ($block['height']) {
                $style['height'] = intval($block['height']);
            }
            if ($block['align']) {
                $style['align'] = $block['align'];
                if ($style['align'] == 'middle') {
                    $style['align'] = 'center';
                }
            }
//            print_object('image data ');
 //         // print_object($block['value']);
   //         // print_object($style);
            // Now add the image and start a new textrun.
            $section->addImage($block['value'], $style);
            $textrun = $section->createTextRun();
        }
    }
}

function offlinequiz_convert_bold_text_docx($text) {
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Now add the remaining text after the image tag.
    $parts = preg_split("/<b>/i", $text);
    $result = array();
    
    // print_object('bold parts');
    // print_object($parts);

    $firstpart = array_shift($parts);
    $firstpart = strip_tags($firstpart);
    $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $firstpart));

    foreach($parts as $part) {
        $closetagpos = strpos($part, '</b>');
        $boldtext = strip_tags(trim(substr($part, 0, $closetagpos)));
        // print_object('bold text: '. $boldtext);
        $boldremain = trim(substr($part, $closetagpos + 4));
        // print_object('bold remain: ' . $boldremain);
         
        $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $boldtext), 'style' => 'bStyle');
        if (!empty($boldremain)) {
            $boldremainblocks = offlinequiz_convert_question_text_docx($boldremain, false, 0, false);
            $result = array_merge($result, $boldremainblocks);
        }
    }
    // print_object('bold blocks:');
    // print_object($result);
    return $result;
}

function offlinequiz_convert_newline_docx($text) {
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Now add the remaining text after the image tag.
    $parts = preg_split("!<br>|<br />!i", $text);
    $result = array();
    
    // print_object('newline parts');
    // print_object($parts);

    $firstpart = array_shift($parts);
    if (!empty($firstpart)) {
        $firstpartblocks = offlinequiz_convert_bold_text_docx($firstpart);
        $result = $firstpartblocks;
    }
    

    foreach($parts as $part) {
        $result[] = array('type' => 'newline');
        if (!empty($part)) {
            $partblocks = offlinequiz_convert_bold_text_docx($part);
            $result = array_merge($result, $partblocks);
        }
    }
    // print_object('newline blocks:');
    // print_object($result);
    return $result;
}


/**
 * Function to transform Moodle HTML code of a question into proprietary markup that only supports italic, underline and bold.
 *
 * @param unknown_type $input The input text.
 * @param unknown_type $stripalltags Whether all tags should be stripped.
 * @param unknown_type $questionid The ID of the question the text stems from.
 * @param unknown_type $coursecontextid The course context ID.
 * @return mixed
 */
function offlinequiz_convert_question_text_docx($text, $listitem = false, $number = 0, $image = true) {
    global $CFG;

    $result = array();
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Replace linebreaks.
//    $text = preg_replace('!<br>!i', "\r\n", $text);
//    $text = preg_replace('!<br />!i', "\r\n", $text);
    $text = preg_replace('!<p>!i', '', $text);
    $text = preg_replace('!</p>!i', '', $text);

    //print_object($strings);
    // First add all the text that appears before the image tag.
    if ($number) {
        $result[] = array('type' => 'string', 'value' => $number . ') ', 'style' => 'bStyle');
    }

    if ($image) {    // Extract image tags
        $strings = preg_split("/<img/i", $text);
        $firstline = array_shift($strings);
        if (!empty($firstline)) {
            $firstlineblocks = offlinequiz_convert_newline_docx($firstline);
            $result = array_merge($result, $firstlineblocks);
        }
    } else {
        $strings = array();
        if (!empty($text)) {
            $textblocks = offlinequiz_convert_newline_docx($text);
            $result = array_merge($result, $textblocks);
        }
    }

    print_object($result);
    
    
//     if ($listitem) {
//         $result[] = array('type' => 'listitem', 'value' => str_ireplace($search, $replace, $firstline));
//     } else {
//         $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $firstline));
//     }

    foreach ($strings as $string) {
        $imagetag = substr($string, 0, strpos($string, '>'));
        $attributes = explode(' ', $imagetag);
        $width = 200;
        $height = 100;
        $align = 'middle';
        foreach ($attributes as $attribute) {
            $valuepair = explode('=', $attribute);
            print_object('value pair ' . $valuepair[1]);

            if (strtolower(trim($valuepair[0])) == 'src') {
                $imageurl = str_replace('file://', '', str_replace('"', '', str_replace("'", '', $valuepair[1])));
            } else {
                $valuepair[1] = preg_replace('!"!i', '', $valuepair[1]);
                $valuepair[1] = preg_replace('!/!i', '', $valuepair[1]);
                if (strtolower(trim($valuepair[0])) == 'width') {
                    $width = trim($valuepair[1]);
                } else if (strtolower(trim($valuepair[0])) == 'height') {
                    $height = trim($valuepair[1]);
                } else if (strtolower(trim($valuepair[0])) == 'align') {
                    $align = trim($valuepair[1]);
                }
            }
        }
        print_object('image url ' . $imageurl);
        $result[] = array('type' => 'image', 'value' => $imageurl, 'height' => $height, 'width' => $width, 'align' => $align);

        // Now add the remaining text after the image tag.
        $remaining = trim(substr($string, strpos($string, '>') + 1));
        if (!empty($remaining)) {
            $remainingblocks = offlinequiz_convert_newline_docx($remaining);
            $result = array_merge($result, $remainingblocks);
        }
    }
    return $result;
}

/**
 * Generates the DOCX question/correction form for an offlinequiz group.
 *
 * @param question_usage_by_activity $templateusage the template question  usage for this offline group
 * @param object $offlinequiz The offlinequiz object
 * @param object $group the offline group object
 * @param int $courseid the ID of the Moodle course
 * @param object $context the context of the offline quiz.
 * @param boolean correction if true the correction form is generated.
 * @return stored_file instance, the generated DOCX file.
 */
function offlinequiz_create_docx_question(question_usage_by_activity $templateusage, $offlinequiz, $group, $courseid, $context, $correction = false) {
    global $CFG, $DB, $OUTPUT;

    $letterstr = 'abcdefghijklmnopqrstuvwxyz';
    $groupletter = strtoupper($letterstr[$group->number - 1]);

    $coursecontext = context_course::instance($courseid);

    add_to_log($courseid, 'offlinequiz', 'createdocx question',
            "mod/offlinequiz.php?q=$offlinequiz->id",
            "$offlinequiz->id", $offlinequiz->id);

    $docx = new PHPWord();
    $trans = new offlinequiz_html_translator();

    // Define cell style arrays
    $styleCell = array('valign' => 'center');

    // Add text elements.
    // italic style
    $docx->addFontStyle('iStyle', array('italic' => true, 'size' => $offlinequiz->fontsize));
    // bold style
    $docx->addFontStyle('bStyle', array('bold' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('brStyle', array('bold' => true, 'align' => 'right', 'size' => $offlinequiz->fontsize));
    // underline style
    $docx->addFontStyle('uStyle', array('underline' => true, 'size' => $offlinequiz->fontsize));

    $docx->addFontStyle('ibStyle', array('italic' => true, 'bold' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('iuStyle', array('italic' => true, 'underline' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('buStyle', array('bold' => true, 'underline' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('ibuStyle', array('italic' => true, 'bold' => true, 'underline' => true, 'size' => $offlinequiz->fontsize));

    // header style
    $docx->addFontStyle('hStyle', array('bold' => true, 'size' => $offlinequiz->fontsize + 4));
    // center style
    $docx->addParagraphStyle('cStyle', array('align' => 'center', 'spaceAfter' => 100));
    $docx->addParagraphStyle('cStyle', array('align' => 'center', 'spaceAfter' => 100));

    // Define table style arrays
    $styleTable = array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 20, 'align' => 'center');
    $styleFirstRow = array('borderBottomSize' => 0, 'borderBottomColor' => 'FFFFFF', 'bgColor' => 'FFFFFF');
    $docx->addTableStyle('tableStyle', $styleTable, $styleFirstRow);

    // Define custom list item style for question answers
    $level1 = new PHPWord_Style_Paragraph();
    $level1->setTabs(new PHPWord_Style_Tabs(array(
            new PHPWord_Style_Tab('clear', 720),
            new PHPWord_Style_Tab('num', 360)
    )));
    $level1->setIndentions(new PHPWord_Style_Indentation(array(
            'left' => 360,
            'hanging' => 360
    )));

    $level2 = new PHPWord_Style_Paragraph();
    $level2->setTabs(new PHPWord_Style_Tabs(array(
            new PHPWord_Style_Tab('left', 720),
            new PHPWord_Style_Tab('num', 720)
    )));
    $level2->setIndentions(new PHPWord_Style_Indentation(array(
            'left' => 720,
            'hanging' => 360
    )));


    // Create the section that will be used for all outputs.
    $section = $docx->createSection();

    $title = offlinequiz_str_html_docx($offlinequiz->name);
    if (!empty($offlinequiz->time)) {
        if (strlen($title) > 35) {
            $title = substr($title, 0, 33) . ' ...';
        }
        $title .= ": ".offlinequiz_str_html_docx(userdate($offlinequiz->time));
    } else {
        if (strlen($title) > 40) {
            $title = substr($title, 0, 37) . ' ...';
        }
    }
    $title .= ",  " . offlinequiz_str_html_docx(get_string('group') . " $groupletter");

    // Add a header.
    $header = $section->createHeader();
    $header->addText($title, 'iStyle', 'cStyle' );
    $header->addImage($CFG->dirroot . '/mod/offlinequiz/pix/line.png', array('width' => 600, 'height' => 5, 'align' => 'center'));
    //$header->addText("___________________________________________________________________________");

    
    // Add a footer.
    $footer = $section->createFooter();
    $footer->addImage($CFG->dirroot . '/mod/offlinequiz/pix/line.png', array('width' => 600, 'height' => 5, 'align' => 'center'));
    $footer->addPreserveText($title . '  |  ' . get_string('page') . ' ' . '{PAGE} / {NUMPAGES}', null, array('align' => 'left'));

    // Print title page.
    if (!$correction) {
        $section->addText(offlinequiz_str_html_docx(get_string('questionsheet', 'offlinequiz') . ' - ' . get_string('group') . " $groupletter"),
                'hStyle', 'cStyle');
        $section->addTextBreak(2);

        $table = $section->addTable('tableStyle');
        $table->addRow();
        $cell = $table->addCell(200, $styleCell)->addText(offlinequiz_str_html_docx(get_string('name')) . ':  ', 'brStyle');
        //$cell = $table->addCell(200, $styleCell)->addText('  _______________________________________________');

        $table->addRow();
        $cell = $table->addCell(200, $styleCell)->addText(offlinequiz_str_html_docx(get_string('idnumber', 'offlinequiz')) . ':  ', 'brStyle');
        //$cell = $table->addCell(200, $styleCell)->addText('  _______________________________________________');

        $table->addRow();
        $cell = $table->addCell(200, $styleCell)->addText(offlinequiz_str_html_docx(get_string('studycode', 'offlinequiz')) . ':  ', 'brStyle');
        //$cell = $table->addCell(200, $styleCell)->addText('  _______________________________________________');

        $table->addRow();
        $cell = $table->addCell(200, $styleCell)->addText(offlinequiz_str_html_docx(get_string('signature', 'offlinequiz')) . ':  ', 'brStyle');
        //$cell = $table->addCell(200, $styleCell)->addText('  _______________________________________________');
        
        $section->addTextBreak(2);
        
        // The DOCX intro text can be arbitrarily long so we have to catch page overflows.
        if (!empty($offlinequiz->pdfintro)) {
            print_object($offlinequiz->pdfintro);
            $blocks = offlinequiz_convert_question_text_docx($offlinequiz->pdfintro);
            print_object($blocks);
            offlinequiz_print_blocks_docx($section, $blocks);
        }
        $section->addPageBreak();
    }

    // Load all the questions needed by this script.
    $layout = offlinequiz_get_group_questions($offlinequiz, $group->id);
    $pagequestions = explode(',', $layout);
    $questionlist = explode(',', str_replace(',0', '', $layout));

    if (!$questionlist) {
        echo $OUTPUT->box_start();
        echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
        echo $OUTPUT->box_end();
        return;
    }

    list($qsql, $params) = $DB->get_in_or_equal($questionlist);
    $params[] = $offlinequiz->id;

    $sql = "SELECT q.*, i.grade AS maxgrade, i.id AS instance, c.contextid
              FROM {question} q,
                   {offlinequiz_q_instances} i,
                   {question_categories} c
             WHERE q.id $qsql
               AND i.offlinequiz = ?
               AND q.id = i.question
               AND q.category=c.id";

    // Load the questions.
    if (!$questions = $DB->get_records_sql($sql, $params)) {
        echo $OUTPUT->box_start();
        echo $OUTPUT->error_text(get_string('noquestionsfound', 'offlinequiz', $groupletter));
        echo $OUTPUT->box_end();
        return;
    }

    // Load the question type specific information.
    if (!get_question_options($questions)) {
        print_error('Could not load question options');
    }

    // Restore the question sessions to their most recent states.
    // Creating new sessions where required.

    // $pagequestions = explode(',', $attempt->layout); //We replace $questionlist here to get pagebreakes
    if ($last = array_pop($pagequestions) != '0') {
        print_error('Last item is not pagebreak');
    }
    $number = 1;

    // we need a mapping from question IDs to slots, assuming that each question occurs only once.
    $slots = $templateusage->get_slots();

    $texfilteractive = $DB->get_field('filter_active', 'active', array('filter' => 'filter/tex', 'contextid' => 1));
    if ($texfilteractive) {
        $tex_filter = new filter_tex($context, array());
    }

    // If shufflequestions has been activated we go through the questions in the order determined by
    // the template question usage.
    if ($offlinequiz->shufflequestions) {
        foreach ($slots as $slot) {

            $slotquestion = $templateusage->get_question($slot);
            $myquestion = $slotquestion->id;

            set_time_limit(120);
            $question = $questions[$myquestion];

            /*****************************************************/
            /*  Either we print the question HTML */
            /*****************************************************/
            $questiontext = $question->questiontext;

            // Filter only for tex formulas.
            if (!empty($tex_filter)) {
                $questiontext = $tex_filter->filter($questiontext);
            }

            // Remove all HTML comments (typically from MS Office).
            $questiontext = preg_replace("/<!--.*?--\s*>/ms", "", $questiontext);

            // Remove <font> tags.
            $questiontext = preg_replace("/<font[^>]*>[^<]*<\/font>/ms", "", $questiontext);

            // Remove all class info from paragraphs because TCDOCX won't use CSS.
            $questiontext = preg_replace('/<p[^>]+class="[^"]*"[^>]*>/i', "<p>", $questiontext);

            $questiontext = $trans->fix_image_paths($questiontext, $question->contextid, 'questiontext', $question->id, 1, 300);
            $blocks = offlinequiz_convert_question_text_docx($questiontext, false, $number);
            offlinequiz_print_blocks_docx($section, $blocks);

            $advancedMultiLevel = new PHPWord_Numbering_AbstractNumbering("Adv Multi-level", array(
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_DECIMAL, "%1.", "left", $level1),
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_LOWER_LETTER, "%2)", "left", $level2)
            ));
            $docx->addNumbering($advancedMultiLevel);

            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {

                // Save the usage slot in the group questions table.
                $DB->set_field('offlinequiz_group_questions', 'usageslot', $slot,
                        array('offlinequizid' => $offlinequiz->id,
                                'offlinegroupid' => $group->id, 'questionid' => $question->id));

                // There is only a slot for multichoice questions.
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order of the answers.

                foreach ($order as $key => $answer) {
                    $answertext = $question->options->answers[$answer]->answer;
                    // Filter only for tex formulas.
                    if (!empty($tex_filter)) {
                        $answertext = $tex_filter->filter($answertext);
                    }
                    // Remove all HTML comments (typically from MS Office).
                    $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
                    // Remove all paragraph tags because they mess up the layout.
                    $answertext = preg_replace("/<p[^>]*>/ms", "", $answertext);
                    $answertext = preg_replace("/<\/p[^>]*>/ms", "", $answertext);
                    $answertext = $trans->fix_image_paths($answertext, $question->contextid, 'answer', $answer, 1, 200);
//                    $answertext = '     ' . $letterstr[$key] . ') ' . $answertext;
                    
                    //                     if ($correction) {
                    //                         if ($question->options->answers[$answer]->fraction > 0) {
                    //                             $html .= '<b>';
                    //                         }

                    //                         $answertext .= " (".round($question->options->answers[$answer]->fraction * 100)."%)";
                    //                     }

//                     $blocks = offlinequiz_convert_question_text_docx($answertext);
//                     offlinequiz_print_blocks_docx($section, $blocks);
                    $section->addListItem($answertext, 1, $advancedMultiLevel);

                    if ($offlinequiz->showgrades) {
                        $pointstr = get_string('points', 'grades');
                        if ($question->maxgrade == 1) {
                            $pointstr = get_string('point', 'offlinequiz');
                        }
                        $section->addText('(' . ($question->maxgrade + 0) . ' ' . $pointstr .')', 'bStyle');
                    }
                }
            }
            $section->addTextBreak();
            $number++;
        }
    } else { // not shufflequestions 
        // Compute mapping  questionid -> slotnumber.
        $questionslots = array();
        foreach ($slots as $slot) {
            $questionslots[$templateusage->get_question($slot)->id] = $slot;
        }
        
        // No shufflequestions, so go through the questions as they have been added to the offlinequiz group
        // We also add custom page breaks.
        foreach ($pagequestions as $myquestion) {

            if ($myquestion == '0') {
                $section->addPageBreak();
                continue;
            }

            set_time_limit(120);

            // Print the question.
            $question = $questions[$myquestion];

            $questiontext = $question->questiontext;

            // Filter only for tex formulas.
            if (!empty($tex_filter)) {
                $questiontext = $tex_filter->filter($questiontext);
            }

            // Remove all HTML comments (typically from MS Office).
            $questiontext = preg_replace("/<!--.*?--\s*>/ms", "", $questiontext);

            // Remove <font> tags.
            $questiontext = preg_replace("/<font[^>]*>[^<]*<\/font>/ms", "", $questiontext);

            // Remove all class info from paragraphs because TCDOCX won't use CSS.
            $questiontext = preg_replace('/<p[^>]+class="[^"]*"[^>]*>/i', "<p>", $questiontext);

            $questiontext = $trans->fix_image_paths($questiontext, $question->contextid, 'questiontext', $question->id, 1, 300);
            $blocks = offlinequiz_convert_question_text_docx($questiontext, false, $number);
            offlinequiz_print_blocks_docx($section, $blocks);

            $advancedMultiLevel = new PHPWord_Numbering_AbstractNumbering("Adv Multi-level", array(
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_DECIMAL, "%1.", "left", $level1),
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_LOWER_LETTER, "%2)", "left", $level2)
            ));
            $docx->addNumbering($advancedMultiLevel);

            if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {

                $slot = $questionslots[$myquestion];

                // Save the usage slot in the group questions table.
                $DB->set_field('offlinequiz_group_questions', 'usageslot', $slot,
                        array('offlinequizid' => $offlinequiz->id,
                                'offlinegroupid' => $group->id, 'questionid' => $question->id));

                // Now retrieve the order of the answers
                $slotquestion = $templateusage->get_question($slot);
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order of the answers.

                foreach ($order as $key => $answer) {
                    $answertext = $question->options->answers[$answer]->answer;
                    // Filter only for tex formulas.
                    if (!empty($tex_filter)) {
                        $answertext = $tex_filter->filter($answertext);
                    }
                    // Remove all HTML comments (typically from MS Office).
                    $answertext = preg_replace("/<!--.*?--\s*>/ms", "", $answertext);
                    // Remove all paragraph tags because they mess up the layout.
                    $answertext = preg_replace("/<p[^>]*>/ms", "", $answertext);
                    $answertext = preg_replace("/<\/p[^>]*>/ms", "", $answertext);
                    $answertext = $trans->fix_image_paths($answertext, $question->contextid, 'answer', $answer, 1, 200);
//                    $answertext = '     ' . $letterstr[$key] . ') ' . $answertext;
                    // Alternative would be a tab:
//                    $answertext = "\t" . $letterstr[$key] . ') ' . $answertext;
                    
                    //                     if ($correction) {
                    //                         if ($question->options->answers[$answer]->fraction > 0) {
                    //                             $html .= '<b>';
                    //                         }

                    //                         $answertext .= " (".round($question->options->answers[$answer]->fraction * 100)."%)";
                    //                     }

//                    $blocks = offlinequiz_convert_question_text_docx($answertext);
  //                  offlinequiz_print_blocks_docx($section, $blocks);


                    $section->addListItem($answertext, 1, $advancedMultiLevel);
                    
                    //$section->addListItem($answertext, 0, null, $listStyle );

                    if ($offlinequiz->showgrades) {
                        $pointstr = get_string('points', 'grades');
                        if ($question->maxgrade == 1) {
                            $pointstr = get_string('point', 'offlinequiz');
                        }
                        $section->addText('(' . ($question->maxgrade + 0) . ' '. $pointstr .')', 'bStyle');
                    }
                }
                $section->addTextBreak();
                $number++;
            } // end if multichoice 
        } // end forall questions
    } // end else no shufflequestions

    //     //  $DB->delete_records('files', array());
            //     $docxstring = $docx->Output('', 'S');

            //     $file = $fs->create_file_from_string($fileinfo, $docxstring);
            //     $docx->remove_temp_files();

    $fs = get_file_storage();

    $fileprefix = 'form';
    if ($correction) {
        $fileprefix = 'correction';
    }

    if (file_exists($CFG->dataroot . '/questions.docx')) {
        unlink($CFG->dataroot . '/questions.docx');
    }
    // Save File
    $objWriter = PHPWord_IOFactory::createWriter($docx, 'Word2007');
    $objWriter->save($CFG->dataroot . '/questions.docx');

    // Prepare file record object.
    $fileinfo = array(
            'contextid' => $context->id, // ID of context.
            'component' => 'mod_offlinequiz',     // usually = table name.
            'filearea' => 'pdfs',     // usually = table name.
            'filepath' => '/',
            'itemid' => 0,           // usually = ID of row in table.
            'filename' => $fileprefix . '-' . strtolower($groupletter) . '.docx'); // any filename

    // delete existing old files, should actually not happen. 
    if ($oldfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
        $oldfile->delete();
    }

    $file = $fs->create_file_from_pathname($fileinfo, $CFG->dataroot . '/questions.docx');

    return $file;
}



/**
 * Function to transform Moodle HTML code of a question into proprietary markup that only supports italic, underline and bold.
 *
 * @param unknown_type $input The input text.
 * @param unknown_type $stripalltags Whether all tags should be stripped.
 * @param unknown_type $questionid The ID of the question the text stems from.
 * @param unknown_type $coursecontextid The course context ID.
 * @return mixed
 */
function offlinequiz_str_html_docx($input) {
    global $CFG;

    $output = $input;

    // Replace linebreaks.
    $output = preg_replace('!<br>!i', "\n", $output);
    $output = preg_replace('!<br />!i', "\n", $output);
    $output = preg_replace('!</p>!i', "\n", $output);

    // First replace the plugin image tags.
    $output = str_replace('[', '(', $output);
    $output = str_replace(']', ')', $output);
    $strings = preg_split("/<img/i", $output);
    $output = array_shift($strings);
    foreach ($strings as $string) {
        $output.= '[*p ';
        $imagetag = substr($string, 0, strpos($string, '>'));
        $attributes = explode(' ', $imagetag);
        foreach ($attributes as $attribute) {
            $valuepair = explode('=', $attribute);
            if (strtolower(trim($valuepair[0])) == 'src') {
                $pluginfilename = str_replace('"', '', str_replace("'", '', $valuepair[1]));
                $pluginfilename = str_replace('@@PLUGINFILE@@/', '', $pluginfilename);
                $file = $fs->get_file($coursecontextid, 'question', 'questiontext', $questionid, '/', $pluginfilename);
                // Copy file to temporary file.
                $output .= $file->get_id(). ']';
            }
        }
        $output .= substr($string, strpos($string, '>')+1);
    }
    $strings = preg_split("/<span/i", $output);
    $output = array_shift($strings);
    foreach ($strings as $string) {
        $tags = preg_split("/<\/span>/i", $string);
        $styleinfo = explode('>', $tags[0]);
        $style = array();
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
        $output .= implode($style).implode($styleinfo, '>');
        rsort($style);
        $output .= implode($style);
        if (!empty($tags[1])) {
            $output .=$tags[1];
        }
    }

    $search  = array('/<i[ ]*>(.*?)<\/i[ ]*>/smi', '/<b[ ]*>(.*?)<\/b[ ]*>/smi', '/<em[ ]*>(.*?)<\/em[ ]*>/smi',
            '/<strong[ ]*>(.*?)<\/strong[ ]*>/smi', '/<u[ ]*>(.*?)<\/u[ ]*>/smi',
            '/<sub[ ]*>(.*?)<\/sub[ ]*>/smi', '/<sup[ ]*>(.*?)<\/sup[ ]*>/smi' );
    $replace = array('[*i]\1[*i]', '[*b]\1[*b]', '[*i]\1[*i]',
            '[*b]\1[*b]', '[*u]\1[*u]',
            '[*l]\1[*l]', '[*h]\1[*h]');
    $output = preg_replace($search, $replace, $output);

    $output = strip_tags($output);

    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');
    $result = str_ireplace($search, $replace, $output);

    return $result;
}
