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
 * Creates DB-entries and PDF forms for offlinequizzes
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('locallib.php');
require_once('pdflib.php');
require_once($CFG->libdir . '/questionlib.php');

$id = optional_param('id', 0, PARAM_INT);               // Course Module ID
$q = optional_param('q', 0, PARAM_INT);                 // or offlinequiz ID
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // reshuffle questions
$forcepdfnew = optional_param('forcepdfnew', false, PARAM_BOOL); // recreate pdfs
$mode = optional_param('mode', 'preview', PARAM_ALPHA);        // mode

$letterstr = 'ABCDEFGHIJKL';

if ($id) {
    if (!$cm = get_coursemodule_from_id('offlinequiz', $id)) {
        print_error("There is no coursemodule with id $id");
    }

    if (!$course = $DB->get_record("course", array('id' => $cm->course))) {
        print_error("Course is misconfigured");
    }

    if (!$offlinequiz = $DB->get_record("offlinequiz", array('id' => $cm->instance))) {
        print_error("The offlinequiz with id $cm->instance corresponding to this coursemodule $id is missing");
    }

} else {
    if (! $offlinequiz = $DB->get_record("offlinequiz", array('id' => $q))) {
        print_error("There is no offlinequiz with id $q");
    }
    if (! $course = $DB->get_record("course", array('id' => $offlinequiz->course))) {
        print_error("The course with id $offlinequiz->course that the offlinequiz with id $q belongs to is missing");
    }
    if (! $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
        print_error("The course module for the offlinequiz with id $q is missing");
    }
}

$offlinequiz->optionflags = 0;

require_login($course->id, false, $cm);
if (!$context = context_module::instance($cm->id)) {
    print_error("The context for the course module with ID $cm->id is missing");
}
$offlinequiz->cmid = $cm->id;

$coursecontext = context_course::instance($course->id);

// We redirect students to info
if (!has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    redirect('view.php?q='.$offlinequiz->id);
}

// if not in all group questions have been set up yet redirect to edit.php
// TODO
/* $emptygroups=offlinequiz_empty_groups ($offlinequiz->id); */
/* if (count($emptygroups) > 0 and has_capability('mod/offlinequiz:manage', $context)) { */
/*     redirect('edit.php?cmid='.$quiz->id.'&amp;groupid='.$emptygroups[0]); */
/* } */

offlinequiz_load_useridentification();

$strpreview = get_string('createquiz', 'offlinequiz');
$strofflinequizzes = get_string("modulenameplural", "offlinequiz");

$PAGE->set_url('/mod/offlinequiz/createquiz.php?id=' . $cm->id);
$PAGE->set_title($strpreview);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report'); // or 'admin'?
$PAGE->set_cacheable(true);

if ($node = $PAGE->settingsnav->find('mod_offlinequiz_createquiz', navigation_node::TYPE_SETTING)) {
    $node->make_active();
}

// Print the page header
// $strupdatemodule = has_capability('moodle/course:manageactivities', $coursecontext)
//    ? $OUTPUT->update_module_button($cm->id, 'offlinequiz') : "";

// $PAGE->set_button($strupdatemodule);

echo $OUTPUT->header();

// Print the offlinequiz name heading and tabs for teacher
$currenttab = 'createofflinequiz';

require('tabs.php');

// echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'; // for overlib

if (!$groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
    print_error('There are no offlinequiz groups', "edit.php?q=$offlinequiz->id$amp;sesskey=".sesskey());
}

$hasscannedpages = offlinequiz_has_scanned_pages($offlinequiz->id);

if ($offlinequiz->grade == 0) {
    echo '<div class="linkbox"><strong>';
    echo $OUTPUT->notification(get_string('gradeiszero', 'offlinequiz'), 'notifyproblem');
    echo '</strong></div>';
}

// Preview
if ($mode == 'preview') {
    // Print shuffle again buttons
    if (!$offlinequiz->docscreated && !$hasscannedpages) {

        echo $OUTPUT->heading(get_string('formspreview', 'offlinequiz'));

        echo $OUTPUT->box_start('generalbox controlbuttonbox');

        unset($buttonoptions);
        $buttonoptions = array();
        $buttonoptions['q'] = $offlinequiz->id;
        $buttonoptions['forcenew'] = true;
        $buttonurl = new moodle_url('/mod/offlinequiz/createquiz.php', $buttonoptions);

        echo '<div class="controlbuttons linkbox">';
        if ($offlinequiz->shufflequestions and $offlinequiz->shuffleanswers) {
            echo $OUTPUT->single_button($buttonurl,  get_string('shufflequestionsanswers', 'offlinequiz').' / '.get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else if ($offlinequiz->shufflequestions) {
            echo $OUTPUT->single_button($buttonurl,  get_string('shufflequestions', 'offlinequiz').' / '.get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else if ($offlinequiz->shuffleanswers) {
            echo $OUTPUT->single_button($buttonurl,  get_string('shuffleanswers', 'offlinequiz').' / '.get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else {
            echo $OUTPUT->single_button($buttonurl,  get_string('reloadquestionlist', 'offlinequiz'));
        }

        echo '</div>';

        echo $OUTPUT->box_end();
    }

    // Shuffle again if no scanned pages
    if ($forcenew) {
        if ($offlinequiz->docscreated || $hasscannedpages) {
            echo $OUTPUT->notification(get_string('formsexist', 'offlinequiz'), 'notifyproblem');
        } else {
            $offlinequiz = offlinequiz_delete_template_usages($offlinequiz);
            $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups);
        }
    }

    $done = 0;
    // Process group data
    foreach ($groups as $group) {
        $groupletter = $letterstr[$group->number - 1];

        // $progressbar->update($done++, sizeof($groups), 'foobar');

        // Print the group heading
        echo $OUTPUT->heading(get_string('previewforgroup', 'offlinequiz', $groupletter));

        echo $OUTPUT->box_start('generalbox groupcontainer');

        $layout = offlinequiz_get_group_questions($offlinequiz, $group->id);

        $pagequestions = explode(',', $layout);
        $questionlist = explode(',', str_replace(',0', '', $layout));

        if (!$questionlist) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/edit.php',
                    array('cmid' => $cm->id, 'groupnumber' => $group->number, 'noquestions' => 1));
            echo html_writer::link($url,  get_string('noquestionsfound', 'offlinequiz', $groupletter),
                    array('class' => 'notifyproblem'));
            echo $OUTPUT->box_end();
            continue;
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

        // Load the questions
        if (!$questions = $DB->get_records_sql($sql, $params)) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/edit.php',
                    array('cmid' => $cm->id, 'groupnumber' => $group->number, 'noquestions' => 1));
            echo html_writer::link($url,  get_string('noquestionsfound', 'offlinequiz', $groupletter),
                    array('class' => 'notifyproblem linkbox'));
            echo $OUTPUT->box_end();
            continue;
        }
        // Load the question type specific information
        if (!get_question_options($questions)) {
            print_error('Could not load question options');
        }

        // get or create a question usage for this offline group
        if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
            echo $OUTPUT->notification(get_string('missingquestions', 'offlinequiz'), 'notifyproblem');
            echo $OUTPUT->box_end();
            echo $OUTPUT->footer();
            continue;
        }
        if (!$slots = $templateusage->get_slots()) {
            echo $OUTPUT->box_start('notify');
            echo $OUTPUT->error_text(get_string('nomcquestions', 'offlinequiz', $groupletter));
            echo $OUTPUT->box_end();
        }

        // $pagequestions = explode(',', offlinequiz_get_group_questions($offlinequiz, $group->id));
        // we need a mapping from question IDs to slots, assuming that each question occurs only once.
        $questionslots = array();
        foreach ($slots as $qid => $slot) {
            $questionslots[$templateusage->get_question($slot)->id] = $slot;
        }

        $questionnumber = 1;
        if ($offlinequiz->shufflequestions) {
            foreach ($slots as $slot) {
                $slotquestion = $templateusage->get_question($slot);
                $question = $questions[$slotquestion->id];
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // order
                offlinequiz_print_question_preview($question, $order, $questionnumber, $context);
                // Note: we don't have description questions in quba slots.
                $questionnumber++;
            }
        } else {
            foreach ($pagequestions as $myquestion) {
                if ($myquestion == 0) {
                    echo '<center>//---------------------- ' . get_string('newpage', 'offlinequiz') . ' ----------------//</center>';
                } else {
                    $question = $questions[$myquestion];

                    $order = array();
                    if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                        $slot = $questionslots[$myquestion];
                        $slotquestion = $templateusage->get_question($slot);
                        $attempt = $templateusage->get_question_attempt($slot);
                        $order = $slotquestion->get_order($attempt);  // order
                    }

                    // use our own function to print the preview.
                    offlinequiz_print_question_preview($question, $order, $questionnumber, $context);
                    if ($question->qtype != 'description') {
                        $questionnumber++;
                    }
                }
            }
        }

        echo $OUTPUT->box_end();
    }// end foreach

    // ==============================================================
    //  TAB for creating, downloading and deleting PDF forms
    // ==============================================================
} else if ($mode=='createpdfs') {

    // Print the heading
    echo $OUTPUT->heading(get_string('downloadpdfs', 'offlinequiz'));

    $emptygroups = offlinequiz_get_empty_groups($offlinequiz);
    if (!empty($emptygroups)) {
        echo $OUTPUT->box_start('linkbox');
        foreach ($emptygroups as $groupnumber) {
            $groupletter = $letterstr[$groupnumber -1];
            echo $OUTPUT->notification(get_string('noquestionsfound', 'offlinequiz', $groupletter), 'notifyproblem');
        }
        echo $OUTPUT->notification(get_string('nopdfscreated', 'offlinequiz'), 'notifyproblem');
        echo $OUTPUT->box_end();

        echo $OUTPUT->footer();
        return true;
    }

    // Print buttons for delete/recreate iff there are no scanned pages yet.
    if (!$hasscannedpages) {
        echo $OUTPUT->box_start('generalbox linkbox');

        unset($buttonoptions);
        $buttonoptions['q'] = $offlinequiz->id;
        $buttonoptions['mode'] = 'createpdfs';
        $buttonurl = new moodle_url('/mod/offlinequiz/createquiz.php', $buttonoptions);
        if ($forcepdfnew) {
            echo '<div class="linkbox">';
            echo $OUTPUT->single_button($buttonurl,  get_string('createpdfforms', 'offlinequiz'), 'get');
            echo '</div>';
        } else {
            ?>
            <div class="singlebutton linkbox">
    	        <form action="<?php echo "$CFG->wwwroot/mod/offlinequiz/createquiz.php?q=".$offlinequiz->id."&mode=createpdfs" ?>" method="POST">
                    <div>
    			        <input type="hidden" name="forcepdfnew" value="1" /> 
			            <input type="submit" value="<?php echo get_string('deletepdfs', 'offlinequiz') ?>"
				         onClick='return confirm("<?php echo get_string('realydeletepdfs', 'offlinequiz') ?>")' />
                    </div>
	             </form>
            </div>
            <?php
        }
        echo $OUTPUT->box_end();
    } // end if (!$completedresults

    $fs = get_file_storage();

    // Delete the PDF forms if forcepdfnew and if there are not scanned pages yet.
    if ($forcepdfnew) {
        if ($hasscannedpages) {
            print_error('Some answer forms have already been analysed', "createquiz.php?q=$offlinequiz->id&amp;mode=createpdfs&amp;sesskey=".sesskey());
        } else {
            $offlinequiz = offlinequiz_delete_pdf_forms($offlinequiz);
        }
    }


    // options for the popup_action
    $options = array();
    $options['height'] = 1200; // optional
    $options['width'] = 1170; // optional

    // ============================================================
    // show/create the question forms for the offline groups.
    // ============================================================
    if (!$forcepdfnew) {
        echo $OUTPUT->box_start('generalbox linkbox');

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number -1];

            if (!$offlinequiz->docscreated) {
                if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                    print_error("Missing data for group ".$groupletter, "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey());
                }

                if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
                    require_once('docxlib.php');
                    $questionfile = offlinequiz_create_docx_question($templateusage, $offlinequiz, $group, $course->id, $context);
                } else {
                    $questionfile = offlinequiz_create_pdf_question($templateusage, $offlinequiz, $group, $course->id, $context);
                }
            } else {
                if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
				    $questionfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', 'form-' . strtolower($groupletter) . '.docx');
				} else {
				    $questionfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', 'form-' . strtolower($groupletter) . '.pdf');
				}
            }
            
            if ($questionfile) {
                $filestring = get_string('formforgroup', 'offlinequiz', $groupletter);
                if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
                    $filestring = get_string('formforgroupdocx', 'offlinequiz', $groupletter);
                }
                $url = "$CFG->wwwroot/pluginfile.php/" . $questionfile->get_contextid() . '/' . $questionfile->get_component() . '/' .
                        $questionfile->get_filearea() . '/' . $questionfile->get_itemid() . '/' . $questionfile->get_filename() . '?forcedownload=1';
                
                echo $OUTPUT->action_link($url, $filestring);
                echo '<br />&nbsp;<br />';
                @flush();@ob_flush();
            } else {
                echo $OUTPUT->notification(get_string('createpdferror', 'offlinequiz', $groupletter));
            }
        }
        echo $OUTPUT->box_end();

        // ============================================================
        // show/create the answer forms for all offline groups.
        // ============================================================
        echo $OUTPUT->box_start('generalbox linkbox');

        echo $OUTPUT->notification(get_string('marginwarning', 'offlinequiz'));
        echo '<br/>';

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number -1];

            if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                print_error("Missing data for group " . $groupletter, "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey());
            }

            if (!$offlinequiz->docscreated) {
                $answerpdffile = offlinequiz_create_pdf_answer(offlinequiz_get_maxanswers($offlinequiz, array($group)), $templateusage, $offlinequiz, $group, $course->id, $context);
            } else {
                $answerpdffile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', 'answer-' . strtolower($groupletter) . '.pdf');
            }

            if ($answerpdffile) {
                $url = "$CFG->wwwroot/pluginfile.php/" . $answerpdffile->get_contextid() . '/' . $answerpdffile->get_component() . '/' .
                        $answerpdffile->get_filearea() . '/' . $answerpdffile->get_itemid() . '/' . $answerpdffile->get_filename() . '?forcedownload=1';
                echo $OUTPUT->action_link($url, get_string('answerformforgroup', 'offlinequiz', $groupletter));
                // , new popup_action('click', $url, 'answer' . $answerpdffile->get_id(), $options));
                echo '<br />&nbsp;<br />';
                @flush();@ob_flush();
            } else {
                echo $OUTPUT->notification(get_string('createpdferror', 'offlinequiz', $groupletter));
            }
        }


        echo $OUTPUT->box_end();

        // ============================================================
        // show/create the correction forms for all offline groups.
        // ============================================================
        echo $OUTPUT->box_start('generalbox linkbox');

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number -1];

            if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                print_error("Missing data for group ".$groupletter, "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey());
            }

            if (!$offlinequiz->docscreated) {
                $correctpdffile = offlinequiz_create_pdf_question($templateusage, $offlinequiz, $group, $course->id, $context, true);
            } else {
                $correctpdffile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', 'correction-' . strtolower($groupletter) . '.pdf');
            }

            if ($correctpdffile) {
                $url = "$CFG->wwwroot/pluginfile.php/" . $correctpdffile->get_contextid() . '/' . $correctpdffile->get_component() . '/' .
                        $correctpdffile->get_filearea() . '/' . $correctpdffile->get_itemid() . '/' . $correctpdffile->get_filename() . '?forcedownload=1';
                echo $OUTPUT->action_link($url, get_string('formforcorrection', 'offlinequiz', $groupletter));
                // , new popup_action('click', $url, 'correction' . $correctpdffile->get_id(), $options));

                echo '<br />&nbsp;<br />';
                @flush();@ob_flush();

            } else {
                echo $OUTPUT->notification(get_string('createpdferror', 'offlinequiz', $groupletter));
            }
        }


        echo $OUTPUT->box_end();

        // Remember that we have created the documents.
        $offlinequiz->docscreated = 1;
        $DB->set_field('offlinequiz', 'docscreated', 1, array('id' => $offlinequiz->id));
    }
}

// Finish the page
echo $OUTPUT->footer();
