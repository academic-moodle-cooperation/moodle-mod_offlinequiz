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
 * This is the settingslib for the offlinequiz admin settings
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Admin settings class for the offlinequiz review opitions.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_offlinequiz_admin_review_setting extends admin_setting {
    /**
     * @var integer should match the constants defined in
     */
    const DURING = 0x10000;
    /**
     * immediately after the offlinequiz
     * @var int
     */
    const IMMEDIATELY_AFTER = 0x01000;
    /**
     * later while the offlinequiz is still open
     * @var int
     */
    const LATER_WHILE_OPEN = 0x00100;
    /**
     * after the offlinequiz was closed
     * @var int
     */
    const AFTER_CLOSE = 0x00010;

    /**
     * @var bool|null forced checked / disabled attributes for the during time.
     */
    protected $duringstate;

    /**
     * This should match mod_offlinequiz_mod_form::$reviewfields but copied
     * here because generating the admin tree needs to be fast.
     * @return array
     */
    public static function fields() {
        return [
                'attempt' => get_string('theattempt', 'offlinequiz'),
                'correctness' => get_string('whethercorrect', 'question'),
                'marks' => get_string('marks', 'offlinequiz'),
                'specificfeedback' => get_string('specificfeedback', 'question'),
                'generalfeedback' => get_string('generalfeedback', 'question'),
                'rightanswer' => get_string('rightanswer', 'question'),
                'sheet' => get_string('scannedform', 'offlinequiz'),
                'gradedsheet' => get_string('gradedscannedform', 'offlinequiz'),
                ];
    }

    /**
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $defaultsetting
     * @param string $duringstate
     */
    public function __construct(
        $name,
        $visiblename,
        $description,
        $defaultsetting,
        $duringstate = null
    ) {
        $this->duringstate = $duringstate;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * returns all on
     * @return int all times.
     */
    public static function all_on() {
        return self::AFTER_CLOSE;
    }
    /**
     * summary of times
     * @return string[]
     */
    protected static function times() {
        return [
                self::AFTER_CLOSE => '',
        ];
    }

    /**
     *
     * @param array $data
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
     * write setting
     * @param mixed $data
     * @return string
     */
    public function write_setting($data) {
        if (is_array($data) || empty($data)) {
            $data = $this->normalise_data($data);
        }
        $this->config_write($this->name, $data);
        return '';
    }

    /**
     * output html
     * @param mixed $data
     * @param mixed $query
     * @return string
     */
    public function output_html($data, $query = '') {
        if (is_array($data) || empty($data)) {
            $data = $this->normalise_data($data);
        }

        $return = '<div class="group"><input type="hidden" name="' .
                $this->get_full_name() . '[' . self::DURING . ']" value="0" />';
        foreach (self::times() as $timemask => $namestring) {
            $id = $this->get_id() . '_' . $timemask;
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

        return format_admin_setting(
            $this,
            $this->visiblename,
            $return,
            $this->description,
            true,
            '',
            get_string('everythingon', 'offlinequiz'),
            $query
        );
    }
}
