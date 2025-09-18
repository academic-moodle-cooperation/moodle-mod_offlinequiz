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
 * Classes to enforce the various access rules that can apply to a offlinequiz.
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * This class keeps track of the various access rules that apply to a particular
 * offlinequiz, with convinient methods for seeing whether access is allowed.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.2
 */
class offlinequiz_access_manager {
    /** @var offlinequiz the offlinequiz settings object. */
    protected $offlinequizobj;
    /** @var int the time to be considered as 'now'. */
    protected $timenow;
    /** @var array of offlinequiz_access_rule_base. */
    protected $rules = [];

    /**
     * Create an instance for a particular offlinequiz.
     * @param object $offlinequizobj An instance of the class offlinequiz from attemptlib.php.
     *      The offlinequiz we will be controlling access to.
     * @param int $timenow The time to use as 'now'.
     * @param bool $canignoretimelimits Whether this user is exempt from time
     *      limits (has_capability('mod/offlinequiz:ignoretimelimits', ...)).
     */
    public function __construct($offlinequizobj, $timenow, $canignoretimelimits) {
        $this->offlinequizobj = $offlinequizobj;
        $this->timenow = $timenow;
        $this->rules = $this->make_rules($offlinequizobj, $timenow, $canignoretimelimits);
    }

    /**
     * Make all the rules relevant to a particular offlinequiz.
     * @param offlinequiz $offlinequizobj information about the offlinequiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/offlinequiz:ignoretimelimits capability.
     * @return array of {@link offlinequiz_access_rule_base}s.
     */
    protected function make_rules($offlinequizobj, $timenow, $canignoretimelimits) {

        $rules = [];
        foreach (self::get_rule_classes() as $ruleclass) {
            $rule = $ruleclass::make($offlinequizobj, $timenow, $canignoretimelimits);
            if ($rule) {
                $rules[$ruleclass] = $rule;
            }
        }

        $superceededrules = [];
        foreach ($rules as $rule) {
            $superceededrules += $rule->get_superceded_rules();
        }

        foreach ($superceededrules as $superceededrule) {
            unset($rules['offlinequizaccess_' . $superceededrule]);
        }

        return $rules;
    }

    /**
     * get array of of all the installed rule class names.
     * @return array
     */
    protected static function get_rule_classes() {
        return core_component::get_plugin_list_with_class('offlinequizaccess', '', 'rule.php');
    }

    /**
     * Add any form fields that the access rules require to the settings form.
     *
     * Note that the standard plugins do not use this mechanism, becuase all their
     * settings are stored in the offlinequiz table.
     *
     * @param mod_offlinequiz_mod_form $offlinequizform the offlinequiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(
            mod_offlinequiz_mod_form $offlinequizform, MoodleQuickForm $mform) {

        foreach (self::get_rule_classes() as $rule) {
            $rule::add_settings_form_fields($offlinequizform, $mform);
        }
    }

    /**
     * The the options for the Browser security settings menu.
     *
     * @return array key => lang string.
     */
    public static function get_browser_security_choices() {
        $options = ['-' => get_string('none')];
        foreach (self::get_rule_classes() as $rule) {
            $options += $rule::get_browser_security_choices();
        }
        return $options;
    }

    /**
     * Validate the data from any form fields added using {@link add_settings_form_fields()}.
     * @param array $errors the errors found so far.
     * @param array $data the submitted form data.
     * @param array $files information about any uploaded files.
     * @param mod_offlinequiz_mod_form $offlinequizform the offlinequiz form object.
     * @return array $errors the updated $errors array.
     */
    public static function validate_settings_form_fields(array $errors,
            array $data, $files, mod_offlinequiz_mod_form $offlinequizform) {

        foreach (self::get_rule_classes() as $rule) {
            $errors = $rule::validate_settings_form_fields($errors, $data, $files, $offlinequizform);
        }

        return $errors;
    }

    /**
     * Save any submitted settings when the offlinequiz settings form is submitted.
     *
     * Note that the standard plugins do not use this mechanism because their
     * settings are stored in the offlinequiz table.
     *
     * @param object $offlinequiz the data from the offlinequiz form, including $offlinequiz->id
     *      which is the id of the offlinequiz being saved.
     */
    public static function save_settings($offlinequiz) {

        foreach (self::get_rule_classes() as $rule) {
            $rule::save_settings($offlinequiz);
        }
    }

    /**
     * Delete any rule-specific settings when the offlinequiz is deleted.
     *
     * Note that the standard plugins do not use this mechanism because their
     * settings are stored in the offlinequiz table.
     *
     * @param object $offlinequiz the data from the database, including $offlinequiz->id
     *      which is the id of the offlinequiz being deleted.
     * @since Moodle 2.7.1, 2.6.4, 2.5.7
     */
    public static function delete_settings($offlinequiz) {

        foreach (self::get_rule_classes() as $rule) {
            $rule::delete_settings($offlinequiz);
        }
    }

    /**
     * Build the SQL for loading all the access settings in one go.
     * @param int $offlinequizid the offlinequiz id.
     * @param string $basefields initial part of the select list.
     * @return array with two elements, the sql and the placeholder values.
     *      If $basefields is '' then you must allow for the possibility that
     *      there is no data to load, in which case this method returns $sql = ''.
     */
    protected static function get_load_sql($offlinequizid, $rules, $basefields) {
        $allfields = $basefields;
        $alljoins = '{offlinequiz} offlinequiz';
        $allparams = ['offlinequizid' => $offlinequizid];

        foreach ($rules as $rule) {
            list($fields, $joins, $params) = $rule::get_settings_sql($offlinequizid);
            if ($fields) {
                if ($allfields) {
                    $allfields .= ', ';
                }
                $allfields .= $fields;
            }
            if ($joins) {
                $alljoins .= ' ' . $joins;
            }
            if ($params) {
                $allparams += $params;
            }
        }

        if ($allfields === '') {
            return ['', []];
        }

        return ["SELECT $allfields FROM $alljoins WHERE offlinequiz.id = :offlinequizid", $allparams];
    }

    /**
     * Load any settings required by the access rules. We try to do this with
     * a single DB query.
     *
     * Note that the standard plugins do not use this mechanism, becuase all their
     * settings are stored in the offlinequiz table.
     *
     * @param int $offlinequizid the offlinequiz id.
     * @return array setting value name => value. The value names should all
     *      start with the name of the corresponding plugin to avoid collisions.
     */
    public static function load_settings($offlinequizid) {
        global $DB;

        $rules = self::get_rule_classes();
        list($sql, $params) = self::get_load_sql($offlinequizid, $rules, '');

        if ($sql) {
            $data = (array) $DB->get_record_sql($sql, $params);
        } else {
            $data = [];
        }

        foreach ($rules as $rule) {
            $data += $rule::get_extra_settings($offlinequizid);
        }

        return $data;
    }

    /**
     * Load the offlinequiz settings and any settings required by the access rules.
     * We try to do this with a single DB query.
     *
     * Note that the standard plugins do not use this mechanism, becuase all their
     * settings are stored in the offlinequiz table.
     *
     * @param int $offlinequizid the offlinequiz id.
     * @return object mdl_offlinequiz row with extra fields.
     */
    public static function load_offlinequiz_and_settings($offlinequizid) {
        global $DB;

        $rules = self::get_rule_classes();
        list($sql, $params) = self::get_load_sql($offlinequizid, $rules, 'offlinequiz.*');
        $offlinequiz = $DB->get_record_sql($sql, $params, MUST_EXIST);

        foreach ($rules as $rule) {
            foreach ($rule::get_extra_settings($offlinequizid) as $name => $value) {
                $offlinequiz->$name = $value;
            }
        }

        return $offlinequiz;
    }

    /**
     * get the class names of all the active rules. Mainly useful for
     * debugging.
     * @return array
     */
    public function get_active_rule_names() {
        $classnames = [];
        foreach ($this->rules as $rule) {
            $classnames[] = get_class($rule);
        }
        return $classnames;
    }

    /**
     * Accumulates an array of messages.
     * @param array $messages the current list of messages.
     * @param string|array $new the new messages or messages.
     * @return array the updated array of messages.
     */
    protected function accumulate_messages($messages, $new) {
        if (is_array($new)) {
            $messages = array_merge($messages, $new);
        } else if (is_string($new) && $new) {
            $messages[] = $new;
        }
        return $messages;
    }

    /**
     * Provide a description of the rules that apply to this offlinequiz, such
     * as is shown at the top of the offlinequiz view page. Note that not all
     * rules consider themselves important enough to output a description.
     *
     * @return array an array of description messages which may be empty. It
     *         would be sensible to output each one surrounded by &lt;p> tags.
     */
    public function describe_rules() {
        $result = [];
        foreach ($this->rules as $rule) {
            $result = $this->accumulate_messages($result, $rule->description());
        }
        return $result;
    }

    /**
     * Whether or not a user should be allowed to start a new attempt at this offlinequiz now.
     * If there are any restrictions in force now, return an array of reasons why access
     * should be blocked. If access is OK, return false.
     *
     * @param int $numattempts the number of previous attempts this user has made.
     * @param object|false $lastattempt information about the user's last completed attempt.
     *      if there is not a previous attempt, the false is passed.
     * @return mixed An array of reason why access is not allowed, or an empty array
     *         (== false) if access should be allowed.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        $reasons = [];
        foreach ($this->rules as $rule) {
            $reasons = $this->accumulate_messages($reasons,
                    $rule->prevent_new_attempt($numprevattempts, $lastattempt));
        }
        return $reasons;
    }

    /**
     * Whether the user should be blocked from starting a new attempt or continuing
     * an attempt now. If there are any restrictions in force now, return an array
     * of reasons why access should be blocked. If access is OK, return false.
     *
     * @return mixed An array of reason why access is not allowed, or an empty array
     *         (== false) if access should be allowed.
     */
    public function prevent_access() {
        $reasons = [];
        foreach ($this->rules as $rule) {
            $reasons = $this->accumulate_messages($reasons, $rule->prevent_access());
        }
        return $reasons;
    }

    /**
     * get wether the preflight check is required
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return bool whether a check is required before the user starts/continues
     *      their attempt.
     */
    public function is_preflight_check_required($attemptid) {
        foreach ($this->rules as $rule) {
            if ($rule->is_preflight_check_required($attemptid)) {
                return true;
            }
        }
        return false;
    }


    /**
     * The pre-flight check has passed. This is a chance to record that fact in
     * some way.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     */
    public function notify_preflight_check_passed($attemptid) {
        foreach ($this->rules as $rule) {
            $rule->notify_preflight_check_passed($attemptid);
        }
    }

    /**
     * Inform the rules that the current attempt is finished. This is use, for example
     * by the password rule, to clear the flag in the session.
     */
    public function current_attempt_finished() {
        foreach ($this->rules as $rule) {
            $rule->current_attempt_finished();
        }
    }

    /**
     * Do any of the rules mean that this student will no be allowed any further attempts at this
     * offlinequiz. Used, for example, to change the label by the grade displayed on the view page from
     * 'your current grade is' to 'your final grade is'.
     *
     * @param int $numattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return bool true if there is no way the user will ever be allowed to attempt
     *      this offlinequiz again.
     */
    public function is_finished($numprevattempts, $lastattempt) {
        foreach ($this->rules as $rule) {
            if ($rule->is_finished($numprevattempts, $lastattempt)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sets up the attempt (review or summary) page with any properties required
     * by the access rules.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page) {
        foreach ($this->rules as $rule) {
            $rule->setup_attempt_page($page);
        }
    }

    /**
     * Compute when the attempt must be submitted.
     *
     * @param object $attempt the data from the relevant offlinequiz_attempts row.
     * @return int|false the attempt close time.
     *      False if there is no limit.
     */
    public function get_end_time($attempt) {
        $timeclose = false;
        foreach ($this->rules as $rule) {
            $ruletimeclose = $rule->end_time($attempt);
            if ($ruletimeclose !== false && ($timeclose === false || $ruletimeclose < $timeclose)) {
                $timeclose = $ruletimeclose;
            }
        }
        return $timeclose;
    }

    /**
     * Compute what should be displayed to the user for time remaining in this attempt.
     *
     * @param object $attempt the data from the relevant offlinequiz_attempts row.
     * @param int $timenow the time to consider as 'now'.
     * @return int|false the number of seconds remaining for this attempt.
     *      False if no limit should be displayed.
     */
    public function get_time_left_display($attempt, $timenow) {
        $timeleft = false;
        foreach ($this->rules as $rule) {
            $ruletimeleft = $rule->time_left_display($attempt, $timenow);
            if ($ruletimeleft !== false && ($timeleft === false || $ruletimeleft < $timeleft)) {
                $timeleft = $ruletimeleft;
            }
        }
        return $timeleft;
    }

    /**
     * if this offlinequiz should only be shown to students in a popup window.
     * @return bool
     */
    public function attempt_must_be_in_popup() {
        foreach ($this->rules as $rule) {
            if ($rule->attempt_must_be_in_popup()) {
                return true;
            }
        }
        return false;
    }

    /**
     * if any options that are required for showing the attempt page
     *      in a popup window.
     * @return array
     */
    public function get_popup_options() {
        $options = [];
        foreach ($this->rules as $rule) {
            $options += $rule->get_popup_options();
        }
        return $options;
    }

    /**
     * Send the user back to the offlinequiz view page. Normally this is just a redirect, but
     * If we were in a secure window, we close this window, and reload the view window we came from.
     *
     * This method does not return;
     *
     * @param  $output the offlinequiz renderer.
     * @param string $message optional message to output while redirecting.
     */
    public function back_to_view_page($output, $message = '') {
        if ($this->attempt_must_be_in_popup()) {
            echo $output->close_attempt_popup($this->offlinequizobj->view_url(), $message);
            die();
        } else {
            redirect($this->offlinequizobj->view_url(), $message);
        }
    }

    /**
     * Make some text into a link to review the offlinequiz, if that is appropriate.
     *
     * @param string $linktext some text.
     * @param object $attempt the attempt object
     * @return string some HTML, the $linktext either unmodified or wrapped in a
     *      link to the review page.
     */
    public function make_review_link($attempt, $reviewoptions, $output) {

        // If the attempt is still open, don't link.
        if (in_array($attempt->state, [offlinequiz_attempt::IN_PROGRESS, offlinequiz_attempt::OVERDUE])) {
            return $output->no_review_message('');
        }

        $when = offlinequiz_attempt_state($this->offlinequizobj->get_offlinequiz(), $attempt);
        $reviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz(
                $this->offlinequizobj->get_offlinequiz());

        if (!$reviewoptions->attempt) {
            return $output->no_review_message($this->offlinequizobj->cannot_review_message($when, true));

        } else {
            return $output->review_link($this->offlinequizobj->review_url($attempt->id),
                    $this->attempt_must_be_in_popup(), $this->get_popup_options());
        }
    }
}
