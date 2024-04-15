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
 * Offlinequiz statistics report, table for showing statistics of each question and its answers.
 *
 * @package   offlinequiz_statistics
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once(__DIR__ . '/statisticslib.php');


/**
 * This table has one row for each question in the offlinequiz, with sub-rows when
 * random questions appear. There are columns for the various statistics.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_question_answer_statistics_table extends flexible_table {
    /** @var object the offlinequiz settings. */
    protected $offlinequiz;

    /** @var object this question with a _stats field. */
    protected $questiondata;

    /** @var integer the offlinequiz course_module id. */
    protected $cmid;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('mod-offlinequiz-report-statistics-report');
        $this->defaultdownloadformat  = 'excel';
    }

    public function set_questiondata($questiondata) {
        $this->questiondata = $questiondata;
    }

    /**
     * Set up the columns and headers and other properties of the table and then
     * call flexible_table::setup() method.
     *
     * @param object $offlinequiz the offlinequiz settings
     * @param int $cmid the offlinequiz course_module id
     * @param moodle_url $reporturl the URL to redisplay this report.
     * @param int $s number of attempts included in the statistics.
     */
    public function statistics_setup($offlinequiz, $cmid, $reporturl, $s) {
        $this->offlinequiz = $offlinequiz;
        $this->cmid = $cmid;

        // Define the table columns.
        $columns = array();
        $headers = array();

        $columns[] = 'number';
        $headers[] = get_string('questionnumber', 'offlinequiz_statistics');

        if (!$this->is_downloading()) {
            $columns[] = 'icon';
            $headers[] = '';
            $columns[] = 'actions';
            $headers[] = '';
        } else {
            $columns[] = 'qtype';
            $headers[] = get_string('questiontype', 'offlinequiz_statistics');
        }

        $columns[] = 'name';
        $headers[] = get_string('question');

        $columns[] = 'response';
        $headers[] = get_string('response', 'offlinequiz_statistics');

        $columns[] = 'fraction';
        $headers[] = get_string('optiongrade', 'offlinequiz_statistics');

        $columns[] = 'count';
        $headers[] = get_string('count', 'offlinequiz_statistics');

        $columns[] = 'frequency';
        $headers[] = get_string('frequency', 'offlinequiz_statistics');

        mod_offlinequiz_create_common_statistics_headers($headers, $columns, $s);

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(false);

        $this->column_class('name', 'questiontext');
        $this->column_class('fraction', 'numcol');
        $this->column_class('count', 'numcol');
        $this->column_class('frequency', 'numcol');
        $this->column_class('s', 'numcol');
        $this->column_class('facility', 'numcol');
        $this->column_class('sd', 'numcol');

        $this->column_class('intended_weight', 'numcol');
        $this->column_class('effective_weight', 'numcol');
        $this->column_class('discrimination_index', 'numcol');

        $this->column_class('correct', 'correct');
        $this->column_class('partially', 'partially');
        $this->column_class('wrong', 'wrong');

        // Set up the table.
        $this->define_baseurl($reporturl->out());

        $this->collapsible(true);

        $this->set_attribute('id', 'questionstatistics');
        $this->set_attribute('class', 'generaltable generalbox boxaligncenter');

        parent::setup();
    }

    /**
     * The question number.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_number($question) {
        if (property_exists($question, 'number')) {
            return $question->number;
        }
        return '';
    }

    /**
     * The question type icon.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_icon($question) {
        if (property_exists($question, 'qtype') && $question->qtype) {
            return print_question_icon($question, true);
        } else {
            return '';
        }
    }

    /**
     * Actions that can be performed on the question by this user (e.g. edit or preview).
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_actions($question) {
        if (property_exists($question, 'actions') && $question->actions) {
            return offlinequiz_question_action_icons($this->offlinequiz, $this->cmid, $question, $this->baseurl);
        } else {
            return '';
        }
    }

    /**
     * The question type name.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_qtype($question) {
        if (property_exists($question, 'qtype') && $question->qtype) {
            return question_bank::get_qtype_name($question->qtype);
        } else {
            return '';
        }
    }

    /**
     * The question name.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_name($question) {
        $name = $question->name;

        if ($this->is_downloading()) {
            return $name;
        }

        if (property_exists($question, '_stats')) {
            require_once('statisticslib.php');
            return mod_offlinequiz_print_column_stats_name($question, $this->baseurl, $name, $this->is_dubious_question($question));
        }
        return $name;
    }

    /**
     * The number of attempts at this question.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_response($question) {
        if (property_exists($question, 'response') && property_exists($question, 'part')) {
            if ($this->is_downloading()) {
                return $this->format_text($question->part . ' ' . $question->response);
            } else {
                return format_text(html_to_text($question->part . ' ' . $question->response));
            }
        } else {
            return '';
        }
    }

    /**
     * The mark fraction that this response earns.
     *
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_fraction($response) {
        if (!isset($response->fraction) || !is_numeric($response->fraction)) {
            return '';
        }
        return format_float(100.0 * $response->fraction, $this->offlinequiz->decimalpoints) . '%';
    }

    /**
     * The count of this response.
     *
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_count($response) {
        if (!isset($response->count)) {
            return '';
        }

        return $response->count;
    }

    /**
     * The frequency with which this response was given.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_frequency($response) {
        if (!isset($response->count) || $this->questiondata->_stats->s <= 0) {
            return '';
        }

        return format_float(100.0 * $response->count / $this->questiondata->_stats->s, $this->offlinequiz->decimalpoints) . '%';
    }


    /**
     * The number of attempts at this question.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_s($question) {
        if (!property_exists($question, '_stats') || !isset($question->_stats->s)) {
            return '';
        }

        return $question->_stats->s;
    }

    /**
     * The facility index (average fraction).
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_facility($question) {
        if (!property_exists($question, '_stats') || is_null($question->_stats->facility)) {
            return '';
        }

        return format_float($question->_stats->facility * 100, 2) . '%';
    }

    /**
     * The standard deviation of the fractions.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_sd($question) {
        if (!property_exists($question, '_stats') || is_null($question->_stats->sd) || $question->_stats->maxmark == 0) {
            return '';
        }

        // Redmine 1760: no percentage here.
        return format_float($question->_stats->sd, 2);
    }

    /**
     * The intended question weight. Maximum mark for the question as a percentage
     * of maximum mark for the offlinequiz. That is, the indended influence this question
     * on the student's overall mark.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_intended_weight($question) {
        if (!property_exists($question, '_stats')) {
            return '';
        }
        return offlinequiz_report_scale_summarks_as_percentage(
                $question->_stats->maxmark, $this->offlinequiz);
    }

    /**
     * The effective question weight. That is, an estimate of the actual
     * influence this question has on the student's overall mark.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_effective_weight($question) {
        global $OUTPUT;

        if (!property_exists($question, '_stats') || $question->_stats->subquestion) {
            return '';
        }

        if (!is_numeric($question->_stats->effectiveweight)) {
            $result = get_string('notavailable', 'offlinequiz');
            if (!$this->is_downloading()) {
                $result .= $OUTPUT->pix_icon('help', get_string('discriminationindex_info', 'offlinequiz'), 'moodle');
            }
            return $result;
        }

        if ($question->_stats->negcovar) {
            $negcovar = get_string('negcovar', 'offlinequiz_statistics');

            if (!$this->is_downloading()) {
                $negcovar = html_writer::tag('div',
                        $negcovar . $OUTPUT->help_icon('negcovar', 'offlinequiz_statistics'),
                        array('class' => 'negcovar'));
            }
            return $negcovar;
        }
        return format_float($question->_stats->effectiveweight, 2) . '%';
    }

    /**
     * Discrimination index. This is the product moment correlation coefficient
     * between the fraction for this qestion, and the average fraction for the
     * other questions in this offlinequiz.
     * @param object $question containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_discrimination_index($question) {
        global $OUTPUT;
        if (!property_exists($question, '_stats')) {
            return '';
        }
        if (!is_numeric($question->_stats->discriminationindex)) {
            $result = get_string('notavailable', 'offlinequiz');
            if(!$this->is_downloading()) {
            $result .= $OUTPUT->pix_icon('help', get_string('discriminationindex_info', 'offlinequiz'), 'moodle');
            }
            return $result;
        }

        return format_float($question->_stats->discriminationindex, 2) . '%';
    }

    /**
     * This method encapsulates the test for whether a question should be considered dubious.
     *
     * @param object question the question object with a property _stats which
     * includes all the stats for the question.
     * @return bool is this question possibly not pulling it's weight?
     */
    protected function is_dubious_question($question) {
        if (!property_exists($question, '_stats') || !is_numeric($question->_stats->discriminativeefficiency)) {
            return false;
        }

        return $question->_stats->discriminationindex < 0;
    }

    public function  wrap_html_start() {
        // Horrible Moodle 2.0 wide-content work-around.
        if (!$this->is_downloading()) {
            echo html_writer::start_tag('div', array('id' => 'tablecontainer',
                    'class' => 'statistics-tablecontainer'));
        }
    }

    public function wrap_html_finish() {
        if (!$this->is_downloading()) {
            echo html_writer::end_tag('div');
        }
    }

    /**
     * This function is not part of the public api.
     */
    public function download_buttons() {
        // Disabled due to problems with multiple tables.
    }
    /**
     * The frequency with which this response was given.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_correct($question) {
        if (property_exists($question, '_stats') && property_exists($question->_stats, 'correct') && $question->_stats->s != 0) {
            $result = $question->_stats->correct . ' (' . round($question->_stats->correct / $question->_stats->s * 100) . '%)';
        } else {
            $result = '';
        }
        return $result;
    }

    /**
     * The frequency with which this response was given.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_partially($question) {
        if (property_exists($question, '_stats') && property_exists($question->_stats, 'partially') && $question->_stats->s != 0) {

            return $question->_stats->partially . ' (' . round($question->_stats->partially / $question->_stats->s * 100) . '%)';
        } else {
            return '';
        }
    }

    /**
     * The frequency with which this response was given.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_wrong($question) {
        if (property_exists($question, '_stats') && property_exists($question->_stats, 'wrong') && $question->_stats->s != 0) {
            return $question->_stats->wrong . ' (' . round($question->_stats->wrong / $question->_stats->s * 100) . '%)';
        } else {
            return '';
        }
    }
}
