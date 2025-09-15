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
 * The file defines the results table shown in the overview report
 *
 * @package       offlinequiz_overview
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
/**
 * the table for the results
 */
class offlinequiz_results_table extends flexible_table {
    /**
     * offlinequiz db entry
     * @var stdClass
     */
    protected $offlinequiz;
    /**
     * if there are no results
     * @var int
     */
    protected $noresults;
    /**
     * size of the page
     * @var int
     */
    protected $psize;

    /**
     * constructor
     * @param mixed $uniqueid
     * @param mixed $params
     */
    public function __construct($uniqueid, $params) {
        parent::__construct($uniqueid);
        $this->offlinequiz = $params['offlinequiz'];
        $this->noresults = $params['noresults'];
        $this->psize = $params['pagesize'];
    }
    /**
     * what to print if there is nothing to display
     * @return void
     */
    public function print_nothing_to_display() {
        global $OUTPUT;
        return;
    }
    /**
     * the html before the table
     * @return void
     */
    public function wrap_html_start() {
        $strreallydel  = addslashes(get_string('deleteresultcheck', 'offlinequiz'));
        echo '<div id="tablecontainer" class="centerbox">';
        echo '<form id="reportform" method="post" action="report.php" onsubmit="return confirm(\'' . $strreallydel . '\');">';
        echo ' <div>';
        echo '  <input type="hidden" name="q" value="' . $this->offlinequiz->id . '" />';
        echo '  <input type="hidden" name="mode" value="overview" />';
        echo '  <input type="hidden" name="action" value="delete" />';
        echo '  <input type="hidden" name="noresults" value="' . $this->noresults . '" />';
        echo '  <input type="hidden" name="pagesize" value="' . $this->psize . '" />';
        echo '  <input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo '  <center>';
    }
    /**
     * the html after the table
     * @return void
     */
    public function wrap_html_finish() {
        $strselectall = get_string('selectall', 'offlinequiz');
        $strselectnone = get_string('selectnone', 'offlinequiz');

        echo '<table id="commands">';
        echo ' <tr><td>';
        echo '  <a href="#" class="selectall">'. $strselectall . '</a> / ';
        echo '  <a href="#" class="deselectall">' . $strselectnone . '</a> ';
        echo '  &nbsp;&nbsp;';
        echo '  <input class="btn btn-secondary" type="submit" value="'.get_string('deleteselectedresults', 'offlinequiz').'"/>';
        echo ' </td></tr></table>';
        echo '  </center>';
        // Close form.
        echo ' </div>';
        echo '</form></div>';
    }
    /**
     * print the bar for the initials
     * @param mixed $alpha
     * @param mixed $current
     * @param mixed $class
     * @param mixed $title
     * @param mixed $urlvar
     * @return void
     */
    protected function print_one_initials_bar($alpha, $current, $class, $title, $urlvar) {
        echo html_writer::start_tag('div', ['class' => 'initialbar linkbox ' . $class]) .
        $title . ' : ';
        if ($current) {
            echo html_writer::link($this->baseurl->out(false, [$urlvar => '']), get_string('all'));
        } else {
            echo html_writer::tag('strong', get_string('all'));
        }
        echo '&nbsp;';

        foreach ($alpha as $letter) {
            if ($letter === $current) {
                echo html_writer::tag('strong', $letter);
            } else {
                echo html_writer::link($this->baseurl->out(false, [$urlvar => $letter]), $letter);
            }
            echo '&nbsp;';
        }

        echo html_writer::end_tag('div');
    }

}
