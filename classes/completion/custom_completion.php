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
 * The mod_offlinequiz custom_completion
 *
 * @package    mod_offlinequiz
 * @author  2021 Thomas Wedekind <thomas.wedekind@univie.ac.at>
 * @copyright 2021 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since Moodle 3.11
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_offlinequiz\completion;

use context_module;
use core_completion\activity_custom_completion;
use grade_grade;
use grade_item;
use quiz;
use quiz_access_manager;

/**
 * Activity custom completion subclass for the offlinequiz activity.
 *
 * Class for defining mod_offlinequiz's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given quiz instance and a user.
 * Copied and modified from the quiz plugin
 * @package   mod_offlinequiz
 * @copyright 2021 Shamim Rezaie <shamim@moodle.com>
 * @author Thomas Wedekind <thomas.wedekind@univie.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Check passing grade (or no attempts left) requirement for completion.
     *
     * @return bool True if the passing grade (or no attempts left) requirement is disabled or met.
     */
    protected function check_passing_grade(): bool {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        // Check for passing grade.
        $item = grade_item::fetch([
            'courseid' => $this->cm->get_course()->id,
            'itemtype' => 'mod',
            'itemmodule' => 'offlinequiz',
            'iteminstance' => $this->cm->instance,
            'outcomeid' => null
        ]);
        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, [$this->userid], false);
            if (!empty($grades[$this->userid]) && $grades[$this->userid]->is_passed($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        $this->validate_rule($rule);

        switch ($rule) {
            case 'completionpass':
                $status = static::check_passing_grade();
                break;
        }
        return empty($status) ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionpass'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionpass' => get_string('completiondetail:passgrade', 'mod_offlinequiz')
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpass',
        ];
    }
}
