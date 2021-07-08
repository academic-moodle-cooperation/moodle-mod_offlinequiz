<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');

require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');


class mod_offlinequiz_external extends external_api
{
    /**
     * Get definition of the parameters for the get_offlinequizzes_by_courses function
     *
     * @return external_function_parameters
     */
    public static function get_offlinequizzes_by_courses_parameters() {
        return new external_function_parameters(
            [
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids (all enrolled courses if empty array)', VALUE_DEFAULT, []
                ),
            ]
        );
    }

    /**
     * Get definition of the return value of the get_offlinequizzes_by_courses function
     *
     * @return external_single_structure
     */
    public static function get_offlinequizzes_by_courses_returns() {
        return new external_single_structure(
            [
                'offlinequizzes' => new external_multiple_structure(self::offlinequiz_structure(), 'offlinequiz object'),
                'warnings' => new external_warnings('warnings')
            ]
        );
    }

    /** Gets information on offlinequizzes in the provided courses (all courses if no ids are provided).
     *
     * @param $courseids
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_offlinequizzes_by_courses($courseids) {
        $warnings = [];

        $params = self::validate_parameters(self::get_offlinequizzes_by_courses_parameters(), [
            'courseids' => $courseids,
        ]);

        $mycourses = [];
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }
        $returnedquizzes = [];
        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            $offlinequiz_instances = get_all_instances_in_courses("offlinequiz", $courses);
            foreach ($offlinequiz_instances as $offlinequiz_instance) {
                $context = context_module::instance($offlinequiz_instance->coursemodule);
                $offlinequiz = self::get_offlinequiz_record($offlinequiz_instance->id);
                $returnedquizzes[] = self::export_offlinequiz($offlinequiz, $context);
            }
        }
        $result = new stdClass();
        $result->offlinequizzes = $returnedquizzes;
        $result->warnings = $warnings;
        return $result;
    }

    /**
     * Get definition of the parameters for the get_offlinequiz function
     *
     * @return external_function_parameters
     */
    public static function get_offlinequiz_parameters() {
        return new external_function_parameters(
            [
                'offlinequizid' => new external_value(PARAM_INT, 'offlinequiz id'),
            ]
        );
    }

    /**
     * Get definition of the return value of the get_offlinequiz function
     *
     * @return external_single_structure
     */
    public static function get_offlinequiz_returns() {
        return new external_single_structure(
            [
                'offlinequiz' => self::offlinequiz_structure(),
            ]
        );
    }

    /** Gets information on offlinequizzes in the provided courses (all courses if no ids are provided).
     *
     * @param $courseids
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_offlinequiz($offlinequizid) {
        $warnings = [];

        $params = self::validate_parameters(self::get_offlinequiz_parameters(), [
            'offlinequizid' => $offlinequizid,
        ]);

        $cm = get_coursemodule_from_instance('offlinequiz', $params['offlinequizid'], 0, false, MUST_EXIST);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $result = new stdClass();
        $result->offlinequiz = self::export_offlinequiz(self::get_offlinequiz_record($cm->instance), $context);
        $result->warnings = $warnings;
        return $result;
    }

    /**
     * Get definition of the parameters for the get_attempt_review function
     *
     * @return external_function_parameters
     */
    public static function get_attempt_review_parameters() {
        return new external_function_parameters(
            [
                'offlinequizid' => new external_value(PARAM_INT, 'offlinequiz id'),
            ]
        );
    }

    /**
     * Get definition of the return value of the get_attempt_review function
     *
     * @return external_single_structure
     */
    public static function get_attempt_review_returns() {
        return new external_single_structure(
            [
                'offlinequiz' => self::offlinequiz_structure(),
                'grade' => new external_value(PARAM_RAW, 'grade for the quiz (or empty or "notyetgraded")', VALUE_OPTIONAL),
                'maxgrade' => new external_value(PARAM_RAW, 'maxgrade for the quiz ', VALUE_OPTIONAL),
                'rawgrade' => new external_value(PARAM_RAW, 'grade for the quiz (or empty or "notyetgraded")', VALUE_OPTIONAL),
                'rawmaxgrade' => new external_value(PARAM_RAW, 'grade for the quiz (or empty or "notyetgraded")', VALUE_OPTIONAL),
                'questions' => new external_multiple_structure(self::question_structure(), 'question object'),
            ]
        );
    }

    /**
     * Gets the current user's review for the offlinequiz with the specified id.
     *
     * @param $offlinequizid
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_attempt_review($offlinequizid) {
        global $USER;

        $res = new stdClass();
        $params = self::validate_parameters(self::get_attempt_review_parameters(), [
            'offlinequizid' => $offlinequizid
        ]);

        $offlinequiz = self::get_offlinequiz_record($params['offlinequizid']);
        if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course)) {
            print_error("The course module for the offlinequiz with id $offlinequiz->id is missing");
        }
        $context = context_module::instance($cm->id);
        if ($results = offlinequiz_get_user_results($offlinequiz->id, $USER->id)
            and offlinequiz_results_open($offlinequiz)) {
            //there should be only one result
            if (count($results) > 1) {
                print_error("More than one result is found for user.");
            }
            foreach ($results as $id => $result) {
                $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
                $group = self::get_group($result->offlinegroupid);
                if (!$quba = question_engine::load_questions_usage_by_activity($result->usageid)) {
                    print_error("question usage with id $result->usageid not found");
                }
                $res->offlinequiz = self::export_offlinequiz($offlinequiz, $context);

                if ($options->marks > 0) {
                    $res->maxgrade = $offlinequiz->grade;
                    $res->rawmaxgrade = $group->sumgrades;
                }
                if ($options->marks > 1) {
                    $res->grade = offlinequiz_rescale_grade($result->sumgrades, $offlinequiz, $group, false);
                    $res->rawgrade = $result->sumgrades;
                }
                $res->questions = self::get_attempt_questions_data($quba, $options);
            }
        } else {
            print_error("Review currently inaccessible.");
        }

        return $res;
    }

    /**
     * Gets information on each question of attempt.
     *
     * @param question_usage_by_activity $quba
     * @param $displayoptions
     * @return array
     */
    private static function get_attempt_questions_data(question_usage_by_activity $quba, $displayoptions) {
        $questions = [];
        $slots = $quba->get_slots();
        $number = 1;
        foreach ($slots as $id => $slot) {
            $qattempt = $quba->get_question_attempt($slot);
            $questiondef = $qattempt->get_question(true);
            $qtype = $questiondef->get_type_name();


            $question = [
                'slot' => $slot,
                'type' => $qtype,
                'sequencecheck' => $qattempt->get_sequence_check_count(),
                'lastactiontime' => $qattempt->get_last_step()->get_timecreated(),
                'hasautosavedstep' => $qattempt->has_autosaved_step(),
                'settings' => !empty($settings) ? json_encode($settings) : null,
            ];

            if ($questiondef->length) {
                $question['number'] = $number;
                $number += $questiondef->length;
                $showcorrectness = $displayoptions->correctness && $qattempt->has_marks();
                if ($showcorrectness) {
                    $question['state'] = (string)$quba->get_question_state($slot);
                }
                $question['status'] = $quba->get_question_state_string($slot, $displayoptions->correctness);
            }
            if ($displayoptions->marks >= question_display_options::MAX_ONLY) {
                $question['maxmark'] = $qattempt->get_max_mark();
            }
            if ($displayoptions->marks >= question_display_options::MARK_AND_MAX) {
                $question['mark'] = $quba->get_question_mark($slot);
            }

            $questions[] = $question;
        }
        return $questions;
    }

    /**
     * Gets the group object with the provided id from the database.
     *
     * @param $groupid
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    private static function get_group($groupid) {
        global $DB;
        if (!$group = $DB->get_record("offlinequiz_groups", ['id' => $groupid])) {
            print_error("The offlinequiz group with $groupid is missing");
        }
        return $group;
    }

    /**
     * Gets the offlinequiz object with the provided id from the database.
     *
     * @param $id
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    private static function get_offlinequiz_record($id) {
        global $DB;
        if (!$offlinequiz = $DB->get_record('offlinequiz', ['id' => $id])) {
            print_error('invalidofflinequizid', 'offlinequiz');
        }
        return $offlinequiz;
    }

    /**
     * Gets structure for an offlinequiz. Used for return value definitions.
     *
     * @return external_single_structure
     */
    private static function offlinequiz_structure() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'offlinequiz id'),
                'course' => new external_value(PARAM_INT, 'course id the offlinequiz belongs to'),
                'name' => new external_value(PARAM_TEXT, 'offlinequiz name'),
                'intro' => new external_value(PARAM_RAW, 'Quiz introduction text.', VALUE_OPTIONAL),
                'introformat' => new external_format_value('intro', VALUE_OPTIONAL),
                'introfiles' => new external_files('Files in the introduction text', VALUE_OPTIONAL),
                'time' => new external_value(PARAM_INT, 'Time of the quiz', VALUE_OPTIONAL),
                'resultsavailable' => new external_value(PARAM_INT, 'whether allowed to view results and results exist', VALUE_OPTIONAL)
            ], 'example information'
        );
    }

    /**
     * Gets an array with relevant offlinequiz information.
     *
     * @param $offlinequiz
     * @param $context
     * @return object
     * @throws coding_exception
     */
    private static function export_offlinequiz($offlinequiz, $context) {
        global $USER;
        $quizdetails = new stdClass();
        $quizdetails->id = $offlinequiz->id;
        $quizdetails->course = $offlinequiz->course;
        $quizdetails->name = external_format_string($offlinequiz->name, $context->id);
        if (has_capability('mod/offlinequiz:view', $context)) {
            // Format intro.
            $options = ['noclean' => true];
            list($quizdetails->intro, $quizdetails->introformat) =
                external_format_text($offlinequiz->intro, $offlinequiz->introformat, $context->id, 'mod_quiz', 'intro', null, $options);
            $quizdetails->introfiles = external_util::get_area_files($context->id, 'mod_quiz', 'intro', false, false);
            if ($offlinequiz->time) {
                $quizdetails->time = $offlinequiz->time;
            }
            $quizdetails->resultsavailable = intval(offlinequiz_get_user_results($offlinequiz->id, $USER->id)
                and offlinequiz_results_open($offlinequiz));
        }
        return $quizdetails;
    }

    /**
     * Describes a single question structure. Used for return value definition.
     *
     * @return external_single_structure the question data. Some fields may not be returned depending on the offlinequiz display settings.
     */
    private static function question_structure() {
        return new external_single_structure(
            [
                'slot' => new external_value(PARAM_INT, 'slot number'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'question type, i.e: multichoice'),
                'sequencecheck' => new external_value(PARAM_INT, 'the number of real steps in this attempt', VALUE_OPTIONAL),
                'lastactiontime' => new external_value(PARAM_INT, 'the timestamp of the most recent step in this question attempt',
                    VALUE_OPTIONAL),
                'hasautosavedstep' => new external_value(PARAM_BOOL, 'whether this question attempt has autosaved data',
                    VALUE_OPTIONAL),
                'state' => new external_value(PARAM_ALPHA, 'the state where the question is in.
                    It will not be returned if the user cannot see it due to the quiz display correctness settings.',
                    VALUE_OPTIONAL),
                'status' => new external_value(PARAM_RAW, 'current formatted state of the question', VALUE_OPTIONAL),
                'mark' => new external_value(PARAM_RAW, 'the mark awarded.
                    It will be returned only if the user is allowed to see it.', VALUE_OPTIONAL),
                'maxmark' => new external_value(PARAM_FLOAT, 'the maximum mark possible for this question attempt.
                    It will be returned only if the user is allowed to see it.', VALUE_OPTIONAL),
                'settings' => new external_value(PARAM_RAW, 'Question settings (JSON encoded).', VALUE_OPTIONAL),
            ], 'The question data. Some fields may not be returned depending on the quiz display settings.'
        );
    }

}
