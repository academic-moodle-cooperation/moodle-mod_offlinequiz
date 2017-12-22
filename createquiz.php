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
 * Creates DB-entries and PDF forms for offlinequizzes
 *
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('locallib.php');
require_once('pdflib.php');
require_once($CFG->libdir . '/questionlib.php');

$id = optional_param('id', 0, PARAM_INT);               // Course Module ID.
$q = optional_param('q', 0, PARAM_INT);                 // Or offlinequiz ID.
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Reshuffle questions.
$forcepdfnew = optional_param('forcepdfnew', false, PARAM_BOOL); // Recreate PDFs.
$mode = optional_param('mode', 'preview', PARAM_ALPHA);        // Mode.
$downloadall = optional_param('downloadall' , false, PARAM_BOOL);

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

// We redirect students to info.
if (!has_capability('mod/offlinequiz:createofflinequiz', $context)) {
    redirect('view.php?q='.$offlinequiz->id);
}

// If not in all group questions have been set up yet redirect to edit.php.
offlinequiz_load_useridentification();

$strpreview = get_string('createquiz', 'offlinequiz');
$strofflinequizzes = get_string("modulenameplural", "offlinequiz");

$PAGE->set_url('/mod/offlinequiz/createquiz.php?id=' . $cm->id);
$PAGE->set_title($strpreview);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report'); // Or 'admin'.
$PAGE->set_cacheable(true);
$PAGE->force_settings_menu(true);

if ($node = $PAGE->settingsnav->find('mod_offlinequiz_createquiz', navigation_node::TYPE_SETTING)) {
    $node->make_active();
}

if (!$groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0,
        $offlinequiz->numgroups)) {
    print_error('There are no offlinequiz groups', "edit.php?q=$offlinequiz->id$amp;sesskey=".sesskey());
}

// Redmine 2131: Handle download all before any HTML output is produced.
if ($downloadall && $offlinequiz->docscreated) {
    $fs = get_file_storage();

    $date = usergetdate(time());
    $timestamp = sprintf('%04d%02d%02d_%02d%02d%02d',
            $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds']);


    $shortname = $DB->get_field('course', 'shortname', array('id' => $offlinequiz->course));
    $zipfilename = clean_filename($shortname . '_' . $offlinequiz->name . '_' . $timestamp . '.zip');
    $zipfilename = str_replace(' ', '_', $zipfilename);
    $tempzip = tempnam($CFG->tempdir . '/', 'offlinequizzip');
    $filelist = array();

    $questionpath = clean_filename(get_string('questionforms', 'offlinequiz'));
    $answerpath = clean_filename(get_string('answerforms', 'offlinequiz'));
    $correctionpath = clean_filename(get_string('correctionforms', 'offlinequiz'));

    // Simply packing all files in the 'pdfs' filearea does not work.
    // We have to read the file names from the offlinequiz_groups table.
    foreach ($groups as $group) {
        if ($questionfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $group->questionfilename)) {
            $filelist[$questionpath . '/' . $questionfile->get_filename()] = $questionfile;
        }

        if ($answerfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $group->answerfilename)) {
            $filelist[$answerpath . '/' . $answerfile->get_filename()] = $answerfile;
        }

        if ($correctionfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $group->correctionfilename)) {
            $filelist[$correctionpath . '/' . $correctionfile->get_filename()] = $correctionfile;
        }
    }

    $zipper = new zip_packer();

    if ($zipper->archive_to_pathname($filelist, $tempzip)) {
        send_temp_file($tempzip, $zipfilename);
    }
}

// Print the page header.
echo $OUTPUT->header();

// Print the offlinequiz name heading and tabs for teacher.
$currenttab = 'createofflinequiz';

require('tabs.php');

$hasscannedpages = offlinequiz_has_scanned_pages($offlinequiz->id);

if ($offlinequiz->grade == 0) {
    echo '<div class="linkbox"><strong>';
    echo $OUTPUT->notification(get_string('gradeiszero', 'offlinequiz'), 'notifyproblem');
    echo '</strong></div>';
}

// Preview.
if ($mode == 'preview') {
    // Print shuffle again buttons.
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
            echo $OUTPUT->single_button($buttonurl,  get_string('shufflequestionsanswers', 'offlinequiz').' / ' .
                    get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else if ($offlinequiz->shufflequestions) {
            echo $OUTPUT->single_button($buttonurl,  get_string('shufflequestions', 'offlinequiz').' / ' .
                    get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else if ($offlinequiz->shuffleanswers) {
            echo $OUTPUT->single_button($buttonurl,  get_string('shuffleanswers', 'offlinequiz').' / ' .
                    get_string('reloadquestionlist', 'offlinequiz'), 'post');
        } else {
            echo $OUTPUT->single_button($buttonurl,  get_string('reloadquestionlist', 'offlinequiz'));
        }

        echo '</div>';

        echo $OUTPUT->box_end();
    }

    // Shuffle again if no scanned pages.
    if ($forcenew) {
        if ($offlinequiz->docscreated || $hasscannedpages) {
            echo $OUTPUT->notification(get_string('formsexist', 'offlinequiz'), 'notifyproblem');
        } else {
            $offlinequiz = offlinequiz_delete_template_usages($offlinequiz);
            $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number',
                      '*', 0, $offlinequiz->numgroups);
        }
    }

    $done = 0;
    // Process group data.
    foreach ($groups as $group) {
        $groupletter = $letterstr[$group->number - 1];

        // Print the group heading.
        echo $OUTPUT->heading(get_string('previewforgroup', 'offlinequiz', $groupletter));

        echo $OUTPUT->box_start('generalbox groupcontainer');

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

        $questions = $DB->get_records_sql($sql, $params);

        // Load the questions.
        if (!$questions = $DB->get_records_sql($sql, $params)) {
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/edit.php',
                    array('cmid' => $cm->id, 'groupnumber' => $group->number, 'noquestions' => 1));
            echo html_writer::link($url,  get_string('noquestionsfound', 'offlinequiz', $groupletter),
                    array('class' => 'linkbox'));
            echo $OUTPUT->box_end();
            continue;
        }
        // Load the question type specific information.
        if (!get_question_options($questions)) {
            print_error('Could not load question options');
        }

        // Get or create a question usage for this offline group.
        if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
            echo $OUTPUT->notification(get_string('missingquestions', 'offlinequiz'), 'notifyproblem');
            echo $OUTPUT->box_end();
            echo $OUTPUT->footer();
            continue;
        }
        if (!$slots = $templateusage->get_slots()) {
            echo $OUTPUT->box_start('notify');
            echo $OUTPUT->notification(get_string('nomcquestions', 'offlinequiz', $groupletter));
            echo $OUTPUT->box_end();
        }

        // We need a mapping from question IDs to slots, assuming that each question occurs only once..
        $questionslots = array();
        foreach ($slots as $qid => $slot) {
            $questionslots[$templateusage->get_question($slot)->id] = $slot;
        }

        $questionnumber = 1;
        $currentpage = 1;
        if ($offlinequiz->shufflequestions) {
            foreach ($slots as $slot) {
                $slotquestion = $templateusage->get_question($slot);
                $question = $questions[$slotquestion->id];
                $attempt = $templateusage->get_question_attempt($slot);
                $order = $slotquestion->get_order($attempt);  // Order.
                offlinequiz_print_question_preview($question, $order, $questionnumber, $context, $PAGE);
                // Note: we don't have description questions in quba slots.
                $questionnumber++;
            }
        } else {
            foreach ($questions as $question) {
            	print('questionpage '. $question->page . '\n');
                if ($question->page > $currentpage) {
                    echo '<center>//---------------------- ' . get_string('newpage', 'offlinequiz') .
                            ' ----------------//</center>';
                    $currentpage++;
                }
                $order = array();
                if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                    $slot = $questionslots[$question->id];
                    $slotquestion = $templateusage->get_question($slot);
                    $attempt = $templateusage->get_question_attempt($slot);
                    $order = $slotquestion->get_order($attempt);
                }
                // Use our own function to print the preview.
                offlinequiz_print_question_preview($question, $order, $questionnumber, $context, $PAGE);
                if ($question->qtype != 'description') {
                    $questionnumber++;
                }
            }
        }
        echo $OUTPUT->box_end();
    }// End foreach.

    // O==============================================================.
    // O TAB for creating, downloading and deleting PDF forms.
    // O==============================================================.
} else if ($mode == 'createpdfs') {

    // Print the heading.
    echo $OUTPUT->heading(get_string('downloadpdfs', 'offlinequiz'));

    $emptygroups = offlinequiz_get_empty_groups($offlinequiz);
    if (!empty($emptygroups)) {
        echo $OUTPUT->box_start('linkbox');
        foreach ($emptygroups as $groupnumber) {
            $groupletter = $letterstr[$groupnumber - 1];
            echo $OUTPUT->notification(get_string('noquestionsfound', 'offlinequiz', $groupletter));
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
            <div class="singlebutton linkbox btn-secondary">
               <form action="<?php echo "$CFG->wwwroot/mod/offlinequiz/createquiz.php?q=" . $offlinequiz->id .
                      "&mode=createpdfs" ?>" method="POST">
                    <div>
                        <input type="hidden" name="forcepdfnew" value="1" />
                        <button type="submit"
                        		onClick='return confirm("<?php echo get_string('reallydeletepdfs', 'offlinequiz') ?>")'
                        		class="btn btn-secondary singlebutton"
                        		 >
                        	<?php echo get_string('deletepdfs', 'offlinequiz') ?>
                        </button>
                   </div>
              </form>
            </div>
            <?php
        }
        echo $OUTPUT->box_end();
    }

    $fs = get_file_storage();

    // Delete the PDF forms if forcepdfnew and if there are no scanned pages yet.
    if ($forcepdfnew) {
        if ($hasscannedpages) {
            print_error('Some answer forms have already been analysed',
                "createquiz.php?q=$offlinequiz->id&amp;mode=createpdfs&amp;sesskey=" . sesskey());
        } else {
            // Redmine 2750: Always delete templates as well.
            offlinequiz_delete_template_usages($offlinequiz);
            $offlinequiz = offlinequiz_delete_pdf_forms($offlinequiz);

            $doctype = 'PDF';
            if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
                $doctype = 'DOCX';
            } else if ($offlinequiz->fileformat == OFFLINEQUIZ_LATEX_FORMAT) {
                $doctype = 'LATEX';
            }
            $params = array(
                'context' => $context,
                'other' => array(
                        'offlinequizid' => $offlinequiz->id,
                        'reportname' => $mode,
                        'doctype' => $doctype
                )
            );
            $event = \mod_offlinequiz\event\docs_deleted::create($params);
            $event->trigger();
        }
    }


    // Options for the popup_action.
    $options = array();
    $options['height'] = 1200; // Optional.
    $options['width'] = 1170; // Optional.

    // O============================================================.
    // O show/create the question forms for the offline groups.
    // O============================================================.
    if (!$forcepdfnew) {
        // Redmine 2131: Add download all link.
        $downloadallurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/createquiz.php',
        array('q' => $offlinequiz->id,
        'mode' => 'createpdfs',
        'downloadall' => 1));
        echo html_writer::start_div('downloadalllink');
        echo html_writer::link($downloadallurl->out(false), get_string('downloadallzip', 'offlinequiz'));
        echo html_writer::end_div();

        echo $OUTPUT->box_start('generalbox linkbox docsbox');

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number - 1];

            if (!$offlinequiz->docscreated) {
                if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                    print_error("Missing data for group ".$groupletter,
                        "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=" . sesskey());
                }
                $DB->set_field('offlinequiz', 'id_digits', get_config('offlinequiz', 'ID_digits'), array('id' => $offlinequiz->id));

                if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
                    require_once('docxlib.php');
                    $questionfile = offlinequiz_create_docx_question($templateusage, $offlinequiz, $group, $course->id, $context);
                } else if ($offlinequiz->fileformat == OFFLINEQUIZ_LATEX_FORMAT) {
                    require_once('latexlib.php');
                    $questionfile = offlinequiz_create_latex_question($templateusage, $offlinequiz, $group, $course->id, $context);
                } else {
                    $questionfile = offlinequiz_create_pdf_question($templateusage, $offlinequiz, $group, $course->id, $context);
                }
                if ($questionfile) {
                    $group->questionfilename = $questionfile->get_filename();
                    $DB->update_record('offlinequiz_groups', $group);
                }
            } else {
                $filename = $group->questionfilename;
                $questionfile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $filename);
            }

            if ($questionfile) {
                $filestring = get_string('formforgroup', 'offlinequiz', $groupletter);
                if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
                    $filestring = get_string('formforgroupdocx', 'offlinequiz', $groupletter);
                } else if ($offlinequiz->fileformat == OFFLINEQUIZ_LATEX_FORMAT) {
                    $filestring = get_string('formforgrouplatex', 'offlinequiz', $groupletter);
                }
                $url = "$CFG->wwwroot/pluginfile.php/" . $questionfile->get_contextid() . '/' . $questionfile->get_component() .
                            '/' . $questionfile->get_filearea() . '/' . $questionfile->get_itemid() . '/' .
                            $questionfile->get_filename() . '?forcedownload=1';
                echo $OUTPUT->action_link($url, $filestring);
                echo '<br />&nbsp;<br />';
                @flush();@ob_flush();
            } else {
                echo $OUTPUT->notification(get_string('createpdferror', 'offlinequiz', $groupletter));
            }
        }
        echo $OUTPUT->box_end();

        // O============================================================.
        // O Show/create the answer forms for all offline groups.
        // O============================================================.
        echo $OUTPUT->box_start('generalbox linkbox docsbox');

        echo $OUTPUT->notification(get_string('marginwarning', 'offlinequiz'));
        echo '<br/>';

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number - 1];

            if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                print_error("Missing data for group " . $groupletter,
                    "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=".sesskey());
            }

            if (!$offlinequiz->docscreated) {
                $answerpdffile = offlinequiz_create_pdf_answer(offlinequiz_get_maxanswers($offlinequiz, array($group)),
                    $templateusage, $offlinequiz, $group, $course->id, $context);
                if ($answerpdffile) {
                    $group->answerfilename = $answerpdffile->get_filename();
                    $DB->update_record('offlinequiz_groups', $group);
                }
            } else {
                $filename = $group->answerfilename;
                $answerpdffile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $filename);
            }

            if ($answerpdffile) {
                $url = "$CFG->wwwroot/pluginfile.php/" . $answerpdffile->get_contextid() . '/' .
                        $answerpdffile->get_component() . '/' . $answerpdffile->get_filearea() . '/' .
                        $answerpdffile->get_itemid() . '/' . $answerpdffile->get_filename() . '?forcedownload=1';
                echo $OUTPUT->action_link($url, get_string('answerformforgroup', 'offlinequiz', $groupletter));
                echo '<br />&nbsp;<br />';
                @flush();@ob_flush();
            } else {
                echo $OUTPUT->notification(get_string('createpdferror', 'offlinequiz', $groupletter));
            }
        }


        echo $OUTPUT->box_end();

        // O============================================================.
        // O Show/create the correction forms for all offline groups.
        // O============================================================.
        echo $OUTPUT->box_start('generalbox linkbox docsbox');

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number - 1];

            if (!$templateusage = offlinequiz_get_group_template_usage($offlinequiz, $group, $context)) {
                print_error("Missing data for group " . $groupletter,
                    "createquiz.php?q=$offlinequiz->id&amp;mode=preview&amp;sesskey=" . sesskey());
            }

            if (!$offlinequiz->docscreated) {
                $correctionpdffile = offlinequiz_create_pdf_question($templateusage, $offlinequiz, $group,
                                     $course->id, $context, true);
                if ($correctionpdffile) {
                    $group->correctionfilename = $correctionpdffile->get_filename();
                    $DB->update_record('offlinequiz_groups', $group);
                }
            } else {
                $filename = $group->correctionfilename;
                $correctionpdffile = $fs->get_file($context->id, 'mod_offlinequiz', 'pdfs', 0, '/', $filename);
            }

            if ($correctionpdffile) {
                $url = "$CFG->wwwroot/pluginfile.php/" . $correctionpdffile->get_contextid() . '/' .
                        $correctionpdffile->get_component() . '/' . $correctionpdffile->get_filearea() . '/' .
                        $correctionpdffile->get_itemid() . '/' . $correctionpdffile->get_filename() . '?forcedownload=1';
                echo $OUTPUT->action_link($url, get_string('formforcorrection', 'offlinequiz', $groupletter));

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

        $doctype = 'PDF';
        if ($offlinequiz->fileformat == OFFLINEQUIZ_DOCX_FORMAT) {
            $doctype = 'DOCX';
        } else if ($offlinequiz->fileformat == OFFLINEQUIZ_LATEX_FORMAT) {
            $doctype = 'LATEX';
        }
        $params = array(
            'context' => $context,
            'other' => array(
                    'offlinequizid' => $offlinequiz->id,
                    'reportname' => $mode,
                    'doctype' => $doctype

            )
        );
        $event = \mod_offlinequiz\event\docs_created::create($params);
        $event->trigger();
    }
}

// Finish the page.
echo $OUTPUT->footer();
