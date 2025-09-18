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
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/
namespace mod_offlinequiz\correct;
use html_writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * participants list table
 */
class offlinequiz_partlist_table extends offlinequiz_selectall_table {
    /**
     * the html after the table
     * @return void
     */
    public function wrap_html_finish() {
        $strselectall = get_string('selectall', 'offlinequiz');
        $strselectnone = get_string('selectnone', 'offlinequiz');

        echo '<table id="commands">';
        echo '<tr><td>';
        echo '<a href="#" class="selectall">'. $strselectall . '</a> / ';
        echo '<a href="#" class="deselectall">' . $strselectnone . '</a> ';
        echo '&nbsp;&nbsp;';
        $options = ['check' => get_string('checkparts', 'offlinequiz'),
                'uncheck' => get_string('uncheckparts', 'offlinequiz')];
        echo html_writer::select($options, 'action', '', ['' => 'choosedots'],
                ['onchange' => 'this.form.submit(); return true;']);

        echo '<noscript id="noscriptmenuaction" style="display: inline;"><div>';
        echo '<input type="submit" value="'.get_string('go').'" /></div></noscript>';
        echo '<script type="text/javascript">' . "\n<!--\n" .
            'document.getElementById("noscriptmenuaction").style.display = "none";'."\n-->\n".'</script>';
        echo '</td></tr></table>';
        echo '  </center>';
        // Close form.
        echo ' </div>';
        echo '</form></div>';

    }
    /**
     * the initials bar
     * @param mixed $alpha
     * @param mixed $current
     * @param mixed $class
     * @param mixed $title
     * @param mixed $urlvar
     * @return void
     */
    protected function print_one_initials_bar($alpha, $current, $class, $title, $urlvar) {
        echo html_writer::start_tag('div', ['class' => 'initialbar ' . $class]) .
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
