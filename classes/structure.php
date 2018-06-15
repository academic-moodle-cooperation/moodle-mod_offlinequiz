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
 * Defines the \mod_offlinequiz\structure class.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_offlinequiz;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/offlinequiz.class.php');


/**
 * Offlinequiz structure class.
 *
 * The structure of the offlinequiz. That is, which questions it is built up
 * from. This is used on the Edit offlinequiz page (edit.php) and also when
 * starting an attempt at the offlinequiz (startattempt.php). Once an attempt
 * has been started, then the attempt holds the specific set of questions
 * that that student should answer, and we no longer use this class.
 *
 */
class structure {
    /** @var \offlinequiz the offlinequiz this is the structure of. */
    protected $offlinequizobj = null;

    /**
     * @var \stdClass[] the questions in this offlinequiz. Contains the row from the questions
     * table, with the data from the offlinequiz_group_questions table added, and also question_categories.contextid.
     */
    protected $questions = array();

    /** @var \stdClass[] offlinequiz_group_questions.id => the offlinequiz_group_questions rows for this offlinequiz, agumented by sectionid. */
    protected $slots = array();

    /** @var \stdClass[] offlinequiz_group_questions.slot => the offlinequiz_group_questions rows for this offlinequiz, agumented by sectionid. */
    protected $slotsinorder = array();

    /**
     * @var \stdClass[] currently a dummy. Holds data that will match the
     * offlinequiz_sections, once it exists.
     */
    protected $sections = array();

    /** @var bool caches the results of can_be_edited. */
    protected $canbeedited = null;

    private $warnings = array();

    /**
     * Create an instance of this class representing an empty offlinequiz.
     * @return structure
     */
    public static function create() {
        return new self();
    }

    /**
     * Create an instance of this class representing the structure of a given offlinequiz.
     * @param \offlinequiz $offlinequizobj the offlinequiz.
     * @return structure
     */
    public static function create_for_offlinequiz($offlinequizobj) {
        $structure = self::create();
        $structure->offlinequizobj = $offlinequizobj;
        $structure->populate_structure($offlinequizobj->get_offlinequiz());
        return $structure;
    }

    /**
     * Whether there are any questions in the offlinequiz.
     * @return bool true if there is at least one question in the offlinequiz.
     */
    public function has_questions() {
        return !empty($this->questions);
    }

    /**
     * Get the number of questions in the offlinequiz.
     * @return int the number of questions in the offlinequiz.
     */
    public function get_question_count() {
        return count($this->questions);
    }

    /**
     * Get the information about the question with this id.
     * @param int $questionid The question id.
     * @return \stdClass the data from the questions table, augmented with
     * question_category.contextid, and the offlinequiz_group_questions data for the question in this offlinequiz.
     */
    public function get_question_by_id($questionid) {
        return $this->questions[$questionid];
    }

    /**
     * Get the information about the question in a given slot.
     * @param int $slotnumber the index of the slot in question.
     * @return \stdClass the data from the questions table, augmented with
     * question_category.contextid, and the offlinequiz_group_questions data for the question in this offlinequiz.
     */
    public function get_question_in_slot($slotnumber) {
        return $this->questions[$this->slotsinorder[$slotnumber]->questionid];
    }

    /**
     * Get the course module id of the offlinequiz.
     * @return int the course_modules.id for the offlinequiz.
     */
    public function get_cmid() {
        return $this->offlinequizobj->get_cmid();
    }


    /**
     * Get id of the offlinequiz.
     * @return int the offlinequiz.id for the offlinequiz.
     */
    public function get_offlinequizid() {
        return $this->offlinequizobj->get_offlinequizid();
    }

    /**
     * Get id of the offlinequiz group.
     * @return int the offlinequiz_groups.id for the offlinequiz.
     */
    public function get_offlinegroupid() {
        return $this->offlinequizobj->get_offlinegroupid();
    }

    /**
     * Get the offlinequiz object.
     * @return \stdClass the offlinequiz settings row from the database.
     */
    public function get_offlinequiz() {
        return $this->offlinequizobj->get_offlinequiz();
    }

    /**
     * Whether the question in the offlinequiz are shuffled for each attempt.
     * @return bool true if the questions are shuffled.
     */
    public function is_shuffled() {
        return $this->offlinequizobj->get_offlinequiz()->shufflequestions;
    }

    /**
     * Offlinequizzes can only be repaginated if they have not been attempted, the
     * questions are not shuffled, and there are two or more questions.
     * @return bool whether this offlinequiz can be repaginated.
     */
    public function can_be_repaginated() {
        return !$this->is_shuffled() && $this->can_be_edited()
                && $this->get_question_count() >= 2;
    }

    /**
     * Offlinequizzes can only be edited if they have not been attempted.
     * @return bool whether the offlinequiz can be edited.
     */
    public function can_be_edited() {
        if ($this->canbeedited === null) {
            $this->canbeedited = !$this->offlinequizobj->get_offlinequiz()->docscreated;
        }
        return $this->canbeedited;
    }

    /**
     * This offlinequiz can only be edited if they have not been attempted.
     * Throw an exception if this is not the case.
     */
    public function check_can_be_edited() {
        if (!$this->can_be_edited()) {
            $reportlink = offlinequiz_attempt_summary_link_to_reports($this->get_offlinequiz(),
                    $this->offlinequizobj->get_cm(), $this->offlinequizobj->get_context());
            throw new \moodle_exception('cannoteditafterattempts', 'offlinequiz',
                    new \moodle_url('/mod/offlinequiz/edit.php', array('cmid' => $this->get_cmid())), $reportlink);
        }
    }

    /**
     * How many questions are allowed per page in the offlinequiz.
     * This setting controls how frequently extra page-breaks should be inserted
     * automatically when questions are added to the offlinequiz.
     * @return int the number of questions that should be on each page of the
     * offlinequiz by default.
     */
    public function get_questions_per_page() {
        return $this->offlinequizobj->get_offlinequiz()->questionsperpage;
    }

    /**
     * Get offlinequiz slots.
     * @return \stdClass[] the slots in this offlinequiz.
     */
    public function get_slots() {
        return $this->slots;
    }

    /**
     * Get offlinequiz slots.
     * @return \stdClass[] the slots in this offlinequiz.
     */
    public function get_slots_in_order() {
        return $this->slotsinorder;
    }

    /**
     * Is this slot the first one on its page?
     * @param int $slotnumber the index of the slot in question.
     * @return bool whether this slot the first one on its page.
     */
    public function is_first_slot_on_page($slotnumber) {
        if ($slotnumber == 1) {
            return true;
        }
        return $this->slotsinorder[$slotnumber]->page != $this->slotsinorder[$slotnumber - 1]->page;
    }

    /**
     * Is this slot the last one on its page?
     * @param int $slotnumber the index of the slot in question.
     * @return bool whether this slot the last one on its page.
     */
    public function is_last_slot_on_page($slotnumber) {
        if (!isset($this->slotsinorder[$slotnumber + 1])) {
            return true;
        }
        return $this->slotsinorder[$slotnumber]->page != $this->slotsinorder[$slotnumber + 1]->page;
    }

    /**
     * Is this slot the last one in the offlinequiz?
     * @param int $slotnumber the index of the slot in question.
     * @return bool whether this slot the last one in the offlinequiz.
     */
    public function is_last_slot_in_offlinequiz($slotnumber) {
        end($this->slotsinorder);
        return $slotnumber == key($this->slotsinorder);
    }

    /**
     * Get the final slot in the offlinequiz.
     * @return \stdClass the offlinequiz_group_questions for for the final slot in the offlinequiz.
     */
    public function get_last_slot() {
        return end($this->slotsinorder);
    }

    /**
     * Get the page a given slot is on.
     * @param int $slotnumber the index of the slot in question.
     * @return int the page number of the page that slot is on.
     */
    public function get_page_number_for_slot($slotnumber) {
        return $this->slotsinorder[$slotnumber]->page;
    }

    /**
     * Get a slot by it's id. Throws an exception if it is missing.
     * @param int $slotid the slot id.
     * @return \stdClass the requested offlinequiz_group_questions row.
     */
    public function get_slot_by_id($slotid) {
        if (!array_key_exists($slotid, $this->slots)) {
            throw new \coding_exception('The \'slotid\' ' . $slotid . ' could not be found.');
        }
        return $this->slots[$slotid];
    }

    /**
     * Get all the questions in a section of the offlinequiz.
     * @param int $sectionid the section id.
     * @return \stdClass[] of question/slot objects.
     */
    public function get_questions_in_section($sectionid) {
        $questions = array();
        foreach ($this->slotsinorder as $slot) {
            if ($slot->sectionid == $sectionid) {
                $questions[] = $this->questions[$slot->questionid];
            }
        }
        return $questions;
    }

    /**
     * Get all the sections of the offlinequiz.
     * @return \stdClass[] the sections in this offlinequiz.
     */
    public function get_offlinequiz_sections() {
        return $this->sections;
    }

    /**
     * Get any warnings to show at the top of the edit page.
     * @return string[] array of strings.
     */
    public function get_edit_page_warnings() {
        global $CFG;

        $warnings = array();

        if (!$this->can_be_edited()) {
            $reviewlink = new \moodle_url($CFG->wwwroot . '/mod/offlinequiz/createquiz.php',
                    array ('q' => $this->offlinequizobj->get_offlinequiz()->id,
                           'mode' => 'createpdfs'));
            $warnings[] = get_string('formsexistx', 'offlinequiz', $reviewlink->out(false));
        }
        if (offlinequiz_has_scanned_pages($this->offlinequizobj->get_offlinequizid())) {
            $reviewlink = offlinequiz_attempt_summary_link_to_reports($this->offlinequizobj->get_offlinequiz(),
                    $this->offlinequizobj->get_cm(), $this->offlinequizobj->get_context());
            $warnings[] = get_string('cannoteditafterattempts', 'offlinequiz', $reviewlink);
        }

        if ($this->is_shuffled()) {
            $updateurl = new \moodle_url('/course/mod.php',
                    array('return' => 'true', 'update' => $this->offlinequizobj->get_cmid(), 'sesskey' => sesskey()));
            $updatelink = '<a href="'.$updateurl->out().'">' . get_string('updatethis', '',
                    get_string('modulename', 'offlinequiz')) . '</a>';
            $warnings[] = get_string('shufflequestionsselected', 'offlinequiz', $updatelink);
        }
        if ($this->offlinequizobj->get_offlinequiz()->grade == 0) {
            $warnings[] = '<b>' . get_string('gradeiszero', 'offlinequiz') . '</b>';
        }
        foreach ($this->warnings as $warning) {
            $warnings[] = '<b>' . $warning . '</b>';
        }

        return $warnings;
    }

    /**
     * Get the date information about the current state of the offlinequiz.
     * @return string[] array of two strings. First a short summary, then a longer
     * explanation of the current state, e.g. for a tool-tip.
     */
    public function get_dates_summary() {
        $timenow = time();
        $offlinequiz = $this->offlinequizobj->get_offlinequiz();

        // Exact open and close dates for the tool-tip.
        $dates = array();
        if ($offlinequiz->timeopen > 0) {
            if ($timenow > $offlinequiz->timeopen) {
                $dates[] = get_string('offlinequizopenedon', 'offlinequiz', userdate($offlinequiz->timeopen));
            } else {
                $dates[] = get_string('offlinequizwillopen', 'offlinequiz', userdate($offlinequiz->timeopen));
            }
        }
        if ($offlinequiz->timeclose > 0) {
            if ($timenow > $offlinequiz->timeclose) {
                $dates[] = get_string('offlinequizisclosed', 'offlinequiz', userdate($offlinequiz->timeclose));
            } else {
                $dates[] = get_string('offlinequizcloseson', 'offlinequiz', userdate($offlinequiz->timeclose));
            }
        }
        if (empty($dates)) {
            $dates[] = get_string('alwaysavailable', 'offlinequiz');
        }
        $explanation = implode(', ', $dates);

        // Brief summary on the page.
        if ($timenow < $offlinequiz->timeopen) {
            $currentstatus = get_string('offlinequizisclosedwillopen', 'offlinequiz',
                    userdate($offlinequiz->timeopen, get_string('strftimedatetimeshort', 'langconfig')));
        } else if ($offlinequiz->timeclose && $timenow <= $offlinequiz->timeclose) {
            $currentstatus = get_string('offlinequizisopenwillclose', 'offlinequiz',
                    userdate($offlinequiz->timeclose, get_string('strftimedatetimeshort', 'langconfig')));
        } else if ($offlinequiz->timeclose && $timenow > $offlinequiz->timeclose) {
            $currentstatus = get_string('offlinequizisclosed', 'offlinequiz');
        } else {
            $currentstatus = get_string('offlinequizisopen', 'offlinequiz');
        }

        return array($currentstatus, $explanation);
    }

    /**
     * Set up this class with the structure for a given offlinequiz.
     * @param \stdClass $offlinequiz the offlinequiz settings.
     */
    public function populate_structure($offlinequiz) {
        global $DB;

        $slots = $DB->get_records_sql("
                SELECT slot.id AS slotid, slot.slot, slot.questionid, slot.page, slot.maxmark,
                       q.*, qc.contextid
                  FROM {offlinequiz_group_questions} slot
                  LEFT JOIN {question} q ON q.id = slot.questionid
                  LEFT JOIN {question_categories} qc ON qc.id = q.category
                 WHERE slot.offlinequizid = ?
                   AND slot.offlinegroupid = ?
              ORDER BY slot.slot", array($offlinequiz->id, $offlinequiz->groupid));

        $slots = $this->populate_missing_questions($slots);

        $this->questions = array();
        $this->slots = array();
        $this->slotsinorder = array();
        foreach ($slots as $slotdata) {
            $this->questions[$slotdata->questionid] = $slotdata;

            $slot = new \stdClass();
            $slot->id = $slotdata->slotid;
            $slot->slot = $slotdata->slot;
            $slot->offlinequizid = $offlinequiz->id;
            $slot->offlinegroupid = $offlinequiz->groupid;
            $slot->page = $slotdata->page;
            $slot->questionid = $slotdata->questionid;
            $slot->maxmark = $slotdata->maxmark;

            $this->slots[$slot->id] = $slot;
            $this->slotsinorder[$slot->slot] = $slot;
        }

        $section = new \stdClass();
        $section->id = 1;
        $section->offlinequizid = $offlinequiz->id;
        $section->offlinegroupid = $offlinequiz->groupid;
        $section->heading = '';
        $section->firstslot = 1;
        $section->shuffle = false;
        $this->sections = array(1 => $section);

        $this->populate_slots_with_sectionids();
        $this->populate_question_numbers();
    }

    /**
     * Used by populate. Make up fake data for any missing questions.
     * @param \stdClass[] $slots the data about the slots and questions in the offlinequiz.
     * @return \stdClass[] updated $slots array.
     */
    protected function populate_missing_questions($slots) {
        // Address missing question types.
        foreach ($slots as $slot) {
            if ($slot->qtype === null) {
                // If the questiontype is missing change the question type.
                $slot->id = $slot->questionid;
                $slot->category = 0;
                $slot->qtype = 'missingtype';
                $slot->name = get_string('missingquestion', 'offlinequiz');
                $slot->slot = $slot->slot;
                $slot->maxmark = 0;
                $slot->questiontext = ' ';
                $slot->questiontextformat = FORMAT_HTML;
                $slot->length = 1;

            } else if (!\question_bank::qtype_exists($slot->qtype)) {
                $slot->qtype = 'missingtype';
            }
        }

        return $slots;
    }

    /**
     * Fill in the section ids for each slot.
     */
    public function populate_slots_with_sectionids() {
        $nextsection = reset($this->sections);
        $currentsectionid = 1;
        foreach ($this->slotsinorder as $slot) {
            if ($slot->slot == $nextsection->firstslot) {
                $currentsectionid = $nextsection->id;
                $nextsection = next($this->sections);
                if (!$nextsection) {
                    $nextsection = new \stdClass();
                    $nextsection->firstslot = -1;
                }
            }

            $slot->sectionid = $currentsectionid;
        }
    }

    /**
     * Number the questions.
     */
    protected function populate_question_numbers() {
        $number = 1;
        foreach ($this->slots as $slot) {
            $question = $this->questions[$slot->questionid];
            if ($question->length == 0) {
                $question->displayednumber = get_string('infoshort', 'offlinequiz');
            } else {
                $question->displayednumber = $number;
                $number += 1;
            }
        }
    }

    /**
     * Move a slot from its current location to a new location.
     *
     * After callig this method, this class will be in an invalid state, and
     * should be discarded if you want to manipulate the structure further.
     *
     * @param int $idmove id of slot to be moved
     * @param int $idbefore id of slot to come before slot being moved
     * @param int $page new page number of slot being moved
     * @return void
     */
    public function move_slot($idmove, $idbefore, $page) {
        global $DB;

        $this->check_can_be_edited();

        $movingslot = $this->slots[$idmove];
        if (empty($movingslot)) {
            throw new moodle_exception('Bad slot ID ' . $idmove);
        }
        $movingslotnumber = (int) $movingslot->slot;

        // Empty target slot means move slot to first.
        if (empty($idbefore)) {
            $targetslotnumber = 0;
        } else {
            $targetslotnumber = (int) $this->slots[$idbefore]->slot;
        }

        // Work out how things are being moved.
        $slotreorder = array();
        if ($targetslotnumber > $movingslotnumber) {
            $slotreorder[$movingslotnumber] = $targetslotnumber;
            for ($i = $movingslotnumber; $i < $targetslotnumber; $i++) {
                $slotreorder[$i + 1] = $i;
            }
        } else if ($targetslotnumber < $movingslotnumber - 1) {
            $slotreorder[$movingslotnumber] = $targetslotnumber + 1;
            for ($i = $targetslotnumber + 1; $i < $movingslotnumber; $i++) {
                $slotreorder[$i] = $i + 1;
            }
        }

        $trans = $DB->start_delegated_transaction();

        // Slot has moved record new order.
        if ($slotreorder) {
            update_field_with_unique_index('offlinequiz_group_questions', 'slot', $slotreorder,
                    array('offlinequizid' => $this->get_offlinequizid(), 'offlinegroupid' => $this->get_offlinegroupid()));

        }
        // Page has changed. Record it.
        if (!$page) {
            $page = 1;
        }
        if ($movingslot->page != $page) {
            $DB->set_field('offlinequiz_group_questions', 'page', $page,
                    array('id' => $movingslot->id));
        }

        $emptypages = $DB->get_fieldset_sql("
                SELECT DISTINCT page - 1
                  FROM {offlinequiz_group_questions} slot
                 WHERE offlinequizid = ?
                   AND offlinegroupid = ?
                   AND page > 1
                   AND NOT EXISTS (SELECT 1 FROM {offlinequiz_group_questions}
                                           WHERE offlinequizid = ?
                                             AND offlinegroupid = ?
                                             AND page = slot.page - 1)
              ORDER BY page - 1 DESC
                ", array($this->get_offlinequizid(), $this->get_offlinegroupid(),
                         $this->get_offlinequizid(), $this->get_offlinegroupid()));

        foreach ($emptypages as $page) {
            $DB->execute("
                    UPDATE {offlinequiz_group_questions}
                       SET page = page - 1
                     WHERE offlinequizid = ?
                       AND offlinegroupid = ?
                       AND page > ?
                    ", array($this->get_offlinequizid(), $this->get_offlinegroupid(), $page));
        }

        $trans->allow_commit();
    }

    /**
     * Refresh page numbering of offlinequiz slots.
     * @param \stdClass $offlinequiz the offlinequiz object.
     * @param \stdClass[] $slots (optional) array of slot objects.
     * @return \stdClass[] array of slot objects.
     */
    public function refresh_page_numbers($offlinequiz, $slots=array()) {
        global $DB;
        // Get slots ordered by page then slot.
        if (!count($slots)) {
            $slots = $DB->get_records('offlinequiz_group_questions', array('offlinequizid' => $offlinequiz->id,
                        'offlinegroupid' => $offlinequiz->groupid), 'slot, page');
        }

        // Loop slots. Start Page number at 1 and increment as required.
        $pagenumbers = array('new' => 0, 'old' => 0);

        foreach ($slots as $slot) {
            if ($slot->page !== $pagenumbers['old']) {
                $pagenumbers['old'] = $slot->page;
                ++$pagenumbers['new'];
            }

            if ($pagenumbers['new'] == $slot->page) {
                continue;
            }
            $slot->page = $pagenumbers['new'];
        }

        return $slots;
    }

    /**
     * Refresh page numbering of offlinequiz slots and save to the database.
     * @param \stdClass $offlinequiz the offlinequiz object.
     * @return \stdClass[] array of slot objects.
     */
    public function refresh_page_numbers_and_update_db($offlinequiz) {
        global $DB;
        $this->check_can_be_edited();

        $slots = $this->refresh_page_numbers($offlinequiz);

        // Record new page order.
        foreach ($slots as $slot) {
            $DB->set_field('offlinequiz_group_questions', 'page', $slot->page,
                    array('id' => $slot->id));
        }

        return $slots;
    }

    /**
     * Remove a slot from a offlinequiz
     * @param \stdClass $offlinequiz the offlinequiz object.
     * @param int $slotnumber The number of the slot to be deleted.
     */
    public function remove_slot($offlinequiz, $slotnumber) {
        global $DB;

        $this->check_can_be_edited();

        $slot = $DB->get_record('offlinequiz_group_questions', array('offlinequizid' => $offlinequiz->id,
                'offlinegroupid' => $offlinequiz->groupid, 'slot' => $slotnumber));
        $maxslot = $DB->get_field_sql('SELECT MAX(slot)
                                         FROM {offlinequiz_group_questions}
                                        WHERE offlinequizid = ?
                                          AND offlinegroupid = ?',
                     array($offlinequiz->id, $offlinequiz->groupid));
        if (!$slot) {
            return;
        }

        $trans = $DB->start_delegated_transaction();
        $DB->delete_records('offlinequiz_group_questions', array('id' => $slot->id));
        for ($i = $slot->slot + 1; $i <= $maxslot; $i++) {
            $DB->set_field('offlinequiz_group_questions', 'slot', $i - 1,
                    array('offlinequizid' => $offlinequiz->id,
                          'offlinegroupid' => $offlinequiz->groupid,
                                    'slot' => $i));
        }

        $qtype = $DB->get_field('question', 'qtype', array('id' => $slot->questionid));
        if ($qtype === 'random') {
            // This function automatically checks if the question is in use, and won't delete if it is.
            question_delete_question($slot->questionid);
        }

        unset($this->questions[$slot->questionid]);

        $this->refresh_page_numbers_and_update_db($offlinequiz);

        $trans->allow_commit();
    }

    /**
     * Change the max mark for a slot.
     *
     * Saves changes to the question grades in the offlinequiz_group_questions table and any
     * corresponding question_attempts.
     *
     * @param \stdClass $slot row from the offlinequiz_group_questions table.
     * @param float $maxmark the new maxmark.
     * @return bool true if the new grade is different from the old one.
     */
    public function update_slot_maxmark($slot, $maxmark) {
        global $DB;

        $maxmark = unformat_float($maxmark);

        if (abs($maxmark - $slot->maxmark) < 1e-7) {
            // Grade has not changed. Nothing to do.
            return false;
        }

        $trans = $DB->start_delegated_transaction();
        $slot->maxmark = $maxmark;
        $DB->update_record('offlinequiz_group_questions', $slot);

        // We also need to update the maxmark for this question in other offlinequiz groups.
        $offlinequiz = $this->offlinequizobj->get_offlinequiz();
        $currentgroupid = $offlinequiz->groupid;

        $groupids = $DB->get_fieldset_select('offlinequiz_groups', 'id',
                'offlinequizid = :offlinequizid AND id <> :currentid',
                array('offlinequizid' => $offlinequiz->id, 'currentid' => $currentgroupid));
        $params = array();
        if ($groupids) {
            list($gsql, $params) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'grp');

            $sql = "SELECT *
                      FROM {offlinequiz_group_questions}
                     WHERE offlinequizid = :offlinequizid
                       AND offlinegroupid $gsql
                       AND questionid = :questionid ";

            $params['offlinequizid'] = $offlinequiz->id;
            $params['questionid'] = $slot->questionid;

            $otherslots = $DB->get_records_sql($sql, $params);
            foreach ($otherslots as $otherslot) {
                $otherslot->maxmark = $maxmark;
                $DB->update_record('offlinequiz_group_questions', $otherslot);
            }
        }

        // Now look at the maxmark of attemps.
        // We do this already in offlinequiz_update_question_instance.
//         \question_engine::set_max_mark_in_attempts(new \result_qubaids_for_offlinequiz($slot->offlinequizid, $slot->offlinegroupid),
//                 $slot->slot, $maxmark);
        $trans->allow_commit();

        return true;
    }

    /**
     * Add/Remove a pagebreak.
     *
     * Saves changes to the slot page relationship in the offlinequiz_group_questions table and reorders the paging
     * for subsequent slots.
     *
     * @param \stdClass $offlinequiz the offlinequiz object.
     * @param int $slotid id of slot.
     * @param int $type repaginate::LINK or repaginate::UNLINK.
     * @return \stdClass[] array of slot objects.
     */
    public function update_page_break($offlinequiz, $slotid, $type) {
        global $DB;

        $this->check_can_be_edited();

        $offlinequizslots = $DB->get_records('offlinequiz_group_questions',
                 array('offlinequizid' => $offlinequiz->id,
                       'offlinegroupid' => $offlinequiz->groupid), 'slot');
        $repaginate = new \mod_offlinequiz\repaginate($offlinequiz->id, $offlinequiz->groupid, $offlinequizslots);
        $repaginate->repaginate_slots($offlinequizslots[$slotid]->slot, $type);
        $slots = $this->refresh_page_numbers_and_update_db($offlinequiz);

        return $slots;
    }

    public function add_warning($string) {
        $this->warnings[] = $string;
    }
}
