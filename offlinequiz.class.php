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
 * Class for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/classes/structure.php');
require_once($CFG->dirroot . '/mod/offlinequiz/accessmanager.php');

use mod_offlinequiz\structure;

/**
 * A class encapsulating a offlinequiz and the questions it contains, and making the
 * information available to scripts like view.php.
 *
 * Initially, it only loads a minimal amout of information about each question - loading
 * extra information only when necessary or when asked. The class tracks which questions
 * are loaded.
 *
 * @copyright  2008 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */
class offlinequiz {
    // Fields initialised in the constructor.
    protected $course;
    protected $cm;
    protected $offlinequiz;
    protected $context;

    // Fields set later if that data is needed.
    protected $questions = null;
    protected $accessmanager = null;
    protected $ispreviewuser = null;

    /**
     * Constructor, assuming we already have the necessary data loaded.
     *
     * @param object $offlinequiz the row from the offlinequiz table.
     * @param object $cm the course_module object for this offlinequiz.
     * @param object $course the row from the course table for the course we belong to.
     * @param bool $getcontext intended for testing - stops the constructor getting the context.
     */
    public function __construct($offlinequiz, $cm, $course, $getcontext = true) {
        $this->offlinequiz = $offlinequiz;
        $this->cm = $cm;
        $this->offlinequiz->cmid = $this->cm->id;
        $this->course = $course;
        if ($getcontext && !empty($cm->id)) {
            $this->context = context_module::instance($cm->id);
        }
    }

    /**
     * Static function to create a new offlinequiz object for a specific user.
     *
     * @param int $offlinequizid the the offlinequiz id.
     * @param int $userid the the userid.
     * @return offlinequiz the new offlinequiz object
     */
    public static function create($offlinequizid, $offlinegroupid, $userid = null) {
        global $DB;

        $offlinequiz = offlinequiz_access_manager::load_offlinequiz_and_settings($offlinequizid);
        $offlinequiz->groupid = $offlinegroupid;
        $course = $DB->get_record('course', array('id' => $offlinequiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id, $course->id, false, MUST_EXIST);

        // Update offlinequiz with override information.
        if ($userid) {
            $offlinequiz = offlinequiz_update_effective_access($offlinequiz, $userid);
        }

        return new offlinequiz($offlinequiz, $cm, $course);
    }

    // Functions for loading more data.

    /**
     * Load just basic information about all the questions in this offlinequiz.
     */
    public function preload_questions() {
        $this->questions = question_preload_questions(null,
                'slot.maxmark, slot.id AS slotid, slot.slot, slot.page',
                '{offlinequiz_group_questions} slot ON slot.offlinequizid = :offlinequizid
                  AND slot.offlinegroupid = :offlinegroupid
                  AND q.id = slot.questionid',
                array('offlinequizid' => $this->offlinequiz->id,
                      'offlinegroupid' => $this->offlinequiz->groupid),
                 'slot.slot');
    }

    /**
     * Fully load some or all of the questions for this offlinequiz. You must call
     * {@link preload_questions()} first.
     *
     * @param array $questionids question ids of the questions to load. null for all.
     */
    public function load_questions($questionids = null) {
        if ($this->questions === null) {
            throw new coding_exception('You must call preload_questions before calling load_questions.');
        }
        if (is_null($questionids)) {
            $questionids = array_keys($this->questions);
        }
        $questionstoprocess = array();
        foreach ($questionids as $id) {
            if (array_key_exists($id, $this->questions)) {
                $questionstoprocess[$id] = $this->questions[$id];
            }
        }
        get_question_options($questionstoprocess);
    }

    /**
     * Get an instance of the {@link \mod_offlinequiz\structure} class for this offlinequiz.
     * @return \mod_offlinequiz\structure describes the questions in the offlinequiz.
     */
    public function get_structure() {
        return \mod_offlinequiz\structure::create_for_offlinequiz($this);
    }

    // Simple getters.
    /** @return int the course id. */
    public function get_courseid() {
        return $this->course->id;
    }

    /** @return object the row of the course table. */
    public function get_course() {
        return $this->course;
    }

    /** @return int the offlinequiz id. */
    public function get_offlinequizid() {
        return $this->offlinequiz->id;
    }

    /** @return int the offlinequiz group id. */
    public function get_offlinegroupid() {
        return $this->offlinequiz->groupid;
    }

    /** @return object the row of the offlinequiz table. */
    public function get_offlinequiz() {
        return $this->offlinequiz;
    }

    /** @return string the name of this offlinequiz. */
    public function get_offlinequiz_name() {
        return $this->offlinequiz->name;
    }

    /** @return int the offlinequiz navigation method. */
    public function get_navigation_method() {
        return $this->offlinequiz->navmethod;
    }

    /** @return int the number of attempts allowed at this offlinequiz (0 = infinite). */
    public function get_num_attempts_allowed() {
        return $this->offlinequiz->attempts;
    }

    /** @return int the course_module id. */
    public function get_cmid() {
        return $this->cm->id;
    }

    /** @return object the course_module object. */
    public function get_cm() {
        return $this->cm;
    }

    /** @return object the module context for this offlinequiz. */
    public function get_context() {
        return $this->context;
    }

    /**
     * @return bool wether the current user is someone who previews the offlinequiz,
     * rather than attempting it.
     */
    public function is_preview_user() {
        if (is_null($this->ispreviewuser)) {
            $this->ispreviewuser = has_capability('mod/offlinequiz:preview', $this->context);
        }
        return $this->ispreviewuser;
    }

    /**
     * @return whether any questions have been added to this offlinequiz.
     */
    public function has_questions() {
        if ($this->questions === null) {
            $this->preload_questions();
        }
        return !empty($this->questions);
    }

    /**
     * @param int $id the question id.
     * @return object the question object with that id.
     */
    public function get_question($id) {
        return $this->questions[$id];
    }

    /**
     * @param array $questionids question ids of the questions to load. null for all.
     */
    public function get_questions($questionids = null) {
        if (is_null($questionids)) {
            $questionids = array_keys($this->questions);
        }
        $questions = array();
        foreach ($questionids as $id) {
            if (!array_key_exists($id, $this->questions)) {
                throw new moodle_exception('cannotstartmissingquestion', 'offlinequiz', $this->view_url());
            }
            $questions[$id] = $this->questions[$id];
            $this->ensure_question_loaded($id);
        }
        return $questions;
    }

    /**
     * @param int $timenow the current time as a unix timestamp.
     * @return offlinequiz_access_manager and instance of the offlinequiz_access_manager class
     *      for this offlinequiz at this time.
     */
    public function get_access_manager($timenow) {
        if (is_null($this->accessmanager)) {
            $this->accessmanager = new offlinequiz_access_manager($this, $timenow,
                    has_capability('mod/offlinequiz:ignoretimelimits', $this->context, null, false));
        }
        return $this->accessmanager;
    }

    /**
     * Wrapper round the has_capability funciton that automatically passes in the offlinequiz context.
     */
    public function has_capability($capability, $userid = null, $doanything = true) {
        return has_capability($capability, $this->context, $userid, $doanything);
    }

    /**
     * Wrapper round the require_capability funciton that automatically passes in the offlinequiz context.
     */
    public function require_capability($capability, $userid = null, $doanything = true) {
        return require_capability($capability, $this->context, $userid, $doanything);
    }

    // URLs related to this attempt.
    /**
     * @return string the URL of this offlinequiz's view page.
     */
    public function view_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/offlinequiz/view.php?id=' . $this->cm->id;
    }

    /**
     * @return string the URL of this offlinequiz's edit page.
     */
    public function edit_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/offlinequiz/edit.php?cmid=' . $this->cm->id;
    }

    /**
     * @param int $attemptid the id of an attempt.
     * @param int $page optional page number to go to in the attempt.
     * @return string the URL of that attempt.
     */
    public function attempt_url($attemptid, $page = 0) {
        global $CFG;
        $url = $CFG->wwwroot . '/mod/offlinequiz/attempt.php?attempt=' . $attemptid;
        if ($page) {
            $url .= '&page=' . $page;
        }
        return $url;
    }

    /**
     * @return string the URL of this offlinequiz's edit page. Needs to be POSTed to with a cmid parameter.
     */
    public function start_attempt_url($page = 0) {
        $params = array('cmid' => $this->cm->id, 'sesskey' => sesskey());
        if ($page) {
            $params['page'] = $page;
        }
        return new moodle_url('/mod/offlinequiz/startattempt.php', $params);
    }

    /**
     * @param int $attemptid the id of an attempt.
     * @return string the URL of the review of that attempt.
     */
    public function review_url($attemptid) {
        return new moodle_url('/mod/offlinequiz/review.php', array('attempt' => $attemptid));
    }

    /**
     * @param int $attemptid the id of an attempt.
     * @return string the URL of the review of that attempt.
     */
    public function summary_url($attemptid) {
        return new moodle_url('/mod/offlinequiz/summary.php', array('attempt' => $attemptid));
    }

    // Bits of content.

    /**
     * @param bool $unfinished whether there is currently an unfinished attempt active.
     * @return string if the offlinequiz policies merit it, return a warning string to
     *      be displayed in a javascript alert on the start attempt button.
     */
    public function confirm_start_attempt_message($unfinished) {
        if ($unfinished) {
            return '';
        }

        if ($this->offlinequiz->timelimit && $this->offlinequiz->attempts) {
            return get_string('confirmstartattempttimelimit', 'offlinequiz', $this->offlinequiz->attempts);
        } else if ($this->offlinequiz->timelimit) {
            return get_string('confirmstarttimelimit', 'offlinequiz');
        } else if ($this->offlinequiz->attempts) {
            return get_string('confirmstartattemptlimit', 'offlinequiz', $this->offlinequiz->attempts);
        }

        return '';
    }

    /**
     * If $reviewoptions->attempt is false, meaning that students can't review this
     * attempt at the moment, return an appropriate string explaining why.
     *
     * @param int $when One of the mod_offlinequiz_display_options::DURING,
     *      IMMEDIATELY_AFTER, LATER_WHILE_OPEN or AFTER_CLOSE constants.
     * @param bool $short if true, return a shorter string.
     * @return string an appropraite message.
     */
    public function cannot_review_message($when, $short = false) {

        if ($short) {
            $langstrsuffix = 'short';
            $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        } else {
            $langstrsuffix = '';
            $dateformat = '';
        }

        if ($when == mod_offlinequiz_display_options::DURING ||
                $when == mod_offlinequiz_display_options::IMMEDIATELY_AFTER) {
            return '';
        } else if ($when == mod_offlinequiz_display_options::LATER_WHILE_OPEN && $this->offlinequiz->timeclose &&
                $this->offlinequiz->reviewattempt & mod_offlinequiz_display_options::AFTER_CLOSE) {
            return get_string('noreviewuntil' . $langstrsuffix, 'offlinequiz',
                    userdate($this->offlinequiz->timeclose, $dateformat));
        } else {
            return get_string('noreview' . $langstrsuffix, 'offlinequiz');
        }
    }

    /**
     * @param string $title the name of this particular offlinequiz page.
     * @return array the data that needs to be sent to print_header_simple as the $navigation
     * parameter.
     */
    public function navigation($title) {
        global $PAGE;
        $PAGE->navbar->add($title);
        return '';
    }

    // Private methods.
    /**
     * Check that the definition of a particular question is loaded, and if not throw an exception.
     * @param $id a questionid.
     */
    protected function ensure_question_loaded($id) {
        if (isset($this->questions[$id]->_partiallyloaded)) {
            throw new moodle_offlinequiz_exception($this, 'questionnotloaded', $id);
        }
    }
}
