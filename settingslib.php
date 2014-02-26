<?php
// This file is for Moodle - http://moodle.org/
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
 * This is the settingslib for the offlinequiz admin settings
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Admin settings class for the offlinequiz review opitions.
 *
 * @copyright  2008 The University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_offlinequiz_admin_review_setting extends admin_setting {
    /**#@+
     * @var integer should match the constants defined in {@link mod_offlinequiz_display_options}.
     * again, copied for performance reasons.
     */
    const DURING =            0x10000;
    const IMMEDIATELY_AFTER = 0x01000;
    const LATER_WHILE_OPEN =  0x00100;
    const AFTER_CLOSE =       0x00010;
    /**#@-*/

    /**
     * @var boolean|null forced checked / disabled attributes for the during time.
     */
    protected $duringstate;

    /**
     * This should match {@link mod_offlinequiz_mod_form::$reviewfields} but copied
     * here because generating the admin tree needs to be fast.
     * @return array
     */
    public static function fields() {
        return array(
                'attempt' => get_string('theattempt', 'offlinequiz'),
                'correctness' => get_string('whethercorrect', 'question'),
                'marks' => get_string('marks', 'offlinequiz'),
//                'feedback' => get_string('feedback', 'question'),
                'specificfeedback' => get_string('specificfeedback', 'question'),
                'generalfeedback' => get_string('generalfeedback', 'question'),
                'rightanswer' => get_string('rightanswer', 'question'),
//                'overallfeedback' => get_string('overallfeedback', 'offlinequiz'),
                'sheet' => get_string('scannedform', 'offlinequiz'),
                'gradedsheet' => get_string('gradedscannedform', 'offlinequiz')
                );
    }

    /**
     * 
     * @param unknown_type $name
     * @param unknown_type $visiblename
     * @param unknown_type $description
     * @param unknown_type $defaultsetting
     * @param unknown_type $duringstate
     */
    public function __construct($name, $visiblename, $description,
            $defaultsetting, $duringstate = null) {
        $this->duringstate = $duringstate;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * @return int all times.
     */
    public static function all_on() {
        // self::DURING | self::IMMEDIATELY_AFTER | self::LATER_WHILE_OPEN |
        return self::AFTER_CLOSE;
    }

    protected static function times() {
        return array(
        //             self::DURING => get_string('reviewduring', 'offlinequiz'),
        //      		self::IMMEDIATELY_AFTER => get_string('reviewimmediately', 'offlinequiz'),
        //             self::LATER_WHILE_OPEN => get_string('reviewopen', 'offlinequiz'),
                self::AFTER_CLOSE => ''
        );
    }

    /**
     * 
     * @param unknown_type $data
     */
    protected function normalise_data($data) {
        $times = self::times();
        $value = 0;
        foreach ($times as $timemask => $name) {
            if ($timemask == self::DURING && !is_null($this->duringstate)) {
                if ($this->duringstate) {
                    $value += $timemask;
                }
            } else if (!empty($data[$timemask])) {
                $value += $timemask;
            }
        }
        return $value;
    }

    /**
     * (non-PHPdoc)
     * @see admin_setting::get_setting()
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * (non-PHPdoc)
     * @see admin_setting::write_setting()
     */
    public function write_setting($data) {
        if (is_array($data) || empty($data)) {
            $data = $this->normalise_data($data);
        }
        $this->config_write($this->name, $data);
        return '';
    }

    /**
     * (non-PHPdoc)
     * @see admin_setting::output_html()
     */
    public function output_html($data, $query = '') {
        if (is_array($data) || empty($data)) {
            $data = $this->normalise_data($data);
        }

        $return = '<div class="group"><input type="hidden" name="' .
                $this->get_full_name() . '[' . self::DURING . ']" value="0" />';
        foreach (self::times() as $timemask => $namestring) {
            $id = $this->get_id(). '_' . $timemask;
            $state = '';
            if ($data & $timemask) {
                $state = 'checked="checked" ';
            }
            if ($timemask == self::DURING && !is_null($this->duringstate)) {
                $state = 'disabled="disabled" ';
                if ($this->duringstate) {
                    $state .= 'checked="checked" ';
                }
            }
            $return .= '<span><input type="checkbox" name="' .
                    $this->get_full_name() . '[' . $timemask . ']" value="1" id="' . $id .
                    '" ' . $state . '/> <label for="' . $id . '">' .
                    $namestring . "</label></span>\n";
        }
        $return .= "</div>\n";

        return format_admin_setting($this, $this->visiblename, $return,
                $this->description, true, '', get_string('everythingon', 'offlinequiz'), $query);
    }
}
