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
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at>
 * @copyright     2016 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 3.1+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_offlinequiz\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/cron.php');

class temporary_file_deletion_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('temporaryfiledeletiontask', 'mod_offlinequiz');
    }

    public function execute() {
        global $DB;
        // Delete old temporary files not needed any longer.
        $keepdays = get_config('offlinequiz', 'keepfilesfordays');
        $keepseconds = $keepdays * 24 * 60 * 60;
        $timenow = time();
        $sql = "SELECT id
              FROM {offlinequiz_queue}
             WHERE timecreated < :expiretime";
        $params = array('expiretime' => (int) $timenow - $keepseconds);

        // First we get the IDs of cronjobs older than the configured number of days.
        $jobids = $DB->get_fieldset_sql($sql, $params);
        foreach ($jobids as $jobid) {
            $dirname = null;
            // Delete all temporary files and the database entries.
            if ($files = $DB->get_records('offlinequiz_queue_data', array('queueid' => $jobid))) {
                foreach ($files as $file) {
                    if (empty($dirname)) {
                        $pathparts = pathinfo($file->filename);
                        $dirname = $pathparts['dirname'];
                    }
                    $DB->delete_records('offlinequiz_queue_data', array('id' => $file->id));
                }
                // Remove the temporary directory.
                echo "Removing dir " . $dirname . "\n";
                remove_dir($dirname);
            }
        }
    }
}