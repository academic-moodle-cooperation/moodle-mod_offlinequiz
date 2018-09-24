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
 * Library of interface functions and constants for module offlinequiz
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the offlinequiz specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// If, for some reason, you need to use global variables instead of constants, do not forget to make them
// global as this file can be included inside a function scope. However, using the global variables
// at the module level is not recommended.

// CONSTANTS.

// The different review options are stored in the bits of $offlinequiz->review.
// These constants help to extract the options.
// Originally this method was copied from the Moodle 1.9 quiz module. We use:
// 111111100000000000.
define('OFFLINEQUIZ_REVIEW_ATTEMPT',          0x1000);  // Show responses.
define('OFFLINEQUIZ_REVIEW_MARKS',            0x2000);  // Show scores.
define('OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK', 0x4000);  // Show feedback.
define('OFFLINEQUIZ_REVIEW_RIGHTANSWER',      0x8000);  // Show correct answers.
define('OFFLINEQUIZ_REVIEW_GENERALFEEDBACK',  0x10000); // Show general feedback.
define('OFFLINEQUIZ_REVIEW_SHEET',            0x20000); // Show scanned sheet.
define('OFFLINEQUIZ_REVIEW_CORRECTNESS',      0x40000); // Show scanned sheet.
define('OFFLINEQUIZ_REVIEW_GRADEDSHEET',      0x800); // Show scanned sheet.

// Define constants for cron job status.
define('OQ_STATUS_PENDING', 1);
define('OQ_STATUS_OPERATING', 2);
define('OQ_STATUS_PROCESSED', 3);
define('OQ_STATUS_NEEDS_CORRECTION', 4);
define('OQ_STATUS_DOUBLE', 5);


// If start and end date for the offline quiz are more than this many seconds apart
// they will be represented by two separate events in the calendar.

define('OFFLINEQUIZ_MAX_EVENT_LENGTH', 5 * 24 * 60 * 60); // 5 days.

// FUNCTIONS.

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $offlinequiz An object from the form in mod_form.php
 * @return int The id of the newly inserted offlinequiz record
 */
function offlinequiz_add_instance($offlinequiz) {
    global $CFG, $DB;

    // Process the options from the form.
    $offlinequiz->timecreated = time();
    $offlinequiz->questions = '';
    $offlinequiz->grade = 100;

    $result = offlinequiz_process_options($offlinequiz);

    if ($result && is_string($result)) {
        return $result;
    }
    if (!property_exists($offlinequiz, 'intro') || $offlinequiz->intro == null) {
        $offlinequiz->intro = '';
    }

    if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
        print_error('invalidcourseid', 'error');
    }

    $context = context_module::instance($offlinequiz->coursemodule);

    // Process the HTML editor data in pdfintro.
    if (is_array($offlinequiz->pdfintro) && array_key_exists('text', $offlinequiz->pdfintro)) {
        if ($draftitemid = $offlinequiz->pdfintro['itemid']) {
              $editoroptions = offlinequiz_get_editor_options();

            $offlinequiz->pdfintro = file_save_draft_area_files($draftitemid, $context->id,
                                                    'mod_offlinequiz', 'pdfintro',
                                                    0, $editoroptions,
                                                    $offlinequiz->pdfintro['text']);
        }
    }

    // Try to store it in the database.
    try {
        if (!$offlinequiz->id = $DB->insert_record('offlinequiz', $offlinequiz)) {
            print_error('Could not create Offlinequiz object!');
            return false;
        }
    } catch (Exception $e) {
        print_error("ERROR: " . $e->debuginfo);
    }

    // Do the processing required after an add or an update.
    offlinequiz_after_add_or_update($offlinequiz);

    return $offlinequiz->id;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $offlinequiz An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function offlinequiz_update_instance($offlinequiz) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

    $offlinequiz->timemodified = time();
    $offlinequiz->id = $offlinequiz->instance;

    // Remember the old values of the shuffle settings.
    $shufflequestions = $DB->get_field('offlinequiz', 'shufflequestions', array('id' => $offlinequiz->id));
    $shuffleanswers = $DB->get_field('offlinequiz', 'shuffleanswers', array('id' => $offlinequiz->id));

    // Process the options from the form.
    $result = offlinequiz_process_options($offlinequiz);
    if ($result && is_string($result)) {
        return $result;
    }

    $context = context_module::instance($offlinequiz->coursemodule);

    // Process the HTML editor data in pdfintro.
    if (property_exists($offlinequiz, 'pdfintro') && is_array($offlinequiz->pdfintro)
            && array_key_exists('text', $offlinequiz->pdfintro)) {
        if ($draftitemid = $offlinequiz->pdfintro['itemid']) {
              $editoroptions = offlinequiz_get_editor_options();

            $offlinequiz->pdfintro = file_save_draft_area_files($draftitemid, $context->id,
                                                    'mod_offlinequiz', 'pdfintro',
                                                    0, $editoroptions,
                                                    $offlinequiz->pdfintro['text']);
        }
    }

    // Update the database.
    if (! $DB->update_record('offlinequiz', $offlinequiz)) {
        return false;  // Some error occurred.
    }

    // Do the processing required after an add or an update.
    offlinequiz_after_add_or_update($offlinequiz);

    // We also need the docscreated and the numgroups field.
    $offlinequiz = $DB->get_record('offlinequiz', array('id' => $offlinequiz->id));

    // Delete the question usage templates if no documents have been created and no answer forms have been scanned.
    if (!$offlinequiz->docscreated && !offlinequiz_has_scanned_pages($offlinequiz->id)) {
        offlinequiz_delete_template_usages($offlinequiz);
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function offlinequiz_delete_instance($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->dirroot . '/calendar/lib.php');

    if (! $offlinequiz = $DB->get_record('offlinequiz', array('id' => $id))) {
        return false;
    }

    if (! $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course)) {
        return false;
    }
    $context = context_module::instance($cm->id);

    // Delete any dependent records here.
    if ($results = $DB->get_records("offlinequiz_results", array('offlinequizid' => $offlinequiz->id))) {
        foreach ($results as $result) {
            offlinequiz_delete_result($result->id, $context);
        }
    }
    if ($scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($scannedpages as $page) {
            offlinequiz_delete_scanned_page($page, $context);
        }
    }
    if ($scannedppages = $DB->get_records('offlinequiz_scanned_p_pages', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($scannedppages as $page) {
            offlinequiz_delete_scanned_p_page($page, $context);
        }
    }

    if ($events = $DB->get_records('event', array('modulename' => 'offlinequiz', 'instance' => $offlinequiz->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event);
            $event->delete();
        }
    }

    if ($plists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($plists as $plist) {
            $DB->delete_records('offlinequiz_participants', array('listid' => $plist->id));
            $DB->delete_records('offlinequiz_p_lists', array('id' => $plist->id));
        }
    }

    // Remove the grade item.
    offlinequiz_grade_item_delete($offlinequiz);

    // Delete template question usages of offlinequiz groups.
    offlinequiz_delete_template_usages($offlinequiz);

    // All the tables with no dependencies...
    $tablestopurge = array(
            'offlinequiz_groups' => 'offlinequizid',
            'offlinequiz' => 'id'
    );

    foreach ($tablestopurge as $table => $keyfield) {
        if (! $DB->delete_records($table, array($keyfield => $offlinequiz->id))) {
            $result = false;
        }
    }

    return true;
}

/**
 * This gets an array with default options for the editor
 *
 * @return array the options
 */
function offlinequiz_get_editor_options($context = null) {
    $options = array('maxfiles' => EDITOR_UNLIMITED_FILES,
                 'noclean' => true);
    if ($context) {
        $options['context'] = $context;
    }
    return $options;
}

/**
 * Delete grade item for given offlinequiz
 *
 * @param object $offlinequiz object
 * @return object offlinequiz
 */
function offlinequiz_grade_item_delete($offlinequiz) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/offlinequiz', $offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id, 0,
            null, array('deleted' => 1));
}

/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt is an offlinequiz attempt.
 *
 * @package  mod_offlinequiz
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function offlinequiz_question_pluginfile($course, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;

    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);

    if (!has_capability('mod/offlinequiz:viewreports', $context)) {
        // If the user is not a teacher then check whether a complete result exists.
        if (!$result = $DB->get_record('offlinequiz_results', array('usageid' => $qubaid, 'status' => 'complete'))) {
            send_file_not_found();
        }
        // If the user's ID is not the ID of the result we don't serve the file.
        if ($result->userid != $USER->id) {
            send_file_not_found();
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Serve questiontext files in the question text when they are displayed in a report.
 *
 * @param context $previewcontext the quiz context
 * @param int $questionid the question id.
 * @param context $filecontext the file (question) context
 * @param string $filecomponent the component the file belongs to.
 * @param string $filearea the file area.
 * @param array $args remaining file args.
 * @param bool $forcedownload.
 * @param array $options additional options affecting the file serving.
 */
function offlinequiz_question_preview_pluginfile($previewcontext, $questionid, $filecontext, $filecomponent, $filearea,
         $args, $forcedownload, $options = array()) {
     global $CFG;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->dirroot . '/lib/questionlib.php');

    list($context, $course, $cm) = get_context_info_array($previewcontext->id);
    require_login($course, false, $cm);

    // We assume that only trusted people can see this report. There is no real way to
    // validate questionid, because of the complexity of random questions.
    require_capability('mod/offlinequiz:viewreports', $context);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/{$filecontext->id}/{$filecomponent}/{$filearea}/{$relativepath}";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Serve image files in the answer text when they are displayed in the preview
 *
 * @param context $context the context
 * @param int $answerid the answer id
 * @param array $args remaining file args
 * @param bool $forcedownload
 */
function offlinequiz_answertext_preview_pluginfile($context, $answerid, $args, $forcedownload, array $options=array()) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->dirroot . '/lib/questionlib.php');

    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);

    // Assume only trusted people can see this report. There is no real way to
    // validate questionid, becuase of the complexity of random quetsions.
    require_capability('mod/offlinequiz:viewreports', $context);

    offlinequiz_send_answertext_file($context, $answerid, $args, $forcedownload, $options);
}

/**
 * Send a file in the text of an answer.
 *
 * @param int $questionid the question id
 * @param array $args the remaining file arguments (file path).
 * @param bool $forcedownload whether the user must be forced to download the file.
 */
function offlinequiz_send_answertext_file($context, $answerid, $args, $forcedownload) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

    $fs = get_file_storage();
    $fullpath = "/$context->id/question/answer/$answerid/" . implode('/', $args);
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload);
}

/**
 * Serves the offlinequiz files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function offlinequiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
        return false;
    }

    // The file file areas served by this method.
    $fileareas = array('pdfs', 'participants', 'imagefiles');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    $fullpath = '/' . $context->id . '/mod_offlinequiz/' . $filearea . '/' . $relativepath;

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Teachers in this context are allowed to see all the files in the context.
    if (has_capability('mod/offlinequiz:viewreports', $context)) {
        if ($filearea == 'pdfs' || $filearea == 'participants') {
            $filename = clean_filename($course->shortname . '_' . $offlinequiz->name . '_' . $file->get_filename());
            $filename = str_replace(" ", "_", $filename);
            send_stored_file($file, 86400, 0, $forcedownload, array('filename' => $filename));
        } else {
            send_stored_file($file, 86400, 0, $forcedownload);
        }
    } else {

        // Get the corresponding scanned pages. There might be several in case an image file is used twice.
        if (!$scannedpages = $DB->get_records('offlinequiz_scanned_pages',
                array('offlinequizid' => $offlinequiz->id, 'warningfilename' => $file->get_filename()))) {
            if (!$scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('offlinequizid' => $offlinequiz->id,
                    'filename' => $file->get_filename()))) {
                    print_error('scanned page not found');
                    return false;
            }
        }

        // Actually, there should be only one scannedpage with that filename...
        foreach ($scannedpages as $scannedpage) {
            $sql = "SELECT *
                      FROM {offlinequiz_results}
                     WHERE id = :resultid
                       AND status = 'complete'";
            if (!$result = $DB->get_record_sql($sql, array('resultid' => $scannedpage->resultid))) {
                return false;
            }

            // Check whether the student is allowed to see scanned sheets.
            $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
            if ($options->sheetfeedback == question_display_options::HIDDEN and
                    $options->gradedsheetfeedback == question_display_options::HIDDEN) {
                return false;
            }

            // If we found a page of a complete result that belongs to the user, we can send the file.
            if ($result->userid == $USER->id) {
                send_stored_file($file, 86400, 0, $forcedownload);
                return true;
            }
        }
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function offlinequiz_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
            'mod-offlinequiz-*' => get_string('page-mod-offlinequiz-x', 'offlinequiz'),
            'mod-offlinequiz-edit' => get_string('page-mod-offlinequiz-edit', 'offlinequiz'));
    return $modulepagetype;
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular offlinequiz,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param object $offlinequiz the offlinequiz object. Only $offlinequiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123", "Attemtps 123 (45 from your groups)" or
 *          "Attemtps 123 (45 from this group)".
 */
function offlinequiz_num_attempt_summary($offlinequiz, $cm, $returnzero = false, $currentgroup = 0) {
    global $DB, $USER;

    $sql = "SELECT COUNT(*)
              FROM {offlinequiz_results}
             WHERE offlinequizid = :offlinequizid
               AND status = 'complete'";

    $numattempts = $DB->count_records_sql($sql, array('offlinequizid' => $offlinequiz->id));
    if ($numattempts || $returnzero) {
        return get_string('attemptsnum', 'offlinequiz', $numattempts);
    }
    return '';
}


/**
 * Returns the same as {@link offlinequiz_num_attempt_summary()} but wrapped in a link
 * to the offlinequiz reports.
 *
 * @param object $offlinequiz the offlinequiz object. Only $offlinequiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param object $context the offlinequiz context.
 * @param bool $returnzero if false (default), when no attempts have been made
 *      '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string HTML fragment for the link.
 */
function offlinequiz_attempt_summary_link_to_reports($offlinequiz, $cm, $context, $returnzero = false,
        $currentgroup = 0) {
    global $CFG;
    $summary = offlinequiz_num_attempt_summary($offlinequiz, $cm, $returnzero, $currentgroup);
    if (!$summary) {
        return '';
    }

    $url = new moodle_url('/mod/offlinequiz/report.php', array(
            'id' => $cm->id, 'mode' => 'overview'));
    return html_writer::link($url, $summary);
}


/**
 * Check for features supported by offlinequizzes.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool True if offlinequiz supports feature
 */
function offlinequiz_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
          return true;

        default:
            return null;
    }
}

/**
 * Is this a graded offlinequiz? If this method returns true, you can assume that
 * $offlinequiz->grade and $offlinequiz->sumgrades are non-zero (for example, if you want to
 * divide by them).
 *
 * @param object $offlinequiz a row from the offlinequiz table.
 * @return bool whether this is a graded offlinequiz.
 */
function offlinequiz_has_grades($offlinequiz) {
    return $offlinequiz->grade >= 0.000005 && $offlinequiz->sumgrades >= 0.000005;
}

/**
 * Pre-process the offlinequiz options form data, making any necessary adjustments.
 * Called by add/update instance in this file, and the save code in admin/module.php.
 *
 * @param object $offlinequiz The variables set on the form.
 */
function offlinequiz_process_options(&$offlinequiz) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');

    $offlinequiz->timemodified = time();

    // Offlinequiz name. (Make up a default if one was not given).
    if (empty($offlinequiz->name)) {
        if (empty($offlinequiz->intro)) {
            $offlinequiz->name = get_string('modulename', 'offlinequiz');
        } else {
            $offlinequiz->name = shorten_text(strip_tags($offlinequiz->intro));
        }
    }
    $offlinequiz->name = trim($offlinequiz->name);

    // Settings that get combined to go into the optionflags column.
    $offlinequiz->optionflags = 0;
    if (!empty($offlinequiz->adaptive)) {
        $offlinequiz->optionflags |= QUESTION_ADAPTIVE;
    }

    // Settings that get combined to go into the review column.
    $review = 0;
    if (isset($offlinequiz->attemptclosed)) {
        $review += OFFLINEQUIZ_REVIEW_ATTEMPT;
        unset($offlinequiz->attemptclosed);
    }

    if (isset($offlinequiz->marksclosed)) {
        $review += OFFLINEQUIZ_REVIEW_MARKS;
        unset($offlinequiz->marksclosed);
    }

    if (isset($offlinequiz->feedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_FEEDBACK;
        unset($offlinequiz->feedbackclosed);
    }

    if (isset($offlinequiz->correctnessclosed)) {
        $review += OFFLINEQUIZ_REVIEW_CORRECTNESS;
        unset($offlinequiz->correctnessclosed);
    }

    if (isset($offlinequiz->rightanswerclosed)) {
        $review += OFFLINEQUIZ_REVIEW_RIGHTANSWER;
        unset($offlinequiz->rightanswerclosed);
    }

    if (isset($offlinequiz->generalfeedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_GENERALFEEDBACK;
        unset($offlinequiz->generalfeedbackclosed);
    }

    if (isset($offlinequiz->specificfeedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK;
        unset($offlinequiz->specificfeedbackclosed);
    }

    if (isset($offlinequiz->sheetclosed)) {
        $review += OFFLINEQUIZ_REVIEW_SHEET;
        unset($offlinequiz->sheetclosed);
    }

    if (isset($offlinequiz->gradedsheetclosed)) {
        $review += OFFLINEQUIZ_REVIEW_GRADEDSHEET;
        unset($offlinequiz->gradedsheetclosed);
    }

    $offlinequiz->review = $review;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $offlinequiz
 * @return stdClass|NULL
 */
function offlinequiz_user_outline($course, $user, $mod, $offlinequiz) {
    global $DB;

    $return = new stdClass;
    $return->time = 0;
    $return->info = '';

    if ($grade = $DB->get_record('offlinequiz_results', array('userid' => $user->id, 'offlinequizid' => $offlinequiz->id))) {
        if ((float) $grade->sumgrades) {
            $return->info = get_string('grade') . ':&nbsp;' . round($grade->sumgrades, $offlinequiz->decimalpoints);
        }
        $return->time = $grade->timemodified;
        return $return;
    }
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $offlinequiz
 * @return boolean
 */
function offlinequiz_user_complete($course, $user, $mod, $offlinequiz) {
    global $DB;

    if ($results = $DB->get_records('offlinequiz_results', array('userid' => $user->id, 'offlinequiz' => $offlinequiz->id))) {
        if ($offlinequiz->grade && $offlinequiz->sumgrades &&
                $grade = $DB->get_record('offlinequiz_results', array('userid' => $user->id, 'offlinequiz' => $offlinequiz->id))) {
            echo get_string('grade') . ': ' . round($grade->grade, $offlinequiz->decimalpoints) .
                '/' . $offlinequiz->grade . '<br />';
        }
        foreach ($results as $result) {
            echo get_string('result', 'offlinequiz') . ': ';
            if ($result->timefinish == 0) {
                print_string('unfinished');
            } else {
                echo round($result->sumgrades, $offlinequiz->decimalpoints) . '/' . $offlinequiz->sumgrades;
            }
            echo ' - ' . userdate($result->timemodified) . '<br />';
        }
    } else {
        print_string('noresults', 'offlinequiz');
    }

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in offlinequiz activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param unknown_type $course
 * @param unknown_type $viewfullnames
 * @param unknown_type $timestart
 * @return boolean
 */
function offlinequiz_print_recent_mod_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note: The evaluation of answer forms is done by a separate cron job using the script mod/offlinequiz/cron.php.
 *
 **/
function offlinequiz_cron() {
    global $DB;

    cron_execute_plugin_type('offlinequiz', 'offlinequiz reports');

    // Remove all saved hotspot data that is older than 7 days.
    $timenow = time();

    // We have to make sure we do this atomic for each scanned page.
    $sql = "SELECT DISTINCT(scannedpageid)
              FROM {offlinequiz_hotspots}
             WHERE time < :expiretime";
    $params = array('expiretime' => $timenow - 604800);

    // First we get the different IDs.
    $ids = $DB->get_fieldset_sql($sql, $params);

    if (!empty($ids)) {
        list($isql, $iparams) = $DB->get_in_or_equal($ids);

        // Now we delete the records.
        $DB->delete_records_select('offlinequiz_hotspots', 'scannedpageid ' . $isql, $iparams);
    }

    // Delete old temporary files not needed any longer.
    $keepdays = get_config('offlinequiz', 'keepfilesfordays');
    $keepseconds = $keepdays * 24 * 60 * 60;

    $sql = "SELECT id
              FROM {offlinequiz_queue}
             WHERE timecreated < :expiretime";
    $params = array('expiretime' => $timenow - $keepseconds);

    // First we get the IDs of cronjobs older than the configured number of days.
    $jobids = $DB->get_fieldset_sql($sql, $params);
    foreach ($jobids as $jobid) {
        $dirname = null;
        // Delete all temporary files and the database entries.
        if ($files = $DB->get_records('offlinequiz_queue_data', array('queueid' => $jobid))) {
            foreach ($files as $file) {
                if (empty($dirname)) {
                    $pathparts = pathinfo($file->filename);
                    $dirname = $pathparts['dirname'];
                }
                $DB->delete_records('offlinequiz_queue_data', array('id' => $file->id));
            }
            // Remove the temporary directory.
            echo "Removing dir " . $dirname . "\n";
            remove_dir($dirname);
        }
    }

    return true;
}

/**
 * Must return an array of users who are participants for a given instance
 * of offlinequiz. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $offlinequizid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function offlinequiz_get_participants($offlinequizid) {
    global $CFG, $DB;

    // Get users from offlinequiz results.
    $usattempts = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {offlinequiz_results} r
             WHERE r.offlinequizid = '$offlinequizid'
               AND (u.id = r.userid OR u.id = r.teacherid");

    // Return us_attempts array (it contains an array of unique users).
    return $usattempts;
}

/**
 * This function returns if a scale is being used by one offlinequiz
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $offlinequizid ID of an instance of this module
 * @return mixed
 */
function offlinequiz_scale_used($offlinequizid, $scaleid) {
    global $DB;

    $return = false;

    $rec = $DB->get_record('offlinequiz', array('id' => $offlinequizid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of offlinequiz.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any offlinequiz
 */
function offlinequiz_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('offlinequiz', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function is called at the end of offlinequiz_add_instance
 * and offlinequiz_update_instance, to do the common processing.
 *
 * @param object $offlinequiz the offlinequiz object.
 */
function offlinequiz_after_add_or_update($offlinequiz) {
    global $DB;

    // Create group entries if they don't exist.
    if (property_exists($offlinequiz, 'numgroups')) {
        for ($i = 1; $i <= $offlinequiz->numgroups; $i++) {
            if (!$group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'number' => $i))) {
                $group = new stdClass();
                $group->offlinequizid = $offlinequiz->id;
                $group->number = $i;
                $group->numberofpages = 1;
                $DB->insert_record('offlinequiz_groups', $group);
            }
        }
    }

    offlinequiz_update_events($offlinequiz);
    offlinequiz_grade_item_update($offlinequiz);
    return;
}

/**
 * This function updates the events associated to the offlinequiz.
 * If $override is non-zero, then it updates only the events
 * associated with the specified override.
 *
 * @uses OFFLINEQUIZ_MAX_EVENT_LENGTH
 * @param object $offlinequiz the offlinequiz object.
 * @param object optional $override limit to a specific override
 */
function offlinequiz_update_events($offlinequiz) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/calendar/lib.php');

    // Load the old events relating to this offlinequiz.
    $conds = array('modulename' => 'offlinequiz',
                   'instance' => $offlinequiz->id);

    if (!empty($override)) {
        // Only load events for this override.
        $conds['groupid'] = isset($override->groupid) ? $override->groupid : 0;
        $conds['userid'] = isset($override->userid) ? $override->userid : 0;
    }
    $oldevents = $DB->get_records('event', $conds);

    $groupid   = 0;
    $userid    = 0;
    $timeopen  = $offlinequiz->timeopen;
    $timeclose = $offlinequiz->timeclose;

    if ($offlinequiz->time) {
        $timeopen = $offlinequiz->time;
    }

    // Only add open/close events if they differ from the offlinequiz default.
    if (!empty($offlinequiz->coursemodule)) {
        $cmid = $offlinequiz->coursemodule;
    } else {
        $cmid = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $offlinequiz->course)->id;
    }

    if (!empty($timeopen)) {
        $event = new stdClass();
        $event->name = $offlinequiz->name . ' (' . get_string('reportstarts', 'offlinequiz') . ')';
        $event->description = format_module_intro('offlinequiz', $offlinequiz, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $offlinequiz->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'offlinequiz';
        $event->instance    = $offlinequiz->id;
        $event->timestart   = $timeopen;
        $event->timeduration = 0;
        $event->visible     = instance_is_visible('offlinequiz', $offlinequiz);

        calendar_event::create($event);
    }
    if (!empty($timeclose)) {
        $event = new stdClass();
        $event->name = $offlinequiz->name . ' (' . get_string('reportends', 'offlinequiz') . ')';
        $event->description = format_module_intro('offlinequiz', $offlinequiz, $cmid);
        // Events module won't show user events when the courseid is nonzero.
        $event->courseid    = ($userid) ? 0 : $offlinequiz->course;
        $event->groupid     = $groupid;
        $event->userid      = $userid;
        $event->modulename  = 'offlinequiz';
        $event->instance    = $offlinequiz->id;
        $event->timestart   = $timeclose;
        $event->timeduration = 0;
        $event->visible     = instance_is_visible('offlinequiz', $offlinequiz);

        calendar_event::create($event);
    }

    // Delete any leftover events.
    foreach ($oldevents as $badevent) {
        $badevent = calendar_event::load($badevent);
        $badevent->delete();
    }
}


/**
 * Prints offlinequiz summaries on MyMoodle Page
 * @param arry $courses
 * @param array $htmlarray
 */
function offlinequiz_print_overview($courses, &$htmlarray) {
    global $USER, $CFG;
    // These next 6 Lines are constant in all modules (just change module name).
    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$offlinequizzes = get_all_instances_in_courses('offlinequiz', $courses)) {
        return;
    }

    // Fetch some language strings outside the main loop.
    $strofflinequiz = get_string('modulename', 'offlinequiz');
    $strnoattempts = get_string('noresults', 'offlinequiz');

    // We want to list offlinequizzes that are currently available, and which have a close date.
    // This is the same as what the lesson does, and the dabate is in MDL-10568.
    $now = time();
    foreach ($offlinequizzes as $offlinequiz) {
        if ($offlinequiz->timeclose >= $now && $offlinequiz->timeopen < $now) {
            // Give a link to the offlinequiz, and the deadline.
            $str = '<div class="offlinequiz overview">' .
                    '<div class="name">' . $strofflinequiz . ': <a ' .
                    ($offlinequiz->visible ? '' : ' class="dimmed"') .
                    ' href="' . $CFG->wwwroot . '/mod/offlinequiz/view.php?id=' .
                    $offlinequiz->coursemodule . '">' .
                    $offlinequiz->name . '</a></div>';
            $str .= '<div class="info">' . get_string('offlinequizcloseson', 'offlinequiz',
                    userdate($offlinequiz->timeclose)) . '</div>';

            // Now provide more information depending on the uers's role.
            $context = context_module::instance($offlinequiz->coursemodule);
            if (has_capability('mod/offlinequiz:viewreports', $context)) {
                // For teacher-like people, show a summary of the number of student attempts.
                // The $offlinequiz objects returned by get_all_instances_in_course have the necessary $cm
                // fields set to make the following call work.
                $str .= '<div class="info">' .
                        offlinequiz_num_attempt_summary($offlinequiz, $offlinequiz, true) . '</div>';
            } else if (has_capability('mod/offlinequiz:attempt', $context)) { // Student
                // For student-like people, tell them how many attempts they have made.
                if (isset($USER->id) && ($results = offlinequiz_get_user_results($offlinequiz->id, $USER->id))) {
                    $str .= '<div class="info">' .
                            get_string('hasresult', 'offlinequiz') . '</div>';
                } else {
                    $str .= '<div class="info">' . $strnoattempts . '</div>';
                }
            } else {
                // For ayone else, there is no point listing this offlinequiz, so stop processing.
                continue;
            }

            // Add the output for this offlinequiz to the rest.
            $str .= '</div>';
            if (empty($htmlarray[$offlinequiz->course]['offlinequiz'])) {
                $htmlarray[$offlinequiz->course]['offlinequiz'] = $str;
            } else {
                $htmlarray[$offlinequiz->course]['offlinequiz'] .= $str;
            }
        }
    }
}


/**
 * Round a grade to to the correct number of decimal places, and format it for display.
 *
 * @param object $offlinequiz The offlinequiz table row, only $offlinequiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function offlinequiz_format_grade($offlinequiz, $grade) {
    if (is_null($grade)) {
        return get_string('notyetgraded', 'offlinequiz');
    }
    return format_float($grade, $offlinequiz->decimalpoints);
}

/**
 * Round a grade to to the correct number of decimal places, and format it for display.
 *
 * @param object $offlinequiz The offlinequiz table row, only $offlinequiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function offlinequiz_format_question_grade($offlinequiz, $grade) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

    if (empty($offlinequiz->questiondecimalpoints)) {
        $offlinequiz->questiondecimalpoints = -1;
    }
    if ($offlinequiz->questiondecimalpoints == -1) {
        return format_float($grade, $offlinequiz->decimalpoints);
    } else {
        return format_float($grade, $offlinequiz->questiondecimalpoints);
    }
}


/**
 * Return grade for given user or all users. The grade is taken from all complete offlinequiz results
 *
 * @param mixed $offlinequiz The offline quiz
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function offlinequiz_get_user_grades($offlinequiz, $userid=0) {
    global $CFG, $DB;

    $maxgrade = $offlinequiz->grade;
    $groups = $DB->get_records('offlinequiz_groups',
                               array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups);

    $user = $userid ? " AND userid =  $userid " : "";

    $sql = "SELECT id, userid, sumgrades, offlinegroupid, timemodified as dategraded, timefinish AS datesubmitted
              FROM {offlinequiz_results}
             WHERE offlinequizid = :offlinequizid
               AND status = 'complete'
    $user";
    $params = array('offlinequizid' => $offlinequiz->id);

    $grades = array();

    if ($results = $DB->get_records_sql($sql, $params)) {
        foreach ($results as $result) {
            $key = $result->userid;
            $grades[$key] = array();
            $groupsumgrades = $groups[$result->offlinegroupid]->sumgrades;
            $grades[$key]['userid'] = $result->userid;
            $grades[$key]['rawgrade'] = round($result->sumgrades / $groupsumgrades * $maxgrade, $offlinequiz->decimalpoints);
            $grades[$key]['dategraded'] = $result->dategraded;
            $grades[$key]['datesubmitted'] = $result->datesubmitted;
        }
    }

    return $grades;
}

/**
 * Update grades in central gradebook
 *
 * @param object $offlinequiz the offline quiz settings.
 * @param int $userid specific user only, 0 means all users.
 */
function offlinequiz_update_grades($offlinequiz, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($offlinequiz->grade == 0) {
        offlinequiz_grade_item_update($offlinequiz);

    } else if ($grades = offlinequiz_get_user_grades($offlinequiz, $userid)) {
        offlinequiz_grade_item_update($offlinequiz, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        offlinequiz_grade_item_update($offlinequiz, $grade);

    } else {
        offlinequiz_grade_item_update($offlinequiz);
    }
}


/**
 * Create grade item for given offlinequiz
 *
 * @param object $offlinequiz object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function offlinequiz_grade_item_update($offlinequiz, $grades = null) {
    global $CFG, $OUTPUT, $DB;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->libdir . '/questionlib.php');

    if (array_key_exists('cmidnumber', $offlinequiz)) {
        // May not be always present.
        $params = array('itemname' => $offlinequiz->name, 'idnumber' => $offlinequiz->cmidnumber);
    } else {
        $params = array('itemname' => $offlinequiz->name);
    }

    $offlinequiz->grade = $DB->get_field('offlinequiz', 'grade', array('id' => $offlinequiz->id));

    if (property_exists($offlinequiz, 'grade') && $offlinequiz->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $offlinequiz->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    // Description by Juergen Zimmer (Tim Hunt):
    // 1. If the offlinequiz is set to not show grades while the offlinequiz is still open,
    // and is set to show grades after the offlinequiz is closed, then create the
    // grade_item with a show-after date that is the offlinequiz close date.
    // 2. If the offlinequiz is set to not show grades at either of those times,
    // create the grade_item as hidden.
    // 3. If the offlinequiz is set to show grades, create the grade_item visible.
    $openreviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    $closedreviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks < question_display_options::MARK_AND_MAX) {
        $params['hidden'] = 1;

    } else if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks >= question_display_options::MARK_AND_MAX) {
        if ($offlinequiz->timeclose) {
            $params['hidden'] = $offlinequiz->timeclose;
        } else {
            $params['hidden'] = 1;
        }
    } else {
        // A) both open and closed enabled
        // B) open enabled, closed disabled - we can not "hide after",
        // grades are kept visible even after closing.
        $params['hidden'] = 0;
    }

    if (!$params['hidden']) {
        // If the grade item is not hidden by the offlinequiz logic, then we need to
        // hide it if the offlinequiz is hidden from students.
        $cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
        if ($cm) {
            $params['hidden'] = !$cm->visible;
        } else {
            $params['hidden'] = !$offlinequiz->visible;
        }
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebookgrades = grade_get_grades($offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id);
    if (!empty($gradebookgrades->items)) {
        $gradeitem = $gradebookgrades->items[0];
        if ($gradeitem->hidden) {
            $params['hidden'] = 1;
        }
        if ($gradeitem->locked) {
            $confirmregrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirmregrade) {
                if (!AJAX_SCRIPT) {
                    $message = get_string('gradeitemislocked', 'grades');
                    $backlink = $CFG->wwwroot . '/mod/offlinequiz/edit.php?q=' . $offlinequiz->id .
                    '&amp;mode=overview';
                    $regradelink = qualified_me() . '&amp;confirm_regrade=1';
                    echo $OUTPUT->box_start('generalbox', 'notice');
                    echo '<p>'. $message .'</p>';
                    echo $OUTPUT->container_start('buttons');
                    echo $OUTPUT->single_button($regradelink, get_string('regradeanyway', 'grades'));
                    echo $OUTPUT->single_button($backlink,  get_string('cancel'));
                    echo $OUTPUT->container_end();
                    echo $OUTPUT->box_end();
                }
                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/offlinequiz', $offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id, 0, $grades, $params);
}

/**
 * @param int $offlinequizid the offlinequiz id.
 * @param int $userid the userid.
 * @param string $status 'all', 'finished' or 'unfinished' to control
 * @param bool $includepreviews
 * @return an array of all the user's results at this offlinequiz. Returns an empty
 *      array if there are none.
 */
function offlinequiz_get_user_results($offlinequizid, $userid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

    $params = array();

    $params['offlinequizid'] = $offlinequizid;
    $params['userid'] = $userid;
    return $DB->get_records_select('offlinequiz_results',
            "offlinequizid = :offlinequizid AND userid = :userid AND status = 'complete'", $params, 'id ASC');
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $offlinequiznode
 */
function offlinequiz_extend_settings_navigation($settings, $offlinequiznode) {
    global $PAGE, $CFG;

    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $offlinequiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/offlinequiz:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('groupquestions', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/edit.php', array('cmid' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_edit',
                new pix_icon('t/edit', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('createofflinequiz', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/createquiz.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_createpdfs',
                new pix_icon('a/add_file', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('participantslists', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/participants.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_participants',
                new pix_icon('i/group', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('results', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/report.php', array('id' => $PAGE->cm->id, 'mode' => 'overview')),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_results',
                new pix_icon('i/report', ''));
        $offlinequiznode->add_node($node, $beforekey);
    }

    question_extend_settings_navigation($offlinequiznode, $PAGE->cm->context)->trim_if_empty();
}

/**
 * Determine the correct number of decimal places required to format a grade.
 *
 * @param object $offlinequiz The offlinequiz table row, only $offlinequiz->decimalpoints is used.
 * @return integer
 */
function offlinequiz_get_grade_format($offlinequiz) {
    if (empty($offlinequiz->questiondecimalpoints)) {
        $offlinequiz->questiondecimalpoints = -1;
    }

    if ($offlinequiz->questiondecimalpoints == -1) {
        return $offlinequiz->decimalpoints;
    }

    return $offlinequiz->questiondecimalpoints;
}

/**
 * @param array $questionids of question ids.
 * @return bool whether any of these questions are used by any instance of this module.
 */
function offlinequiz_questions_in_use($questionids) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    list($test, $params) = $DB->get_in_or_equal($questionids);

    // Either the questions are used in the group questions, or in results, or in template qubas.
    return $DB->record_exists_select('offlinequiz_group_questions', 'questionid ' . $test, $params) ||
           question_engine::questions_in_use($questionids, new qubaid_join('{offlinequiz_results} quiza',
            'quiza.usageid', '')) ||
           question_engine::questions_in_use($questionids, new qubaid_join('{offlinequiz_groups} groupa',
            'groupa.templateusageid', ''));
}
