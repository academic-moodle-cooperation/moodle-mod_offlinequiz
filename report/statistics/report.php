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
 * Offlinequiz statistics report class.
 *
 * @package   offlinequiz_statistics
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/statistics_form.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/statistics_table.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/statistics_question_table.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/statistics_question_answer_table.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/qstats.php');
require_once($CFG->dirroot . '/mod/offlinequiz/report/statistics/responseanalysis.php');

/**
 * The offlinequiz statistics report provides summary information about each question in
 * a offlinequiz, compared to the whole offlinequiz. It also provides a drill-down to more
 * detailed information about each question.
 *
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_statistics_report extends offlinequiz_default_report {
    /** @var integer Time after which statistics are automatically recomputed. */
    const TIME_TO_CACHE_STATS = 900; // 15 minutes.

    /** @var object instance of table class used for main questions stats table. */
    protected $table;

    /**
     * Display the report.
     */
    public function display($offlinequiz, $cm, $course) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        $this->context = context_module::instance($cm->id);

        // Work out the display options.
        $download = optional_param('download', '', PARAM_ALPHA);
        $everything = optional_param('everything', 0, PARAM_BOOL);
        $recalculate = optional_param('recalculate', 0, PARAM_BOOL);
        // A qid paramter indicates we should display the detailed analysis of a question and subquestions.
        $qid = optional_param('qid', 0, PARAM_INT);
        $questionid = optional_param('questionid', 0, PARAM_INT);
        // Determine statistics mode.
        $statmode = optional_param('statmode', 'statsoverview', PARAM_ALPHA);

        $pageoptions = array();
        $pageoptions['id'] = $cm->id;
        $pageoptions['mode'] = 'statistics';
        $pageoptions['statmode'] = $statmode;

        // When showing big tables add the JavaScript for the double scrollbar.
        if ($statmode == 'questionstats' || $statmode == 'questionandanswerstats') {
            $module = array(
                    'name'      => 'mod_offlinequiz_statistics',
                    'fullpath'  => '/mod/offlinequiz/report/statistics/doublescroll.js',
                    'requires'  => array(),
                    'strings'   => array(),
                    'async'     => false,
            );

            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('doubleScroll', 'mod_offlinequiz');
            $PAGE->requires->js_init_call('offlinequiz_statistics_init_doublescroll', null, false, $module);
        }

        if (!$groups = $DB->get_records('offlinequiz_groups',
                array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups)) {
            print_error('nogroups', 'offlinequiz', $CFG->wwwroot . '/course/view.php?id=' .
                $COURSE->id, $scannedpage->offlinequizid);
        }

        // Determine groupid.
        $groupnumber = optional_param('offlinegroup', -1, PARAM_INT);
        if ($groupnumber === -1 and !empty($SESSION->question_pagevars['groupnumber'])) {
            $groupnumber = $SESSION->question_pagevars['groupnumber'];
        }

        if ($groupnumber > 0) {
            $pageoptions['offlinegroup'] = $groupnumber;
            $offlinequiz->groupnumber = $groupnumber;
            $offlinequiz->sumgrades = $DB->get_field('offlinequiz_groups', 'sumgrades',
                    array('offlinequizid' => $offlinequiz->id, 'number' => $groupnumber));

            if ($offlinegroup = offlinequiz_get_group($offlinequiz, $groupnumber)) {
                $offlinequiz->groupid = $offlinegroup->id;
                $groupquestions = offlinequiz_get_group_question_ids($offlinequiz);
                $offlinequiz->questions = $groupquestions;
            } else {
                print_error('invalidgroupnumber', 'offlinequiz');
            }
        } else {
            $offlinequiz->groupid = 0;
            // The user wants to evaluate results from all offlinequiz groups.
            // Compare the sumgrades of all offlinequiz groups. First we put all sumgrades in an array.
            $sumgrades = array();
            foreach ($groups as $group) {
                $sumgrades[] = round($group->sumgrades, $offlinequiz->decimalpoints);
            }
            // Now we remove duplicates.
            $sumgrades = array_unique($sumgrades);

            if (count($sumgrades) > 1) {
                // If the groups have different sumgrades, we can't pick one.
                $offlinequiz->sumgrades = -1;
            } else if (count($sumgrades) == 1) {
                // If the groups all have the same sumgrades, we pick the first one.
                $offlinequiz->sumgrades = $sumgrades[0];
            } else {
                // Pathological, there are no sumgrades, i.e. no groups...
                $offlinequiz->sumgrades = 0;
            }

            // If no group has been chosen we simply take the questions from the question instances.
            $sql = "SELECT DISTINCT(questionid)
                      FROM {offlinequiz_group_questions}
                     WHERE offlinequizid = :offlinequizid";

            $questionids = $DB->get_fieldset_sql($sql, array('offlinequizid' => $offlinequiz->id));
            $offlinequiz->questions = $questionids;
        }

        // We warn the user if the different offlinequiz groups have different sets of questions.
        $differentquestions = false;
        if ($offlinequiz->groupid == 0 && count($groups) > 1 &&
                $this->groups_have_different_questions($offlinequiz, $groups)) {
            $differentquestions = true;
        }

        $reporturl = new moodle_url('/mod/offlinequiz/report.php', $pageoptions);

        $useallattempts = 0;

        // Find out current groups mode.
        $currentgroup = $this->get_current_group($cm, $course, $this->context);
        $nostudentsingroup = false; // True if a group is selected and there is no one in it.
        if (empty($currentgroup)) {
            $currentgroup = 0;
            $groupstudents = array();

        } else if ($currentgroup == self::NO_GROUPS_ALLOWED) {
            $groupstudents = array();
            $nostudentsingroup = true;

        } else {
            // All users who can attempt offlinequizzes and who are in the currently selected group.
            $groupstudents = get_users_by_capability($this->context,
                    array('mod/offlinequiz:reviewmyattempts', 'mod/offlinequiz:attempt'),
                    '', '', '', '', $currentgroup, '', false);
            if (!$groupstudents) {
                $nostudentsingroup = true;
            }
        }

        // If recalculate was requested, handle that.
        if ($recalculate && confirm_sesskey()) {
            $this->clear_cached_data($offlinequiz->id, $currentgroup, $useallattempts, $offlinequiz->groupid);
            redirect($reporturl);
        }

        // Set up the main table.
        if ($statmode == 'statsoverview' || $statmode == 'questionstats') {
            $this->table = new offlinequiz_statistics_table();
        } else {
            $this->table = new offlinequiz_question_answer_statistics_table();
        }
        if ($everything) {
            $report = get_string('completestatsfilename', 'offlinequiz_statistics');
        } else {
            $report = get_string('questionstatsfilename', 'offlinequiz_statistics');
        }
        $courseshortname = format_string($course->shortname, true,
                array('context' => context_course::instance($course->id)));
        $filename = offlinequiz_report_download_filename($report, $courseshortname, $offlinequiz->name);
        $this->table->is_downloading($download, $filename,
                get_string('offlinequizstructureanalysis', 'offlinequiz_statistics'));

        // Load the questions.
        // NOTE: function is hacked to deliver question array with question IDs as keys, not the slot as before.
        $questions = offlinequiz_report_get_significant_questions($offlinequiz);

        $questionids = array_keys($questions);
        $fullquestions = question_load_questions($questionids);

        foreach ($questions as $quid => $question) {
            $q = $fullquestions[$quid];
            $q->maxmark = $question->maxmark;
            $q->number = $question->number;
            $questions[$quid] = $q;
        }

        // Get the data to be displayed.
        list($offlinequizstats, $questions, $subquestions, $s) =
                $this->get_offlinequiz_and_questions_stats($offlinequiz, $currentgroup,
                        $nostudentsingroup, $useallattempts, $groupstudents, $questions);

        $offlinequizinfo = $this->get_formatted_offlinequiz_info_data($course, $cm, $offlinequiz, $offlinequizstats);

        // Set up the table, if there is data.
        if ($s) {
            $this->table->statistics_setup($offlinequiz, $cm->id, $reporturl, $s);
        }
        // Print the page header stuff (if not downloading.
        if (!$this->table->is_downloading()) {
            $this->print_header_and_tabs($cm, $course, $offlinequiz, $statmode, 'statistics');

            // Options for the help text popup_action.
            $options = array('width' => 650,
                    'height' => 400,
                    'resizable' => false,
                    'top' => 0,
                    'left' => 0,
                    'menubar' => false,
                    'location' => false,
                    'scrollbars' => true,
                    'toolbar' => false,
                    'status' => false,
                    'directories' => false,
                    'fullscreen' => false,
                    'dependent' => false);

            $helpfilename = 'statistics_help_';
            if (current_language() == 'de') {
                $helpfilename .= 'de.html';
            } else {
                $helpfilename .= 'en.html';
            }
            $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report/statistics/help/' . $helpfilename);
            $pixicon = new pix_icon('help', get_string('statisticshelp', 'offlinequiz_statistics'));
            $helpaction = $OUTPUT->action_icon($url, $pixicon, new popup_action('click', $url, 'help123', $options));

            echo $OUTPUT->box_start('linkbox');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
            echo $OUTPUT->heading(get_string($statmode . 'header', 'offlinequiz_statistics') . $helpaction);
            echo $OUTPUT->box_end();

            if (!$questionid) {
                $this->print_offlinequiz_group_selector($cm, $groups, $groupnumber, $pageoptions);
                if ($statmode == 'statsoverview' && ($offlinequiz->sumgrades == -1 || $differentquestions)) {
                	echo $OUTPUT->box_start();
                	$notificationmessage = get_string('remarks', 'offlinequiz_statistics') . ":<br />";
                    if ($offlinequiz->sumgrades == -1) {
                    	$notificationmessage .= '- ' . get_string('differentsumgrades', 'offlinequiz_statistics',
                                implode(', ', $sumgrades)) . "<br />";
                    }
                    if ($differentquestions) {
                    	$notificationmessage .= '- ' . get_string('differentquestions', 'offlinequiz_statistics',
                                implode(', ', $sumgrades));
                    }
                    echo $OUTPUT->notification($notificationmessage, 'notifynote');
                    echo $OUTPUT->box_end();
                    
                }
            }

            if (groups_get_activity_groupmode($cm)) {
                groups_print_activity_menu($cm, $reporturl->out());
                if ($currentgroup && !$groupstudents) {
                    echo $OUTPUT->notification(get_string('nostudentsingroup', 'offlinequiz_statistics'));
                }
            }

            if (!$offlinequiz->questions) {
                echo offlinequiz_no_questions_message($offlinequiz, $cm, $this->context);
            } else if (!$this->table->is_downloading() && $s == 0) {
                echo $OUTPUT->box_start('linkbox');
                echo $OUTPUT->notification(get_string('noattempts', 'offlinequiz'), 'notifyproblem');
                echo $OUTPUT->box_end();
                echo '<br/>';
            }
        }

        if ($everything) { // Implies is downloading.
            // Overall report, then the analysis of each question.
            if ($statmode == 'statsoverview') {
                $this->download_offlinequiz_info_table($offlinequizinfo);
            } else if ($statmode == 'questionstats') {

                if ($s) {
                    $this->output_offlinequiz_structure_analysis_table($s, $questions, $subquestions);

                    if ($this->table->is_downloading() == 'xhtml') {
                        $this->output_statistics_graph($offlinequizstats->id, $s);
                    }

//                     foreach ($questions as $question) {
//                         if (question_bank::get_qtype(
//                                 $question->qtype, false)->can_analyse_responses()) {
//                             $this->output_individual_question_response_analysis(
//                                     $question, $reporturl, $offlinequizstats);

//                         } else if (!empty($question->_stats->subquestions)) {
//                             $subitemstodisplay = explode(',', $question->_stats->subquestions);
//                             foreach ($subitemstodisplay as $subitemid) {
//                                 $this->output_individual_question_response_analysis(
//                                         $subquestions[$subitemid], $reporturl, $offlinequizstats);
//                             }
//                         }
//                     }
                }
            } else if ($statmode == 'questionandanswerstats') {
                if ($s) {
                    $this->output_offlinequiz_structure_analysis_table($s, $questions, $subquestions);

                    if ($this->table->is_downloading() == 'xhtml') {
                        $this->output_statistics_graph($offlinequizstats->id, $s);
                    }

                    foreach ($questions as $question) {
                        if (question_bank::get_qtype(
                                $question->qtype, false)->can_analyse_responses()) {
                            $this->output_individual_question_response_analysis(
                                    $question, $reporturl, $offlinequizstats);

                        } else if (!empty($question->_stats->subquestions)) {
                            $subitemstodisplay = explode(',', $question->_stats->subquestions);
                            foreach ($subitemstodisplay as $subitemid) {
                                $this->output_individual_question_response_analysis(
                                        $subquestions[$subitemid], $reporturl, $offlinequizstats);
                            }
                        }
                    }
                }
            }

            $this->table->export_class_instance()->finish_document();

        } else if ($questionid) {
            // Report on an individual question indexed by position.
            if (!isset($questions[$questionid])) {
                print_error('questiondoesnotexist', 'question');
            }

            $this->output_individual_question_data($offlinequiz, $questions[$questionid]);
            $this->output_individual_question_response_analysis(
                    $questions[$questionid], $reporturl, $offlinequizstats);

            // Back to overview link.
            echo $OUTPUT->box('<a href="' . $reporturl->out() . '">' .
                    get_string('backtoquestionsandanswers', 'offlinequiz_statistics') . '</a>',
                    'backtomainstats boxaligncenter backlinkbox generalbox boxwidthnormal mdl-align');

        } else if ($qid) {
            // Report on an individual sub-question indexed questionid.
            if (!isset($subquestions[$qid])) {
                print_error('questiondoesnotexist', 'question');
            }

            $this->output_individual_question_data($offlinequiz, $subquestions[$qid]);
            $this->output_individual_question_response_analysis(
                    $subquestions[$qid], $reporturl, $offlinequizstats);

            // Back to overview link.
            echo $OUTPUT->box('<a href="' . $reporturl->out() . '">' .
                    get_string('backtoquestionsandanswers', 'offlinequiz_statistics') . '</a>',
                    'boxaligncenter backlinkbox generalbox boxwidthnormal mdl-align');

        } else {
            // On-screen display of overview report.
            echo $this->output_caching_info($offlinequizstats, $offlinequiz->id, $currentgroup,
                    $groupstudents, $useallattempts, $reporturl, $offlinequiz->groupid);

            if ($statmode == 'statsoverview') {
            	echo html_writer::start_div("downloadoptions");
            	echo $this->everything_download_options();
               	echo html_writer::end_div();
            	echo '<br/><center>';
                echo $this->output_offlinequiz_info_table($offlinequizinfo);
                echo '</center>';
            } else if ($statmode == 'questionstats') {
                if ($s) {
                    echo '<br/>';
                    $this->output_offlinequiz_structure_analysis_table($s, $questions, $subquestions);
                }
            } else if ($statmode == 'questionandanswerstats') {
                if ($s) {
                    echo '<br/>';
                    $this->output_offlinequiz_question_answer_table($s, $questions, $subquestions, $offlinequizstats);
                }
            }
        }
    }


    /**
     * Checks whether the different offlinequiz groups have different sets of questions (order is irrelevant).
     *
     * @param unknown_type $offlinequiz
     * @param unknown_type $groups
     * @return boolean
     */
    private function groups_have_different_questions($offlinequiz, $groups) {
        $agroup = array_pop($groups);
        $aquestions = offlinequiz_get_group_question_ids($offlinequiz, $agroup->id);

        // Compare all other groups to the first one.
        foreach ($groups as $bgroup) {
            $bquestions = offlinequiz_get_group_question_ids($offlinequiz, $bgroup->id);
            // Check which questions are in group A but not in group B.
            $diff1 = array_diff($aquestions, $bquestions);
            // Check which questions are in group B but not in group A.
            $diff2 = array_diff($bquestions, $aquestions);
            // Return true if there are any differences.
            if (!empty($diff1) || !empty($diff2)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param unknown_type $cm The course module, needed to construct the base URL
     * @param unknown_type $groups The group objects as read from the database
     * @param unknown_type $groupnumber The currently chosen group number
     */
    private function print_offlinequiz_group_selector($cm, $groups, $groupnumber, $pageoptions) {
        global $CFG, $OUTPUT;

        $options = array();
        $letterstr = 'ABCDEFGH';
        $prefix = get_string('statisticsforgroup', 'offlinequiz_statistics');
        foreach ($groups as $group) {
            $options[$group->number] = $prefix . ' ' . $letterstr[$group->number - 1];
        }
        $urlparams = array('id' => $cm->id, 'mode' => 'statistics', 'statmode' => $pageoptions['statmode']);
        if (key_exists('offlinegroup', $pageoptions)) {
            $urlparams['offlinegroup'] = $pageoptions['offlinegroup'];
        }

        $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php', $urlparams);
        echo $OUTPUT->single_select($url, 'offlinegroup', $options, $groupnumber,
                array(0 => get_string('allgroups', 'offlinequiz_statistics')));
    }


    /**
     * Display the statistical and introductory information about a question.
     * Only called when not downloading.
     * @param object $offlinequiz the offlinequiz settings.
     * @param object $question the question to report on.
     * @param moodle_url $reporturl the URL to resisplay this report.
     * @param object $offlinequizstats Holds the offlinequiz statistics.
     */
    protected function output_individual_question_data($offlinequiz, $question) {
        global $OUTPUT;

        // On-screen display. Show a summary of the question's place in the offlinequiz,
        // and the question statistics.
        $datumfromtable = $this->table->format_row($question);

        echo '<strong>';
        echo $question->name . '&nbsp;&nbsp;&nbsp;' . $datumfromtable['actions'] . '&nbsp;&nbsp;&nbsp;';
        echo '</strong>';
        echo $datumfromtable['icon'] . '&nbsp;' .
                question_bank::get_qtype($question->qtype, false)->menu_name() . '&nbsp;' .
                $datumfromtable['icon'] . '<br/>';
        echo $this->render_question_text_plain($question);

        // Set up the question statistics table.
        $questionstatstable = new html_table();
        $questionstatstable->id = 'questionstatstable';
        $questionstatstable->align = array('left', 'right');
        $questionstatstable->attributes['class'] = 'generaltable titlesleft';

        unset($datumfromtable['number']);
        unset($datumfromtable['icon']);
        $actions = $datumfromtable['actions'];
        unset($datumfromtable['actions']);
        unset($datumfromtable['name']);
        unset($datumfromtable['response']);
        unset($datumfromtable['frequency']);
        unset($datumfromtable['count']);
        unset($datumfromtable['fraction']);

        $labels = array(
            's' => get_string('attempts', 'offlinequiz_statistics'),
            'facility' => get_string('facility', 'offlinequiz_statistics'),
            'sd' => get_string('standarddeviationq', 'offlinequiz_statistics'),
            'random_guess_score' => get_string('random_guess_score', 'offlinequiz_statistics'),
            'intended_weight' => get_string('intended_weight', 'offlinequiz_statistics'),
            'effective_weight' => get_string('effective_weight', 'offlinequiz_statistics'),
            'discrimination_index' => get_string('discrimination_index', 'offlinequiz_statistics'),
            'discriminative_efficiency' => get_string('discriminative_efficiency', 'offlinequiz_statistics'),
            'correct' => get_string('correct', 'offlinequiz_statistics'),
            'partially' => get_string('partially', 'offlinequiz_statistics'),
            'wrong' => get_string('wrong', 'offlinequiz_statistics'),
        );
        foreach ($datumfromtable as $item => $value) {
            $questionstatstable->data[] = array($labels[$item], $value);
        }

        // Display the various bits.
        echo '<br/>';
        echo '<center>';
        echo html_writer::table($questionstatstable);
        echo '</center>';
    }

    /**
     * @param object $question question data.
     * @return string HTML of question text, ready for display.
     */
    protected function render_question_text($question) {
        global $OUTPUT;

        $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                $question->contextid, 'question', 'questiontext', $question->id,
                $this->context->id, 'quiz_statistics');

        return $OUTPUT->box(format_text($text, $question->questiontextformat,
                array('noclean' => true, 'para' => false, 'overflowdiv' => true)),
                'questiontext boxaligncenter generalbox boxwidthnormal mdl-align');
    }

    /**
     * @param object $question question data.
     * @return string HTML of question text, ready for display.
     */
    protected function render_question_text_plain($question, $showimages = true) {
        global $OUTPUT;

        if ($showimages) {
            $text = question_rewrite_question_preview_urls($question->questiontext, $question->id,
                    $question->contextid, 'question', 'questiontext', $question->id,
                    $this->context->id, 'quiz_statistics');
        } else {
            $text = $question->questiontext;
        }
        $questiontext = question_utils::to_plain_text($text, $question->questiontextformat,
                array('noclean' => true, 'para' => false, 'overflowdiv' => true));
        return '&nbsp;&nbsp;&nbsp;' . $questiontext;
    }


    /**
     * Display the response analysis for a question.
     * @param object $question the question to report on.
     * @param moodle_url $reporturl the URL to resisplay this report.
     * @param object $offlinequizstats Holds the offlinequiz statistics.
     */
    protected function output_individual_question_response_analysis($question,
            $reporturl, $offlinequizstats) {
        global $OUTPUT;

        if (!question_bank::get_qtype($question->qtype, false)->can_analyse_responses()) {
            return;
        }

        $qtable = new offlinequiz_statistics_question_table($question->id);
        $qtable->set_attribute('id', 'statisticsquestiontable');

        $exportclass = $this->table->export_class_instance();
        $qtable->export_class_instance($exportclass);
        if (!$this->table->is_downloading()) {
            // Output an appropriate title.
            echo $OUTPUT->heading(get_string('analysisofresponses', 'offlinequiz_statistics'));
            echo $this->render_question_text_plain($question, false);
            echo '<br/>';

        } else {
            // Work out an appropriate title.
            $questiontabletitle = '"' . $question->name . '"';
            if (!empty($question->number)) {
                $questiontabletitle = '(' . $question->number . ') ' . $questiontabletitle;
            }
            if ($this->table->is_downloading() == 'xhtml') {
                $questiontabletitle = get_string('analysisofresponsesfor',
                        'offlinequiz_statistics', $questiontabletitle);
            }

            // Set up the table.
            $exportclass->start_table($questiontabletitle);

            if ($this->table->is_downloading() == 'xhtml') {
                echo $this->render_question_text($question);
            }
        }

        $responesstats = new offlinequiz_statistics_response_analyser($question);
        $responesstats->load_cached($offlinequizstats->id);

        $qtable->question_setup($reporturl, $question, $responesstats);
        if ($this->table->is_downloading()) {
            $exportclass->output_headers($qtable->headers);
        }
        $letterstr = 'abcdefghijklmnopqrstuvwxyz';
        $counter = 0;
        foreach ($responesstats->responseclasses as $partid => $partclasses) {
            $rowdata = new stdClass();
            foreach ($partclasses as $responseclassid => $responseclass) {
                $rowdata->responseclass = $responseclass->responseclass;

                $responsesdata = $responesstats->responses[$partid][$responseclassid];
                if (empty($responsesdata)) {
                    if ($responseclass->responseclass != get_string('noresponse', 'question')) {
                        $rowdata->part = $letterstr[$counter++] . ')';
                    } else {
                        $rowdata->part = '';
                    }

                    if (!array_key_exists('responseclass', $qtable->columns)) {
                        $rowdata->response = $responseclass->responseclass;
                    } else {
                        $rowdata->response = '';
                    }
                    $rowdata->fraction = $responseclass->fraction;
                    $rowdata->count = 0;
                    $classname = '';
                    if ($rowdata->fraction > 0) {
                        $classname = 'greenrow';
                    } else if ($rowdata->fraction < 0) {
                        $classname = 'redrow';
                    }
                    $qtable->add_data_keyed($qtable->format_row($rowdata), $classname);
                    continue;
                }

                foreach ($responsesdata as $response => $data) {
                    if ($response != get_string('noresponse', 'question')) {
                        $rowdata->part = $letterstr[$counter++] . ')';
                    } else {
                        $rowdata->part = '';
                    }
                    $rowdata->response = $response;
                    $rowdata->fraction = $data->fraction;
                    $rowdata->count = $data->count;
                    $classname = '';
                    if ($rowdata->fraction > 0) {
                        $classname = 'greenrow';
                    } else if ($rowdata->fraction < 0) {
                        $classname = 'redrow';
                    }
                    $qtable->add_data_keyed($qtable->format_row($rowdata), $classname);
                    break;
                }
            }
        }

        $qtable->finish_output(!$this->table->is_downloading());
    }

    /**
     * Output the table that lists all the questions in the offlinequiz with their statistics.
     * @param int $s number of attempts.
     * @param array $questions the questions in the offlinequiz.
     * @param array $subquestions the subquestions of any random questions.
     */
    protected function output_offlinequiz_structure_analysis_table($s, $questions, $subquestions) {
        if (!$s) {
            return;
        }

        foreach ($questions as $question) {
            // Output the data for this question.
            $this->table->add_data_keyed($this->table->format_row($question));

            if (empty($question->_stats->subquestions)) {
                continue;
            }

            // And its subquestions, if it has any.
            $subitemstodisplay = explode(',', $question->_stats->subquestions);
            foreach ($subitemstodisplay as $subitemid) {
                $subquestions[$subitemid]->maxmark = $question->maxmark;
                $this->table->add_data_keyed($this->table->format_row($subquestions[$subitemid]));
            }
        }
        $this->table->finish_output(!$this->table->is_downloading());
    }

    protected function get_formatted_offlinequiz_info_data($course, $cm, $offlinequiz, $offlinequizstats) {
        // You can edit this array to control which statistics are displayed.
        $todisplay = array( // Comment in 'firstattemptscount' => 'number'.
                    'allattemptscount' => 'number',
                    'maxgrade' => 'number_format',
                    'bestgrade' => 'scale_to_maxgrade',
                    'worstgrade' => 'scale_to_maxgrade',
                    'allattemptsavg' => 'scale_to_maxgrade',
                    'median' => 'scale_to_maxgrade',
                    'standarddeviation' => 'scale_to_maxgrade', // The 'summarks_as_percentage'.
                    'skewness' => 'number_format',
                    'kurtosis' => 'number_format',
                    'cic' => 'percent_to_number_format',
                    'errorratio' => 'number_format_percent',
                    'standarderror' => 'scale_to_maxgrade');

        if ($offlinequiz->sumgrades > 0) {
            $offlinequizstats->sumgrades = $offlinequiz->sumgrades;
        } else if ($offlinequiz->sumgrades == -1) {
            $offlinequizstats->sumgrades = '';
            $offlinequizstats->bestgrade = '';
            $offlinequizstats->worstgrade = '';
            $offlinequizstats->allattemptsavg = '';
            $offlinequizstats->median = '';
            $offlinequizstats->standarddeviation = '';
        }
        $offlinequizstats->maxgrade = $offlinequiz->grade;

        // General information about the offlinequiz.
        $offlinequizinfo = array();
        $offlinequizinfo[get_string('offlinequizname', 'offlinequiz_statistics')] = format_string($offlinequiz->name);

        if ($cm->idnumber) {
            $offlinequizinfo[get_string('idnumbermod')] = $cm->idnumber;
        }
        if ($offlinequiz->timeopen) {
            $offlinequizinfo[get_string('reviewopens', 'offlinequiz')] = userdate($offlinequiz->timeopen);
        }
        if ($offlinequiz->timeclose) {
            $offlinequizinfo[get_string('reviewcloses', 'offlinequiz')] = userdate($offlinequiz->timeclose);
        }
        if ($offlinequiz->timeopen && $offlinequiz->timeclose) {
            $offlinequizinfo[get_string('duration', 'offlinequiz_statistics')] =
                    format_time($offlinequiz->timeclose - $offlinequiz->timeopen);
        }
        // The statistics.
        foreach ($todisplay as $property => $format) {
            if (!isset($offlinequizstats->$property) || empty($format)) {
                continue;
            }
            $value = $offlinequizstats->$property;

            switch ($format) {
                case 'summarks_as_percentage':
                    $formattedvalue = offlinequiz_report_scale_summarks_as_percentage($value, $offlinequiz);
                    break;
                case 'scale_to_maxgrade':
                    $formattedvalue = offlinequiz_report_scale_grade($value, $offlinequiz);
                    break;
                case 'number_format_percent':
                    $formattedvalue = offlinequiz_format_grade($offlinequiz, $value) . '%';
                    break;
                case 'number_format':
                    // 2 extra decimal places, since not a percentage,
                    // and we want the same number of sig figs.???
                    $formattedvalue = format_float($value, $offlinequiz->decimalpoints);
                    break;
                case 'percent_to_number_format':
                    $formattedvalue = format_float($value / 100.00, $offlinequiz->decimalpoints);
                    break;
                case 'number':
                    $formattedvalue = $value + 0;
                    break;
                default:
                    $formattedvalue = $value;
            }

            $offlinequizinfo[get_string($property, 'offlinequiz_statistics',
                    $this->using_attempts_string(!empty($offlinequizstats->allattempts)))] =
                    $formattedvalue;
        }

        return $offlinequizinfo;
    }

    /**
     * Output the table that lists all the questions in the offlinequiz with their statistics.
     * @param int $s number of attempts.
     * @param array $questions the questions in the offlinequiz.
     * @param array $subquestions the subquestions of any random questions.
     */
    protected function output_offlinequiz_question_answer_table($s, $questions, $subquestions, $offlinequizstats) {
        if (!$s) {
            return;
        }

        foreach ($questions as $question) {
            // Output the data for this question.
            $question->actions = 'actions';
            $this->table->add_data_keyed($this->table->format_row($question));
            $this->output_question_answers($question, $offlinequizstats);
        }
        $this->table->finish_output(!$this->table->is_downloading());
    }

    /**
     * Output a question and its answers in one table in a sequence of rows.
     *
     * @param object $question
     */
    protected function output_question_answers($question, $offlinequizstats) {

        $exportclass = $this->table->export_class_instance();
        $responesstats = new offlinequiz_statistics_response_analyser($question);
        $responesstats->load_cached($offlinequizstats->id);
        $this->table->set_questiondata($question);

        $letterstr = 'abcdefghijklmnopqrstuvwxyz';
        $counter = 0;
        $counter2 = 0;

        foreach ($responesstats->responseclasses as $partid => $partclasses) {
            $rowdata = new stdclass();
            $partcounter = 0;
            foreach ($partclasses as $responseclassid => $responseclass) {
                $rowdata->responseclass = $responseclass->responseclass;
                $responsesdata = $responesstats->responses[$partid][$responseclassid];

                if (empty($responsesdata)) {
                    $rowdata->part = $letterstr[$counter++] . ')';
                    $rowdata->response = $responseclass->responseclass;
                    $rowdata->response = str_ireplace(array('<br />', '<br/>', '<br>', "\r\n"),
                            array('', '', '', ''), $rowdata->response);
                    $rowdata->fraction = $responseclass->fraction;
                    $rowdata->count = 0;
                    $classname = '';
                    if ($rowdata->fraction > 0) {
                        $classname = 'greenrow';
                    } else if ($rowdata->fraction < 0) {
                        $classname = 'redrow';
                    }
                    if ($counter2 == 0 && $partcounter == 0) {
                        if ($this->table->is_downloading()) {
                            $rowdata->name = format_text(strip_tags($question->questiontext), FORMAT_PLAIN);
                            $rowdata->name = str_ireplace(array('<br />', '<br/>', '<br>', "\r\n"),
                                    array('', '', '', ''), $rowdata->name);
                        } else {
                            $rowdata->name = format_text(html_to_text($question->questiontext));
                        }
                    } else {
                        $rowdata->name = '';
                    }

                    $rowdata->s = '';
                    $rowdata->facility = '';
                    $rowdata->sd = '';
                    $rowdata->intended_weight = '';
                    $rowdata->effective_weight = '';
                    $rowdata->discrimination_index = '';
                    $this->table->add_data_keyed($this->table->format_row($rowdata), $classname);
                    $partcounter++;
                    continue;
                } else {
                    foreach ($responsesdata as $response => $data) {
                        $rowdata->response = $response;
                        $rowdata->response = str_ireplace(array('<br />', '<br/>', '<br>', "\r\n"),
                                array('', '', '', ''), $rowdata->response);
                        $rowdata->fraction = $data->fraction;
                        $rowdata->count = $data->count;
                        $rowdata->part = $letterstr[$counter++] . ')';

                        $classname = '';
                        if ($rowdata->fraction > 0) {
                            $classname = 'greenrow';
                        } else if ($rowdata->fraction < 0) {
                            $classname = 'redrow';
                        }

                        if ($counter2 == 0 && $partcounter == 0) {
                            if ($this->table->is_downloading()) {
                                $rowdata->name = format_text(strip_tags($question->questiontext), FORMAT_PLAIN);
                                $rowdata->name = str_ireplace(array('<br />', '<br/>', '<br>', "\r\n"),
                                        array('', '', '', ''), $rowdata->name);
                            } else {
                                $rowdata->name = format_text(html_to_text($question->questiontext));
                            }
                        } else {
                            $rowdata->name = '';
                        }
                        $rowdata->s = '';
                        $rowdata->facility = '';
                        $rowdata->sd = '';
                        $rowdata->intended_weight = '';
                        $rowdata->effective_weight = '';
                        $rowdata->discrimination_index = '';
                        $this->table->add_data_keyed($this->table->format_row($rowdata), $classname);
                        $partcounter++;
                        break; // We want to display every response only once.
                    }
                }
            }
            $counter2++;
        }
    }

    /**
     * Output the table of overall offlinequiz statistics.
     * @param array $offlinequizinfo as returned by {@link get_formatted_offlinequiz_info_data()}.
     * @return string the HTML.
     */
    protected function output_offlinequiz_info_table($offlinequizinfo) {
        $offlinequizinfotable = new html_table();
        $offlinequizinfotable->id = 'statsoverviewtable';
        $offlinequizinfotable->align = array('left', 'right');
        $offlinequizinfotable->attributes['class'] = 'generaltable titlesleft';
        $offlinequizinfotable->data = array();

        foreach ($offlinequizinfo as $heading => $value) {
             $offlinequizinfotable->data[] = array($heading, $value);
        }

        return html_writer::table($offlinequizinfotable);
    }

    /**
     * Download the table of overall offlinequiz statistics.
     * @param array $offlinequizinfo as returned by {@link get_formatted_offlinequiz_info_data()}.
     */
    protected function download_offlinequiz_info_table($offlinequizinfo) {
        global $OUTPUT;

        // XHTML download is a special case.
        if ($this->table->is_downloading() == 'xhtml') {
            echo $OUTPUT->heading(get_string('offlinequizinformation', 'offlinequiz_statistics'));
            echo $this->output_offlinequiz_info_table($offlinequizinfo);
            return;
        }

        // Reformat the data ready for output.
        $headers = array();
        $row = array();
        foreach ($offlinequizinfo as $heading => $value) {
            $headers[] = $heading;
            if (is_double($value)) {
                $row[] = format_float($value, 2);
            } else {
                $row[] = $value;
            }
        }

        // Do the output.
        $exportclass = $this->table->export_class_instance();
        $exportclass->start_table(get_string('offlinequizinformation', 'offlinequiz_statistics'));
        $exportclass->output_headers($headers);
        $exportclass->add_data($row);
        $exportclass->finish_table();
    }

    /**
     * Output the HTML needed to show the statistics graph.
     * @param int $offlinequizstatsid the id of the statistics to show in the graph.
     */
    protected function output_statistics_graph($offlinequizstatsid, $s) {
        global $PAGE;

        if ($s == 0) {
            return;
        }

        $output = $PAGE->get_renderer('mod_offlinequiz');
        $imageurl = new moodle_url('/mod/offlinequiz/report/statistics/statistics_graph.php',
                array('id' => $offlinequizstatsid));
        $graphname = get_string('statisticsreportgraph', 'offlinequiz_statistics');
        echo $output->graph($imageurl, $graphname);
    }

    /**
     * Return the stats data for when there are no stats to show.
     *
     * @param array $questions question definitions.
     * @param int $firstattemptscount number of first attempts (optional).
     * @param int $firstattemptscount total number of attempts (optional).
     * @return array with three elements:
     *      - integer $s Number of attempts included in the stats (0).
     *      - array $offlinequizstats The statistics for overall attempt scores.
     *      - array $qstats The statistics for each question.
     */
    protected function get_emtpy_stats($questions, $firstattemptscount = 0,
            $allattemptscount = 0) {
        $offlinequizstats = new stdClass();
        $offlinequizstats->firstattemptscount = $firstattemptscount;
        $offlinequizstats->allattemptscount = $allattemptscount;

        $qstats = new stdClass();
        $qstats->questions = $questions;
        $qstats->subquestions = array();
        $qstats->responses = array();

        return array(0, $offlinequizstats, false);
    }

    /**
     * Compute the offlinequiz statistics.
     *
     * @param object $offlinequizid the offlinequiz id.
     * @param int $currentgroup the current group. 0 for none.
     * @param bool $nostudentsingroup true if there a no students.
     * @param bool $useallattempts use all attempts, or just first attempts.
     * @param array $groupstudents students in this group.
     * @param array $questions question definitions.
     * @return array with three elements:
     *      - integer $s Number of attempts included in the stats.
     *      - array $offlinequizstats The statistics for overall attempt scores.
     *      - array $qstats The statistics for each question.
     */
    protected function compute_stats($offlinequizid, $currentgroup, $nostudentsingroup,
            $useallattempts, $groupstudents, $questions, $offlinegroupid) {
        global $DB;

        // Calculating MEAN of marks for all attempts by students
        // http://docs.moodle.org/dev/Offlinequiz_item_analysis_calculations_in_practise
        //     #Calculating_MEAN_of_grades_for_all_attempts_by_students.
        if ($nostudentsingroup) {
            return $this->get_emtpy_stats($questions);
        }

        list($fromqa, $whereqa, $qaparams) = offlinequiz_statistics_attempts_sql(
                $offlinequizid, $currentgroup, $groupstudents, true, false, $offlinegroupid);

        $attempttotals = $DB->get_records_sql("
                SELECT
                    1,
                    COUNT(1) AS countrecs,
                    SUM(sumgrades) AS total
                FROM $fromqa
                WHERE $whereqa
                GROUP BY 1", $qaparams);
        // GROUP BY CASE WHEN attempt = 1 THEN 1 ELSE 0 END AS isfirst.

        if (!$attempttotals) {
            return $this->get_emtpy_stats($questions);
        }

        if (isset($attempttotals[1])) {
            $firstattempts = $attempttotals[1];
            $firstattempts->average = $firstattempts->total / $firstattempts->countrecs;
        } else {
            $firstattempts = new stdClass();
            $firstattempts->countrecs = 0;
            $firstattempts->total = 0;
            $firstattempts->average = null;
        }

        $allattempts = new stdClass();
        if (isset($attempttotals[0])) {
            $allattempts->countrecs = $firstattempts->countrecs + $attempttotals[0]->countrecs;
            $allattempts->total = $firstattempts->total + $attempttotals[0]->total;
        } else {
            $allattempts->countrecs = $firstattempts->countrecs;
            $allattempts->total = $firstattempts->total;
        }

        if ($useallattempts) {
            $usingattempts = $allattempts;
            $usingattempts->sql = '';
        } else {
            $usingattempts = $firstattempts;
            $usingattempts->sql = 'AND offlinequiza.attempt = 1 ';
        }
        $s = $usingattempts->countrecs;
        if ($s == 0) {
            return $this->get_emtpy_stats($questions, $firstattempts->countrecs,
                    $allattempts->countrecs);
        }
        $summarksavg = $usingattempts->total / $usingattempts->countrecs;

        $offlinequizstats = new stdClass();
        $offlinequizstats->allattempts = $useallattempts;
        $offlinequizstats->firstattemptscount = $firstattempts->countrecs;
        $offlinequizstats->allattemptscount = $allattempts->countrecs;
        $offlinequizstats->firstattemptsavg = $firstattempts->average;
        $offlinequizstats->allattemptsavg = $allattempts->total / $allattempts->countrecs;

        $marks = $DB->get_fieldset_sql("
                SELECT sumgrades
                FROM $fromqa
                WHERE $whereqa", $qaparams);

        // Also remember the best and worst grade.
        $offlinequizstats->bestgrade = max($marks);
        $offlinequizstats->worstgrade = min($marks);

        // Recalculate sql again this time possibly including test for first attempt.
        list($fromqa, $whereqa, $qaparams) = offlinequiz_statistics_attempts_sql(
                $offlinequizid, $currentgroup, $groupstudents, $useallattempts, false, $offlinegroupid);

        // Median ...
        if ($s % 2 == 0) {
            // An even number of attempts.
            $limitoffset = $s / 2 - 1;
            $limit = 2;
        } else {
            $limitoffset = floor($s / 2);
            $limit = 1;
        }
        $sql = "SELECT id, sumgrades
                  FROM $fromqa
                 WHERE $whereqa
              ORDER BY sumgrades";

        $medianmarks = $DB->get_records_sql_menu($sql, $qaparams, $limitoffset, $limit);

        $offlinequizstats->median = array_sum($medianmarks) / count($medianmarks);
        if ($s > 1) {
            // Fetch the sum of squared, cubed and power 4d
            // differences between marks and mean mark.
            $mean = $usingattempts->total / $s;
            $sql = "SELECT
                    SUM(POWER((offlinequiza.sumgrades - $mean), 2)) AS power2,
                    SUM(POWER((offlinequiza.sumgrades - $mean), 3)) AS power3,
                    SUM(POWER((offlinequiza.sumgrades - $mean), 4)) AS power4
                    FROM $fromqa
                    WHERE $whereqa";
            $params = array('mean1' => $mean, 'mean2' => $mean, 'mean3' => $mean) + $qaparams;

            $powers = $DB->get_record_sql($sql, $params, MUST_EXIST);

            // Standard_Deviation:
            // see http://docs.moodle.org/dev/Offlinequiz_item_analysis_calculations_in_practise
            //         #Standard_Deviation.

            $offlinequizstats->standarddeviation = sqrt($powers->power2 / ($s - 1));

            // Skewness.
            if ($s > 2) {
                // See http://docs.moodle.org/dev/
                //      Offlinequiz_item_analysis_calculations_in_practise#Skewness_and_Kurtosis.
                $m2 = $powers->power2 / $s;
                $m3 = $powers->power3 / $s;
                $m4 = $powers->power4 / $s;

                $k2 = $s * $m2 / ($s - 1);
                $k3 = $s * $s * $m3 / (($s - 1) * ($s - 2));
                if ($k2) {
                    $offlinequizstats->skewness = $k3 / (pow($k2, 3 / 2));
                }
            }

            // Kurtosis.
            if ($s > 3) {
                $k4 = $s * $s * ((($s + 1) * $m4) - (3 * ($s - 1) * $m2 * $m2)) / (($s - 1) * ($s - 2) * ($s - 3));
                if ($k2) {
                    $offlinequizstats->kurtosis = $k4 / ($k2 * $k2);
                }
            }
        }
        $qstats = new offlinequiz_statistics_question_stats($questions, $s, $summarksavg);
        $qstats->load_step_data($offlinequizid, $currentgroup, $groupstudents, $useallattempts, $offlinegroupid);
        $qstats->compute_statistics();

        if ($s > 1) {
            $p = count($qstats->questions); // Number of positions.
            if ($p > 1 && isset($k2)) {
                if ($k2 == 0) {
                    $offlinequizstats->cic = null;
                    $offlinequizstats->errorratio = null;
                    $offlinequizstats->standarderror = null;
                } else {
                    $offlinequizstats->cic = (100 * $p / ($p - 1)) * (1 - ($qstats->get_sum_of_mark_variance()) / $k2);
                    $offlinequizstats->errorratio = 100 * sqrt(1 - ($offlinequizstats->cic / 100));
                    $offlinequizstats->standarderror = $offlinequizstats->errorratio * $offlinequizstats->standarddeviation / 100;
                }
            }
        }

        return array($s, $offlinequizstats, $qstats);
    }

    /**
     * Load the cached statistics from the database.
     *
     * @param object $offlinequiz the offlinequiz settings
     * @param int $currentgroup the current group. 0 for none.
     * @param bool $nostudentsingroup true if there a no students.
     * @param bool $useallattempts use all attempts, or just first attempts.
     * @param array $groupstudents students in this group.
     * @param array $questions question definitions.
     * @return array with 4 elements:
     *     - $offlinequizstats The statistics for overall attempt scores.
     *     - $questions The questions, with an additional _stats field.
     *     - $subquestions The subquestions, if any, with an additional _stats field.
     *     - $s Number of attempts included in the stats.
     * If there is no cached data in the database, returns an array of four nulls.
     */
    protected function try_loading_cached_stats($offlinequiz, $currentgroup,
            $nostudentsingroup, $useallattempts, $groupstudents, $questions) {
        global $DB;

        $timemodified = time() - self::TIME_TO_CACHE_STATS;
        if ($offlinequiz->groupid) {
            $offlinequizstats = $DB->get_record_select('offlinequiz_statistics',
                'offlinequizid = ? AND offlinegroupid = ? AND groupid = ? AND allattempts = ? AND timemodified > ?',
                array($offlinequiz->id, $offlinequiz->groupid, $currentgroup, $useallattempts, $timemodified));
        } else {
            $offlinequizstats = $DB->get_record_select('offlinequiz_statistics',
                'offlinequizid = ? AND offlinegroupid = 0 AND groupid = ? AND allattempts = ? AND timemodified > ?',
                    array($offlinequiz->id, $currentgroup, $useallattempts, $timemodified));
        }

        if (!$offlinequizstats) {
            // No cached data found.
            return array(null, $questions, null, null);
        }

        if ($useallattempts) {
            $s = $offlinequizstats->allattemptscount;
        } else {
            $s = $offlinequizstats->firstattemptscount;
        }

        $subquestions = array();
        $questionstats = $DB->get_records('offlinequiz_q_statistics',
                array('offlinequizstatisticsid' => $offlinequizstats->id));

        $subquestionstats = array();
        foreach ($questionstats as $stat) {
            $questions[$stat->questionid]->_stats = $stat;
        }

        if (!empty($subquestionstats)) {
            $subqstofetch = array_keys($subquestionstats);
            $subquestions = question_load_questions($subqstofetch);
            foreach ($subquestions as $subqid => $subq) {
                $subquestions[$subqid]->_stats = $subquestionstats[$subqid];
                $subquestions[$subqid]->maxmark = $subq->defaultmark;
            }
        }

        return array($offlinequizstats, $questions, $subquestions, $s);
    }

    /**
     * Store the statistics in the cache tables in the database.
     *
     * @param object $offlinequizid the offlinequiz id.
     * @param int $currentgroup the current group. 0 for none.
     * @param bool $useallattempts use all attempts, or just first attempts.
     * @param object $offlinequizstats The statistics for overall attempt scores.
     * @param array $questions The questions, with an additional _stats field.
     * @param array $subquestions The subquestions, if any, with an additional _stats field.
     */
    protected function cache_stats($offlinequizid, $currentgroup,
            $offlinequizstats, $questions, $subquestions, $offlinegroupid = 0) {
        global $DB;

        $toinsert = clone($offlinequizstats);
        $toinsert->offlinequizid = $offlinequizid;
        $toinsert->offlinegroupid = $offlinegroupid;
        $toinsert->groupid = $currentgroup;
        $toinsert->timemodified = time();

        // Fix up some dodgy data.
        if (isset($toinsert->errorratio) && is_nan($toinsert->errorratio)) {
            $toinsert->errorratio = null;
        }
        if (isset($toinsert->standarderror) && is_nan($toinsert->standarderror)) {
            $toinsert->standarderror = null;
        }

        // Store the data.
        $offlinequizstats->id = $DB->insert_record('offlinequiz_statistics', $toinsert);

        foreach ($questions as $question) {
            $question->_stats->offlinequizstatisticsid = $offlinequizstats->id;
            $DB->insert_record('offlinequiz_q_statistics', $question->_stats, false);
        }

        foreach ($subquestions as $subquestion) {
            $subquestion->_stats->offlinequizstatisticsid = $offlinequizstats->id;
            $DB->insert_record('offlinequiz_q_statistics', $subquestion->_stats, false);
        }

        return $offlinequizstats->id;
    }

    /**
     * Get the offlinequiz and question statistics, either by loading the cached results,
     * or by recomputing them.
     *
     * @param object $offlinequiz the offlinequiz settings.
     * @param int $currentgroup the current group. 0 for none.
     * @param bool $nostudentsingroup true if there a no students.
     * @param bool $useallattempts use all attempts, or just first attempts.
     * @param array $groupstudents students in this group.
     * @param array $questions question definitions.
     * @return array with 4 elements:
     *     - $offlinequizstats The statistics for overall attempt scores.
     *     - $questions The questions, with an additional _stats field.
     *     - $subquestions The subquestions, if any, with an additional _stats field.
     *     - $s Number of attempts included in the stats.
     */
    protected function get_offlinequiz_and_questions_stats($offlinequiz, $currentgroup,
            $nostudentsingroup, $useallattempts, $groupstudents, $questions) {

        list($offlinequizstats, $questions, $subquestions, $s) =
                $this->try_loading_cached_stats($offlinequiz, $currentgroup, $nostudentsingroup,
                        $useallattempts, $groupstudents, $questions);

        if (is_null($offlinequizstats)) {
            list($s, $offlinequizstats, $qstats) = $this->compute_stats($offlinequiz->id,
                    $currentgroup, $nostudentsingroup, $useallattempts, $groupstudents, $questions, $offlinequiz->groupid);

            if ($s) {
                $questions = $qstats->questions;
                $subquestions = $qstats->subquestions;
                $offlinequizstatisticsid = $this->cache_stats($offlinequiz->id, $currentgroup,
                        $offlinequizstats, $questions, $subquestions, $offlinequiz->groupid);

                $this->analyse_responses($offlinequizstatisticsid, $offlinequiz->id, $currentgroup,
                        $nostudentsingroup, $useallattempts, $groupstudents,
                        $questions, $subquestions, $offlinequiz->groupid);
            }
        }
        return array($offlinequizstats, $questions, $subquestions, $s);
    }

    protected function analyse_responses($offlinequizstatisticsid, $offlinequizid, $currentgroup,
            $nostudentsingroup, $useallattempts, $groupstudents, $questions, $subquestions, $offlinegroupid) {

        $qubaids = offlinequiz_statistics_qubaids_condition(
                $offlinequizid, $currentgroup, $groupstudents, $useallattempts, false, $offlinegroupid);

        $done = array();
        foreach ($questions as $question) {
            if (!question_bank::get_qtype($question->qtype, false)->can_analyse_responses()) {
                continue;
            }
            $done[$question->id] = 1;
            $responesstats = new offlinequiz_statistics_response_analyser($question);
            $responesstats->analyse($qubaids);
            $responesstats->store_cached($offlinequizstatisticsid);
        }

        foreach ($subquestions as $question) {
            if (!question_bank::get_qtype($question->qtype, false)->can_analyse_responses() ||
                    isset($done[$question->id])) {
                continue;
            }
            $done[$question->id] = 1;

            $responesstats = new offlinequiz_statistics_response_analyser($question);
            $responesstats->analyse($qubaids);
            $responesstats->store_cached($offlinequizstatisticsid);
        }
    }

    /**
     * @return string HTML snipped for the Download full report as UI.
     */
    protected function everything_download_options() {
        global $OUTPUT;
        if($this->table->baseurl) {
            return $OUTPUT->download_dataformat_selector(get_string('downloadeverything', 'offlinequiz_statistics'),
                    $this->table->baseurl->out_omit_querystring(), 'download', $this->table->baseurl->params() + array('everything' => 1));
        }
    }

    /**
     * Generate the snipped of HTML that says when the stats were last caculated,
     * with a recalcuate now button.
     * @param object $offlinequizstats the overall offlinequiz statistics.
     * @param int $offlinequizid the offlinequiz id.
     * @param int $currentgroup the id of the currently selected group, or 0.
     * @param array $groupstudents ids of students in the group.
     * @param bool $useallattempts whether to use all attempts, instead of just
     *      first attempts.
     * @return string a HTML snipped saying when the stats were last computed,
     *      or blank if that is not appropriate.
     */
    protected function output_caching_info($offlinequizstats, $offlinequizid, $currentgroup,
            $groupstudents, $useallattempts, $reporturl, $offlinegroupid) {
        global $DB, $OUTPUT;

        if (empty($offlinequizstats->timemodified)) {
            return '';
        }

        // Find the number of attempts since the cached statistics were computed.
        list($fromqa, $whereqa, $qaparams) = offlinequiz_statistics_attempts_sql(
                $offlinequizid, $currentgroup, $groupstudents, $useallattempts, true, false, $offlinegroupid);
        $count = $DB->count_records_sql("
                SELECT COUNT(1)
                FROM $fromqa
                WHERE $whereqa
                AND offlinequiza.timefinish > {$offlinequizstats->timemodified}", $qaparams);

        if (!$count) {
            $count = 0;
        }

        // Generate the output.
        $a = new stdClass();
        $a->lastcalculated = format_time(time() - $offlinequizstats->timemodified);
        $a->count = $count;

        $recalcualteurl = new moodle_url($reporturl,
                array('recalculate' => 1, 'sesskey' => sesskey()));
        $output = '<br/>';
        $output .= $OUTPUT->box_start(
                'boxaligncenter generalbox boxwidthnormal mdl-align', 'cachingnotice');
        $output .= get_string('lastcalculated', 'offlinequiz_statistics', $a);
        $output .= $OUTPUT->single_button($recalcualteurl,
                get_string('recalculatenow', 'offlinequiz_statistics'));
        $output .= $OUTPUT->box_end(true);

        return $output;
    }

    /**
     * Clear the cached data for a particular report configuration. This will
     * trigger a re-computation the next time the report is displayed.
     * @param int $offlinequizid the offlinequiz id.
     * @param int $currentgroup a group id, or 0.
     * @param bool $useallattempts whether all attempts, or just first attempts are included.
     */
    protected function clear_cached_data($offlinequizid, $currentgroup, $useallattempts, $offlinegroupid) {
        global $DB;

        if ($offlinegroupid) {
            $todelete = $DB->get_records_menu('offlinequiz_statistics',
                    array('offlinequizid' => $offlinequizid, 'offlinegroupid' => $offlinegroupid,
                    'groupid' => $currentgroup, 'allattempts' => $useallattempts), '', 'id, 1');

        } else {
            $todelete = $DB->get_records_menu('offlinequiz_statistics', array('offlinequizid' => $offlinequizid,
                    'groupid' => $currentgroup, 'allattempts' => $useallattempts), '', 'id, 1');
        }

        if (!$todelete) {
            return;
        }

        list($todeletesql, $todeleteparams) = $DB->get_in_or_equal(array_keys($todelete));

        $DB->delete_records_select('offlinequiz_q_statistics',
                'offlinequizstatisticsid ' . $todeletesql, $todeleteparams);
        $DB->delete_records_select('offlinequiz_q_response_stats',
                'offlinequizstatisticsid ' . $todeletesql, $todeleteparams);
        $DB->delete_records_select('offlinequiz_statistics',
                'id ' . $todeletesql, $todeleteparams);
    }

    /**
     * @param bool $useallattempts whether we are using all attempts.
     * @return the appropriate lang string to describe this option.
     */
    protected function using_attempts_string($useallattempts) {
        if ($useallattempts) {
            return get_string('allattempts', 'offlinequiz_statistics');
        } else {
            return get_string('firstattempts', 'offlinequiz_statistics');
        }
    }
}

function offlinequiz_statistics_attempts_sql($offlinequizid, $currentgroup, $groupstudents,
        $allattempts = true, $includeungraded = false, $offlinegroupid = 0) {
    global $DB;

    $fromqa = '{offlinequiz_results} offlinequiza ';

    $whereqa = 'offlinequiza.offlinequizid = :offlinequizid AND  offlinequiza.status = :offlinequizstatefinished';
    $qaparams = array('offlinequizid' => $offlinequizid, 'offlinequizstatefinished' => 'complete');

    if ($offlinegroupid) {
        $whereqa .= ' AND offlinequiza.offlinegroupid = :offlinegroupid';
        $qaparams['offlinegroupid'] = $offlinegroupid;
    }

    if (!empty($currentgroup) && $groupstudents) {
        list($grpsql, $grpparams) = $DB->get_in_or_equal(array_keys($groupstudents),
                SQL_PARAMS_NAMED, 'u');
        $whereqa .= " AND offlinequiza.userid $grpsql";
        $qaparams += $grpparams;
    }

    if (!$includeungraded) {
        $whereqa .= ' AND offlinequiza.sumgrades IS NOT NULL';
    }

    return array($fromqa, $whereqa, $qaparams);
}

/**
 * Return a {@link qubaid_condition} from the values returned by
 * {@link offlinequiz_statistics_attempts_sql}
 * @param string $fromqa from offlinequiz_statistics_attempts_sql.
 * @param string $whereqa from offlinequiz_statistics_attempts_sql.
 */
function offlinequiz_statistics_qubaids_condition($offlinequizid, $currentgroup, $groupstudents,
        $allattempts = true, $includeungraded = false, $offlinegroupid = 0) {
    list($fromqa, $whereqa, $qaparams) = offlinequiz_statistics_attempts_sql($offlinequizid, $currentgroup,
            $groupstudents, $allattempts, $includeungraded, $offlinegroupid);
    return new qubaid_join($fromqa, 'offlinequiza.usageid', $whereqa, $qaparams);
}
