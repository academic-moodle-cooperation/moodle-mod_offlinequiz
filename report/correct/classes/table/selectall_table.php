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

namespace offlinequiz_correct\table;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');
/**
 * Class selectall_table
 *
 * @package    offlinequiz_correct
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selectall_table extends \flexible_table {
    /**
     * the script of the fitting report
     * @var string
     */
    protected $reportscript;
    /**
     * params array
     * @var array
     */
    protected $params;

    /**
     * constructor
     * @param mixed $uniqueid
     * @param mixed $reportscript
     * @param mixed $params
     */
    public function __construct($uniqueid, $reportscript, $params) {
        parent::__construct($uniqueid);
        $this->reportscript = $reportscript;
        $this->params = $params;
    }

    /**
     * there is nothing to display
     * @return void
     */
    public function print_nothing_to_display() {
        global $OUTPUT;
        return;
    }

    /**
     * start the html
     * @return void
     */
    public function wrap_html_start() {
        echo '<div id="tablecontainer" class="centerbox">';
        echo '<form id="reportform" method="post" action="' . $this->reportscript .
        '" onsubmit="return confirm(\'' . $this->params['strreallydel'] . '\');">';
        echo ' <div>';

        foreach ($this->params as $name => $value) {
            echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
        }
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo '  <center>';
    }

    /**
     * end the html
     * @return void
     */
    public function wrap_html_finish() {
        $strselectall = get_string('selectall', 'offlinequiz');
        $strselectnone = get_string('selectnone', 'offlinequiz');

        echo '<table id="commands">';
        echo '<tr><td>';
        echo '<a href="#" class="selectall">' . $strselectall . '</a> / ';
        echo '<a href="#" class="deselectall">' . $strselectnone . '</a> ';
        echo '&nbsp;&nbsp;';
        echo '<input type="submit" value="' . get_string('deleteselectedpages', 'offlinequiz_rimport')
        . '" class="btn btn-secondary"/>';
        echo '</td></tr></table>';
        echo '  </center>';
        // Close form.
        echo ' </div>';
        echo '</form></div>';
    }
}
