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

/**
 * Class partlist_table
 *
 * @package    offlinequiz_correct
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * participants list table
 */
class partlist_table extends selectall_table {
    /**
     * end html
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
        $options = ['check' => get_string('checkparts', 'offlinequiz'),
            'uncheck' => get_string('uncheckparts', 'offlinequiz')];
        echo html_writer::select(
            $options,
            'action',
            '',
            ['' => 'choosedots'],
            ['onchange' => 'this.form.submit(); return true;']
        );

        echo '<noscript id="noscriptmenuaction" style="display: inline;"><div>';
        echo '<input type="submit" value="' . get_string('go') . '" /></div></noscript>';
        echo '<script type="text/javascript">' . "\n<!--\n" .
            'document.getElementById("noscriptmenuaction").style.display = "none";' . "\n-->\n" . '</script>';
        echo '</td></tr></table>';
        echo '  </center>';
        // Close form.
        echo ' </div>';
        echo '</form></div>';
    }

    /**
     * print the initials bar
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
