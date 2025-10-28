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

namespace mod_offlinequiz\settings;

/**
 * Class admin_setting_configtext_user_formula
 *
 * @package    mod_offlinequiz
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configtext_user_formula extends \admin_setting_configtext {
    /**
     * validate the configtext of a user formula
     * @param mixed $data
     * @return bool|string
     */
    public function validate($data) {
        global $DB, $CFG;

        $valid = false;
        // Allow paramtype to be a custom regex if it is the form of /pattern/.
        if (preg_match('#^/.*/$#', $this->paramtype)) {
            if (preg_match($this->paramtype, $data)) {
                $valid = true;
            } else {
                return get_string('validateerror', 'admin');
            }
        } else if ($this->paramtype === PARAM_RAW) {
            $valid = true;
        } else {
             $cleaned = clean_param($data, $this->paramtype);
            if ("$data" === "$cleaned") { // Implicit conversion to string is needed to do exact comparison.
                $valid = true;
            } else {
                return get_string('validateerror', 'admin');
            }
        }
        if ($valid) {
             require_once($CFG->dirroot . "/mod/offlinequiz/locallib.php");

             $matches = [];
            if (preg_match(OFFLINEQUIZ_USER_FORMULA_REGEXP, $data, $matches)) {
                $prefix = $matches[1];
                $digits = intval($matches[2]);
                $postfix = $matches[3];
                $field = $matches[4];
                // Check the number of digits.
                if ($digits < 1 || $digits > 10) {
                    return get_string('invalidnumberofdigits', 'offlinequiz');
                }
                   // Check for valid user table field.
                if ($testusers = $DB->get_records('user', null, '', '*', 0, 1)) {
                    if (count($testusers) > 0 && $testuser = array_pop($testusers)) {
                        if (isset($testuser->{$field})) {
                            set_config('ID_digits', $digits, 'offlinequiz');
                            set_config('ID_prefix', $prefix, 'offlinequiz');
                            set_config('ID_postfix', $postfix, 'offlinequiz');
                            set_config('ID_field', $field, 'offlinequiz');
                            return true;
                        } else {
                            return get_string('invaliduserfield', 'offlinequiz');
                        }
                    }
                }
            } else {
                return get_string('invalidformula', 'offlinequiz');
            }
        }
        return '';
    }
}
