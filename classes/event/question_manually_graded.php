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
 * The mod_offlinequiz question manually graded event.
 *
 * @package    core
 * @author  2014 Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright 2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since Moodle 2.7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_offlinequiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_offlinequiz question manually graded event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int offlinequizid: the id of the offlinequiz.
 *      - int resultid: the id of the result.
 *      - int slot: the question number in the result.
 * }
 *
 * @package    core
 * @since      Moodle 2.7
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_manually_graded extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'question';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventquestionmanuallygraded', 'mod_offlinequiz');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' manually graded the question with id '$this->objectid' for the result " .
            "with id '{$this->other['resultid']}' in the offlinequiz with the course module id '$this->contextinstanceid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/offlinequiz/comment.php', array('resultid' => $this->other['resultid'],
            'slot' => $this->other['slot']));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['offlinequizid'])) {
            throw new \coding_exception('The \'offlinequizid\' value must be set in other.');
        }

        if (!isset($this->other['resultid'])) {
            throw new \coding_exception('The \'resultid\' value must be set in other.');
        }

        if (!isset($this->other['slot'])) {
            throw new \coding_exception('The \'slot\' value must be set in other.');
        }

    }
}
