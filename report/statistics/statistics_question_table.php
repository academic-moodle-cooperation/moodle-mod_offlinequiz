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
 * Offlinequiz statistics report, table for showing statistics about a particular question.
 *
 * @package   offlinequiz_statistics
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');


/**
 * This table shows statistics about a particular question.
 *
 * Lists the responses that students made to this question, with frequency counts.
 *
 * The responses may be grouped, either by subpart of the question, or by the
 * answer they match.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_statistics_question_table extends flexible_table {
    /** @var object this question with a _stats field. */
    protected $questiondata;

    /**
     * Constructor.
     * @param $qid the id of the particular question whose statistics are being
     * displayed.
     */
    public function __construct($qid) {
        parent::__construct('mod-offlinequiz-report-statistics-question-table' . $qid);
        $this->defaultdownloadformat  = 'excel';
    }

    /**
     * Set up the columns and headers and other properties of the table and then
     * call flexible_table::setup() method.
     *
     * @param moodle_url $reporturl the URL to redisplay this report.
     * @param object $question a question with a _stats field
     * @param bool $hassubqs
     */
    public function question_setup($reporturl, $questiondata,
            offlinequiz_statistics_response_analyser $responsestats) {
        $this->questiondata = $questiondata;

        $this->define_baseurl($reporturl->out());
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable generalbox boxaligncenter');

        // Define the table columns.
        $columns = array();
        $headers = array();

        $columns[] = 'part';
        $headers[] = '';

        $columns[] = 'response';
        $headers[] = get_string('response', 'offlinequiz_statistics');

        $columns[] = 'fraction';
        $headers[] = get_string('optiongrade', 'offlinequiz_statistics');

        $columns[] = 'count';
        $headers[] = get_string('count', 'offlinequiz_statistics');

        $columns[] = 'frequency';
        $headers[] = get_string('frequency', 'offlinequiz_statistics');

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(false);

        $this->column_class('fraction', 'numcol');
        $this->column_class('count', 'numcol');
        $this->column_class('frequency', 'numcol');

        $this->column_suppress('part');
        $this->column_suppress('responseclass');

        parent::setup();
    }

    protected function format_percentage($fraction) {
        return format_float($fraction * 100, 2) . '%';
    }

    /**
     * The mark fraction that this response earns.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_fraction($response) {
        if (is_null($response->fraction)) {
            return '';
        }

        return $this->format_percentage($response->fraction);
    }

    /**
     * The frequency with which this response was given.
     * @param object $response containst the data to display.
     * @return string contents of this table cell.
     */
    protected function col_frequency($response) {
        if (!$this->questiondata->_stats->s) {
            return '';
        }

        return $this->format_percentage($response->count / $this->questiondata->_stats->s);
    }
}
