<?php
// This file is part of Moodle - http://moodle.org/
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

namespace mod_offlinequiz\courseformat;

use action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\url;
use core_courseformat\local\overview\overviewitem;
use stdClass;

/**
 * Offline quiz overview integration (for Moodle 5.1+)
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * The course module.
     * @var stdClass
     */
    private $coursemodule;

    /**
     * The offlinequiz.
     * @var stdClass
     */
    private $offlinequiz;

    /**
     * modulecontext
     * @var \context
     */
    private $modulecontext;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        /** @var \core\output\renderer_helper $rendererhelper the renderer helper */
        protected readonly \core\output\renderer_helper $rendererhelper,
    ) {
        global $DB;
        $this->coursemodule = get_coursemodule_from_id('offlinequiz', $cm->id);
        $this->offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance]);
        $this->modulecontext = \context_module::instance($cm->id);
        parent::__construct($cm);
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'testdate' => $this->get_test_date(),
            'results' => $this->get_results(),
        ];
    }

    /**
     * get the result for this Person
     * @return stdClass
     */
    private function get_result() {
        global $DB, $USER;
        $select = "SELECT *
             FROM {offlinequiz_results} qa
            WHERE qa.offlinequizid = :offlinequizid
              AND qa.userid = :userid
              AND qa.status = 'complete'";
        $result = $DB->get_record_sql($select, ['offlinequizid' => $this->offlinequiz->id, 'userid' => $USER->id]);
        return $result;
    }

    /**
     * Returns the actions of this offlinequiz for this user.
     * @return overviewitem
     */
    public function get_actions_overview(): ?overviewitem {

        global $OUTPUT, $CFG;
        $content = '';
        require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
        if (has_capability('mod/offlinequiz:attempt', $this->cm->context)) {
            if ($this->offlinequiz->showtutorial && !$this->user_has_result()) {
                $content .= $OUTPUT->render(new action_link(
                    url: new url(
                        '/mod/offlinequiz/tutorial.php',
                        ['id' => $this->cm->id],
                    ),
                    text: get_string('tutorialbutton', 'offlinequiz'),
                    attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
                ));
            }
        }
        if (has_capability('mod/offlinequiz:manage', $this->cm->context)) {
            $content .= $OUTPUT->render(new action_link(
                url: new url(
                    '/mod/offlinequiz/view.php',
                    ['id' => $this->cm->id],
                ),
                text: get_string('view'),
                attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
            ));
            if ($this->get_correction_count()) {
                $content .= $OUTPUT->render(new action_link(
                    url: new url(
                        '/mod/offlinequiz/report.php',
                        ['q' => $this->coursemodule->instance, 'mode' => 'correct'],
                    ),
                    text: get_string('correctbutton', 'offlinequiz', $this->get_correction_count()),
                    attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
                ));
            }
        }
        $result = $this->get_result();
        if ($result && offlinequiz_results_open($this->offlinequiz) && has_capability('mod/offlinequiz:attempt', $this->modulecontext)) {
            $options = offlinequiz_get_review_options($this->offlinequiz, $result, $this->modulecontext);
            if (
                $result->timefinish && ($options->attempt == \question_display_options::VISIBLE ||
                $options->marks >= \question_display_options::MAX_ONLY ||
                $options->sheetfeedback == \question_display_options::VISIBLE ||
                $options->gradedsheetfeedback == \question_display_options::VISIBLE
                )
            ) {
                $content .= $OUTPUT->render(new action_link(
                    url: new url(
                        '/mod/offlinequiz/review.php',
                        ['q' => $this->coursemodule->instance, 'resultid' => $result->id],
                    ),
                    text: get_string('viewresultbutton', 'offlinequiz'),
                    attributes: ['class' => button::SECONDARY_OUTLINE->classes()],
                ));
            }
        }
        return new overviewitem(
            name: get_string('actions'),
            value: '',
            content: $content,
            textalign: text_align::CENTER,
        );
    }

    /**
     * Check if the user has a result in the offlinequiz
     */
    public function user_has_result(): bool {
        global $DB, $USER;
        $result = $DB->record_exists('offlinequiz_results', ['userid' => $USER->id, 'offlinequizid' => $this->offlinequiz->id]);
        return $result;
    }

    /**
     * get amount of errors to correct for this OQ
     * @return string
     */
    private function get_correction_count() {
        global $DB;
        $sql = "SELECT count(*) count
        FROM {offlinequiz_scanned_pages}
        WHERE offlinequizid = :offlinequizid
        AND (status = 'error'
            OR status = 'suspended'
            OR error = 'missingpages')";
        $count = $DB->get_record_sql($sql, ['offlinequizid' => $this->coursemodule->instance]);
        return $count->count;
    }


    /**
     * get amount of errors to correct for this OQ
     * @return string
     */
    private function get_results_count(): string {
        global $DB;
        return $DB->count_records('offlinequiz_results', ['offlinequizid' => $this->offlinequiz->id, 'status' => 'complete']);
    }

    /**
     * get the testdate of this offlinequiz;
     * @return overviewitem
     */
    public function get_test_date(): overviewitem | null {

        $content = '-';
        $value = 0;
        if ($this->offlinequiz->time) {
            if (
                has_capability('mod/offlinequiz:attempt', $this->cm->context)
                || has_capability('mod/offlinequiz:manage', $this->cm->context)
            ) {
                $content = userdate($this->offlinequiz->time);
                $value = $this->offlinequiz->time;
            }
        }
        if (!$value) {
            return null;
        }
        return new overviewitem(
            name: get_string('testdate', 'offlinequiz'),
            value: $value,
            content: $content,
        );
    }

    /**
     * Get the testdate of this offlinequiz
     * @return overviewitem|null
     */
    public function get_results(): overviewitem|null {
        if (has_capability('mod/offlinequiz:manage', $this->cm->context)) {
            $content = $this->get_results_count();
            return new overviewitem(
                name: get_string('results', 'offlinequiz'),
                value: $content,
                content: $content,
            );
        }
        return null;
    }
}
