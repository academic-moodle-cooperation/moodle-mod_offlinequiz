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

class hotspot_deletion_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('hotspotdeletiontask', 'mod_offlinequiz');
    }

    public function execute() {
        global $DB;
        // Remove all saved hotspot data that is older than 7 days.
        $timenow = time();

        // We have to make sure we do this atomic for each scanned page.
        $sql = "SELECT DISTINCT(scannedpageid)
              FROM {offlinequiz_hotspots}
             WHERE time < :expiretime";
        $params = array('expiretime' => (int) $timenow - 604800);

        // First we get the different IDs.
        $ids = $DB->get_fieldset_sql($sql, $params);

        if (!empty($ids)) {
            list($isql, $iparams) = $DB->get_in_or_equal($ids);

            // Now we delete the records.
            $DB->delete_records_select('offlinequiz_hotspots', 'scannedpageid ' . $isql, $iparams);
        }
    }
}