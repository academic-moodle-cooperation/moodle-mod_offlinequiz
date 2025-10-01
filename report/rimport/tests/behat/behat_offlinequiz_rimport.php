<?php
// This file is part of offlinequiz_rimport - http://moodle.org/
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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\DriverException;
use Behat\Step\When;
/**
 * Behat steps in plugin offlinequiz_rimport
 *
 * @package    offlinequiz_rimport
 * @category   test
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_offlinequiz_rimport extends behat_base {

    /**
     * Adds the files to the offlinequiz and evaluates them
     *
     * @When /^I upload the file :filename to the offlinequiz :entityname and let it evaluate$/
     *
     * @param string $entityname The name of the offlinequiz
     * @param string $filename
     */
    #[When('I upload the file :filename to the offlinequiz :entityname and let it evaluate')]
    #[\core\attribute\example('I upload the file "/mod/../import_multipage_incomplete.zip" to the offlinequiz "testofflinequiz" and let it evaluate')]
    public function i_upload_the_file_to_the_offlinequiz_and_let_it_evaluate($filename, $entityname): void {
        global $CFG, $DB;
        $this->execute('behat_navigation::i_am_on_page_instance', [$this->escape($entityname), 'offlinequiz activity']);
        $this->execute("behat_general::i_click_on", [get_string('upload'), 'link']);
        $this->execute('behat_repository_upload::i_upload_file_to_filemanager', [$filename, 'ZIP- or image-file']);
        $this->execute("behat_general::i_click_on", [get_string('import', 'offlinequiz_rimport'), 'button']);

        // run adhoc tasks
        \core\cron::setup_user();

        // Discard task output as not appropriate for Behat output!
        ob_start();

        // Run all tasks which have a scheduled runtime of before in 3 hours.
        $timenow = time() + 3600;

        while (
                $task = \core\task\manager::get_next_adhoc_task($timenow)
        ) {
            // Clean the output buffer between tasks.
            ob_clean();

            // Run the task.
            \core\cron::run_inner_adhoc_task($task);

            // Check whether the task record still exists.
            // If a task was successful it will be removed.
            // If it failed then it will still exist.
            if ($DB->record_exists('task_adhoc', ['id' => $task->get_id()])) {
                // End ouptut buffering and flush the current buffer.
                // This should be from just the current task.
                ob_end_flush();

                throw new DriverException('An adhoc task failed', 0);
            }
        }
    }

}
