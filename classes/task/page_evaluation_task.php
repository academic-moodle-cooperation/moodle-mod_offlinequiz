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
 * calls the offlinequiz cron task for evaluating uploaded files
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2016 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.1+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_offlinequiz\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/cron.php');
/**
 * the page evaluation task (deprecated)
 */
class page_evaluation_task extends \core\task\scheduled_task {
    /**
     * get name of the task
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('pageevaluationtask', 'mod_offlinequiz');
    }
    /**
     * execute the task
     * @return void
     */
    public function execute() {
        \offlinequiz_evaluation_cron();
    }
}
