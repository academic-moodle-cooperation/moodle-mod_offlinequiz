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
 * Blocks can be of type string, newline or image.
 * If the first block is a string it is printed as a listitem using the numbering and depth provided.
 * Otherwise, the empty string is used as the list item string.
 * 
 * @param PHPWord_Section $section A PHP Word section
 * @param array $blocks The array of blocks as created by the conversion functions.
 * @param PHPWord_Numbering_AbstractNumbering $numbering The numbering used for the list item
 * @param int $depth The depth in the enumeration (0 for questions, 1 for answers).
 */
function offlinequiz_print_blocks_docx(PHPWord_Section $section, $blocks, $numbering = null, $depth = 0) {
     
    // We skip leading newlines.
    while ($blocks[0]['type'] == 'newline') {
        array_shift($blocks);
    }
    
    // First print the list item string.
    if (!empty($numbering)) {
        $itemstring = ' ';
        if ($blocks[0]['type'] == 'string') {
            $itemstring = $blocks[0]['value'];
            array_shift($blocks);
        }
        $section->addListItem($itemstring, $depth, $numbering, 'nStyle');
        
        // We also skip the first sequential newline because we got a newline with addListItem
        if (!empty($blocks) && $blocks[0]['type'] == 'newline') {
            array_shift($blocks);
        }
    }

    // Now we go through the rest of the blocks (if there are any) and print them to a textrun.
    if (!empty($blocks)) {
        if (empty($numbering)) {
            $textrun = $section->createTextRun();
        } else {
            $textrun = $section->createTextRun('questionTab');
            $textrun->addText("\t", 'nStyle');
        }
        foreach($blocks as $block) {
            if ($block['type'] == 'string') {
                if (!empty($block['style'])) {
                    $textrun->addText($block['value'], $block['style']);
                } else {
                    $textrun->addText($block['value'], 'nStyle');
                }
            } else if ($block['type'] == 'newline') {
                if (empty($numbering)) {
                    $textrun = $section->createTextRun();
                } else {
                    $textrun = $section->createTextRun('questionTab');
                    $textrun->addText("\t", 'nStyle');
                }
            } else if ($block['type'] == 'image') {
                // Retrieve the style info.
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

                // Now add the image and start a new textrun.
                $section->addImage($block['value'], $style);
                if (empty($numbering)) {
                    $textrun = $section->createTextRun();
                } else {
                    $textrun = $section->createTextRun('questionTab');
                    $textrun->addText("\t", 'nStyle');
                }
            }
        }
    }
}

/**
 * Function to convert underline characters (HTML <span ...> tags) into string blocks with underline style.
 *  
 * @param string $text
 */
function offlinequiz_convert_underline_text_docx($text) {
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Now add the remaining text after the image tag.
    $parts = preg_split('/<span style="text-decoration: underline;">/i', $text);
    $result = array();
    
    $firstpart = array_shift($parts);
    if (!empty($firstpart)) {
        $firstpart = strip_tags($firstpart);
        $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $firstpart));
    }

    foreach($parts as $part) {
        $closetagpos = strpos($part, '</span>');

        $underlinetext = strip_tags(substr($part, 0, $closetagpos));
        $underlineremain = strip_tags(trim(substr($part, $closetagpos + 7)));
         
        $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $underlinetext), 'style' => 'uStyle');
        if (!empty($underlineremain)) {
            $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $underlineremain));
        }
    }
    return $result;
}


/**
 * Function to convert bold characters (HTML <b> tags) into string blocks with bold style.
 *  
 * @param string $text
 */
function offlinequiz_convert_bold_text_docx($text) {
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Now add the remaining text after the image tag.
    $parts = preg_split("/<b>|<strong>/i", $text); 
    $result = array();
    
    $firstpart = array_shift($parts);
    if (!empty($firstpart)) {
        $result = offlinequiz_convert_underline_text_docx($firstpart);
    }

    foreach($parts as $part) {
        if ($closetagpos = strpos($part, '</b>')) {
            $boldremain = trim(substr($part, $closetagpos + 4));
        } else if ($closetagpos = strpos($part, '</strong>')) {
            $boldremain = trim(substr($part, $closetagpos + 9));
        } else {
            $closetagpos = strlen($part) - 1;
            $boldremain = '';            
        }
        $boldtext = strip_tags(trim(substr($part, 0, $closetagpos)));

        $result[] = array('type' => 'string', 'value' => str_ireplace($search, $replace, $boldtext), 'style' => 'bStyle');
        if (!empty($boldremain)) {
            $boldremainblocks = offlinequiz_convert_underline_text_docx($boldremain);
            $result = array_merge($result, $boldremainblocks);
        }
    }
    return $result;
}

/**
 * Function to convert line breaks (HTML <br/> tags) into line break blocks for rendering.
 * 
 * @param string $text
 */
function offlinequiz_convert_newline_docx($text) {
    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');

    // Now add the remaining text after the image tag.
    $parts = preg_split("!<br>|<br />!i", $text);
    $result = array();
    
    $firstpart = array_shift($parts);
    // If the original text was only a newline, we don't have a first part.
    if (!empty($firstpart)) {
        if ($firstpart == '<br/>' || $firstpart == '<br />') {
            $result = array('type' => 'newline');
        } else {
            $result = offlinequiz_convert_bold_text_docx($firstpart);
        }
    }
    
    foreach($parts as $part) {
        $result[] = array('type' => 'newline');
        if (!empty($part)) {
            $newlineremainblocks = offlinequiz_convert_bold_text_docx($part);
            $result = array_merge($result, $newlineremainblocks);
        }
    }
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
function offlinequiz_convert_image_docx($text) {
    global $CFG;

    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');
    $result = array();

    // Remove paragraphs.
    $text = preg_replace('!<p>!i', '', $text);
    $text = preg_replace('!</p>!i', '', $text);

    // First add all the text that appears before the image tag.
    $strings = preg_split("/<img/i", $text);
    $firstline = array_shift($strings);
    if (!empty($firstline)) {
        $result = offlinequiz_convert_newline_docx($firstline);
    }
    
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
            $valuepair[0] = strtolower(trim($valuepair[0]));
            
            if (!empty($valuepair[0])) {
                if ($valuepair[0] == 'src') {
                    $imageurl = str_replace('file://', '', str_replace('"', '', str_replace("'", '', $valuepair[1])));
                } else {
                    $valuepair[1] = trim(preg_replace('!"!i', '', $valuepair[1]));
                    $valuepair[1] = trim(preg_replace('!/!i', '', $valuepair[1]));
                    if ($valuepair[0] == 'width') {
                        $width = trim($valuepair[1]);
                    } else if ($valuepair[0] == 'height') {
                        $height = trim($valuepair[1]);
                    } else if ($valuepair[0] == 'align') {
                        $align = trim($valuepair[1]);
                    }
                }
            }
        }
        $result[] = array('type' => 'image', 'value' => $imageurl, 'height' => $height, 'width' => $width, 'align' => $align);

        // Now add the remaining text after the image tag.
        $remaining = substr($string, strpos($string, '>') + 1);
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
    
    PHPWord_Media::resetMedia();
    
    $docx = new PHPWord();
    $trans = new offlinequiz_html_translator();

    // Define cell style arrays
    $styleCell = array('valign' => 'center');

    // Add text styles.
    // Normal style
    $docx->addFontStyle('nStyle', array('size' => $offlinequiz->fontsize));
    // italic style
    $docx->addFontStyle('iStyle', array('italic' => true, 'size' => $offlinequiz->fontsize));
    // bold style
    $docx->addFontStyle('bStyle', array('bold' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('brStyle', array('bold' => true, 'align' => 'right', 'size' => $offlinequiz->fontsize));
    // underline style
    $docx->addFontStyle('uStyle', array('underline' => PHPWord_Style_Font::UNDERLINE_SINGLE, 'size' => $offlinequiz->fontsize));

    $docx->addFontStyle('ibStyle', array('italic' => true, 'bold' => true, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('iuStyle', array('italic' => true, 'underline' =>  PHPWord_Style_Font::UNDERLINE_SINGLE, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('buStyle', array('bold' => true, 'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE, 'size' => $offlinequiz->fontsize));
    $docx->addFontStyle('ibuStyle', array('italic' => true, 'bold' => true, 'underline' => PHPWord_Style_Font::UNDERLINE_SINGLE, 'size' => $offlinequiz->fontsize));

    // header style
    $docx->addFontStyle('hStyle', array('bold' => true, 'size' => $offlinequiz->fontsize + 4));
    // center style
    $docx->addParagraphStyle('cStyle', array('align' => 'center', 'spaceAfter' => 100));
    $docx->addParagraphStyle('cStyle', array('align' => 'center', 'spaceAfter' => 100));
    $docx->addParagraphStyle('questionTab', array(
            'tabs' => array(
                    new PHPWord_Style_Tab("left", 360)
            )
    ));

    // Define table style arrays
    $styleTable = array('borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 20, 'align' => 'center');
    $styleFirstRow = array('borderBottomSize' => 0, 'borderBottomColor' => 'FFFFFF', 'bgColor' => 'FFFFFF');
    $docx->addTableStyle('tableStyle', $styleTable, $styleFirstRow);

    $boldfont = new PHPWord_Style_Font();
    $boldfont->setBold(true);
    $boldfont->setSize($offlinequiz->fontsize);

    $normalfont = new PHPWord_Style_Font();
    $normalfont->setSize($offlinequiz->fontsize);

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
    $header->addText($title, array('size' => 10), 'cStyle' );
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
            $blocks = offlinequiz_convert_image_docx($offlinequiz->pdfintro);
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
               AND i.offlinequizid = ?
               AND q.id = i.questionid
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

    // Create the docx question numbering. This is only created once since we number all questions from 1...n
    $questionnumbering = new PHPWord_Numbering_AbstractNumbering("Question-level", array(
            new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_DECIMAL, "%1)", "left", $level1, $boldfont),
            new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_LOWER_LETTER, "%2)", "left", $level2, $normalfont)
        ));
    $docx->addNumbering($questionnumbering);

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

            $questiontext = $trans->fix_image_paths($questiontext, $question->contextid, 'questiontext', $question->id, 0.2, 400);

            $blocks = offlinequiz_convert_image_docx($questiontext);
            offlinequiz_print_blocks_docx($section, $blocks, $questionnumbering, 0);
            
            $answernumbering = new PHPWord_Numbering_AbstractNumbering("Adv Multi-level", array(
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_DECIMAL, "%1.", "left", $level1, $boldfont),
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_LOWER_LETTER, "%2)", "left", $level2, $normalfont)
            ));
            $docx->addNumbering($answernumbering);

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

                    $blocks = offlinequiz_convert_image_docx($answertext);
                    offlinequiz_print_blocks_docx($section, $blocks, $answernumbering, 1);

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
         
        // We have to compute the mapping  questionid -> slotnumber.
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

            $questiontext = $trans->fix_image_paths($questiontext, $question->contextid, 'questiontext', $question->id, 0.2, 400);

            $blocks = offlinequiz_convert_image_docx($questiontext);

            // Description questions are printed without a number because they are not on the answer form.
            if ($question->qtype == 'description') {
                offlinequiz_print_blocks_docx($section, $blocks);
            } else {
                offlinequiz_print_blocks_docx($section, $blocks, $questionnumbering, 0);
            }
            
            $answernumbering = new PHPWord_Numbering_AbstractNumbering("Adv Multi-level", array(
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_DECIMAL, "%1.", "left", $level1),
                    new PHPWord_Numbering_Level("1", PHPWord_Numbering_Level::NUMFMT_LOWER_LETTER, "%2)", "left", $level2)
            ));
            $docx->addNumbering($answernumbering);

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

                    $blocks = offlinequiz_convert_image_docx($answertext);
                    offlinequiz_print_blocks_docx($section, $blocks, $answernumbering, 1);
                    
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
                // End if multichoice.
            } 
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

    srand(microtime()*1000000);
    $unique = str_replace('.', '', microtime(true) . rand(0, 100000));
    $tempfilename = $CFG->dataroot."/temp/offlinequiz/" . $unique . '.docx';
    
    if (file_exists($tempfilename)) {
        unlink($tempfilename);
    }
    // Save File
    $objWriter = PHPWord_IOFactory::createWriter($docx, 'Word2007');
    $objWriter->save($tempfilename);

    // Prepare file record object.
    $fileinfo = array(
            'contextid' => $context->id, // ID of context.
            'component' => 'mod_offlinequiz',     // usually = table name.
            'filearea' => 'pdfs',     // usually = table name.
            'filepath' => '/',
            'itemid' => 0,           // usually = ID of row in table.
            'filename' => $fileprefix . '-' . strtolower($groupletter) . '.docx'); // any filename

    // Delete existing old files, should actually not happen. 
    if ($oldfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
            $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])) {
        $oldfile->delete();
    }

    // Create a Moodle file from the temporary file.
    $file = $fs->create_file_from_pathname($fileinfo, $tempfilename);

    // Remove all temporary files.
    unlink($tempfilename);
    $trans->remove_temp_files();

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
//     $output = preg_replace('!<br>!i', "\n", $output);
//     $output = preg_replace('!<br />!i', "\n", $output);

    // First replace the plugin image tags.
    $output = str_replace('[', '(', $output);
    $output = str_replace(']', ')', $output);

    $output = strip_tags($output);

    $search  = array('&quot;', '&amp;', '&gt;', '&lt;');
    $replace = array('"', '&', '>', '<');
    $result = str_ireplace($search, $replace, $output);

    return $result;
}
