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
 * Define the backup_offlinequiz_activity_task
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/backup/moodle2/backup_offlinequiz_stepslib.php');

class backup_offlinequiz_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Generate the offlinequiz.xml file containing all the offlinequiz information
        // and annotating used questions.
        $this->add_step(new backup_offlinequiz_activity_structure_step('offlinequiz_structure', 'offlinequiz.xml'));

        // Note: Following  steps must be present
        // in all the activities using question banks (only offlinequiz for now).
        // TODO: Specialise these step to a new subclass of backup_activity_task.

        // Process all the annotated questions to calculate the question
        // categories needing to be included in backup for this activity
        // plus the categories belonging to the activity context itself.
        $this->add_step(new backup_calculate_question_categories('activity_question_categories'));

        // Clean backup_temp_ids table from questions. We already
        // have used them to detect question_categories and aren't
        // needed anymore.
        $this->add_step(new backup_delete_temp_questions('clean_temp_questions'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of offlinequizzes.
        $search = "/(" . $base . "\/mod\/offlinequiz\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OFFLINEQUIZINDEX*$2@$', $content);

        // Link to offlinequiz view by moduleid.
        $search = "/(" . $base . "\/mod\/offlinequiz\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OFFLINEQUIZVIEWBYID*$2@$', $content);

        // Link to offlinequiz view by offlinequizid.
        $search = "/(" . $base . "\/mod\/offlinequiz\/view.php\?q\=)([0-9]+)/";
        $content = preg_replace($search, '$@OFFLINEQUIZVIEWBYQ*$2@$', $content);

        return $content;
    }
}
