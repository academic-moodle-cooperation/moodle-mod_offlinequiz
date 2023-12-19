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
 * The file defines some subclasses that can be used when you are building
 * a report like the overview or responses report.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/
namespace mod_offlinequiz\correct;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class offlinequiz_selectall_table extends \flexible_table {

    protected $reportscript;
    protected $params;


    public function __construct($uniqueid, $reportscript, $params) {
        parent::__construct($uniqueid);
        $this->reportscript = $reportscript;
        $this->params = $params;
    }

    public function wrap_html_start() {

        echo '<div id="tablecontainer" class="centerbox">';
        echo '<form id="reportform" method="post" action="'. $this->reportscript .
             '" onsubmit="return confirm(\'' . $this->params['strreallydel'] . '\');">';
        echo ' <div>';

        foreach ($this->params as $name => $value) {
            echo '<input type="hidden" name="' . $name .'" value="' . $value . '" />';
        }
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo '  <center>';
    }

    public function wrap_html_finish() {
        $strselectall = get_string('selectall', 'offlinequiz');
        $strselectnone = get_string('selectnone', 'offlinequiz');

        echo '<table id="commands">';
        echo '<tr><td>';
        echo '<a href="#" class="selectall">'. $strselectall . '</a> / ';
        echo '<a href="#" class="deselectall">' . $strselectnone . '</a> ';
        echo '&nbsp;&nbsp;';
        echo '<input type="submit" value="'.get_string('deleteselectedpages', 'offlinequiz_rimport')
              . '" class="btn btn-secondary"/>';
        echo '</td></tr></table>';
        echo '  </center>';
        // Close form.
        echo ' </div>';
        echo '</form></div>';
    }
} // End class.
