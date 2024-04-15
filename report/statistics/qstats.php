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
 * Offlinequiz statistics report question calculations class.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.5
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();


/**
 * This class has methods to compute the question statistics from the raw data.
 *
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offlinequiz_statistics_question_stats {
    public $questions;
    public $subquestions = array();

    protected $s;
    protected $summarksavg;
    protected $allattempts;

    /** @var mixed states from which to calculate stats - iteratable. */
    protected $lateststeps;

    protected $sumofmarkvariance = 0;
    protected $randomselectors = array();

    /**
     * Constructor.
     * @param $questions the questions.
     * @param $s the number of attempts included in the stats.
     * @param $summarksavg the average attempt summarks.
     */
    public function __construct($questions, $s, $summarksavg) {
        $this->s = $s;
        $this->summarksavg = $summarksavg;
        $slot = 1;
        foreach ($questions as $qid => $question) {
            $question->_stats = $this->make_blank_question_stats();
            $question->_stats->questionid = $qid;
            $question->_stats->slot = $slot++;
        }

        $this->questions = $questions;
    }

    /**
     * @return object ready to hold all the question statistics.
     */
    protected function make_blank_question_stats() {
        $stats = new stdClass();
        $stats->slot = null;
        $stats->s = 0;
        $stats->totalmarks = 0;
        $stats->totalothermarks = 0;
        $stats->markvariancesum = 0;
        $stats->othermarkvariancesum = 0;
        $stats->covariancesum = 0;
        $stats->covariancemaxsum = 0;
        $stats->subquestion = false;
        $stats->subquestions = '';
        $stats->covariancewithoverallmarksum = 0;
        $stats->randomguessscore = null;
        $stats->markarray = array();
        $stats->othermarksarray = array();
        return $stats;
    }

    /**
     * Load the data that will be needed to perform the calculations.
     *
     * @param int $offlinequizid the offlinequiz id.
     * @param int $currentgroup the current group. 0 for none.
     * @param array $groupstudents students in this group.
     * @param bool $allattempts use all attempts, or just first attempts.
     */
    public function load_step_data($offlinequizid, $currentgroup, $groupstudents, $allattempts, $offlinegroupid) {
        global $DB;

        $this->allattempts = $allattempts;

        $questionids = array();
        foreach ($this->questions as $question) {
            $questionids[] = $question->id;
        }

        // Get the SQL and params for question IDs.
        list($qsql, $qparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'q');

        list($fromqa, $whereqa, $qaparams) = offlinequiz_statistics_attempts_sql(
                $offlinequizid, $currentgroup, $groupstudents, $allattempts, false, $offlinegroupid);

        // Trying to make this work on MySQL
        // First we get the questionattemptids that we actually need.
        $params = array_merge($qparams, $qaparams);

        // Works already quite a bit faster.
        $this->lateststeps = $DB->get_records_sql("
                SELECT
                    qas.id,
                    offlinequiza.sumgrades,
                    qa.questionid,
                    qa.slot,
                    qa.maxmark,
                    qas.fraction,
                    qas.fraction * qa.maxmark as mark

                FROM $fromqa
                JOIN {question_attempts} qa ON qa.questionusageid = offlinequiza.usageid AND qa.questionid $qsql
                JOIN {question_attempt_steps} qas ON 
                      qas.id =(SELECT MAX(id) FROM {question_attempt_steps} qas2 WHERE qas2.questionattemptid = qa.id)
                WHERE $whereqa", $params);
    }

    /*
     * Compute the statistics for the questions given to the constructor
     *
     */
    public function compute_statistics() {
        set_time_limit(0);

        $subquestionstats = array();

        // Compute the statistics of position, and for random questions, work
        // out which questions appear in which positions.
        foreach ($this->lateststeps as $step) {
            $this->initial_steps_walker($step, $this->questions[$step->questionid]->_stats);
            // If this is a random question what is the real item being used?
            if ($step->questionid != $this->questions[$step->questionid]->id) {
                if (!isset($subquestionstats[$step->questionid])) {
                    $subquestionstats[$step->questionid] = $this->make_blank_question_stats();
                    $subquestionstats[$step->questionid]->questionid = $step->questionid;
                    $subquestionstats[$step->questionid]->allattempts = $this->allattempts;
                    $subquestionstats[$step->questionid]->usedin = array();
                    $subquestionstats[$step->questionid]->subquestion = true;
                    $subquestionstats[$step->questionid]->differentweights = false;
                    $subquestionstats[$step->questionid]->maxmark = $step->maxmark;
                    $subquestionstats[$step->questionid]->correct = 0;
                    $subquestionstats[$step->questionid]->partially = 0;
                    $subquestionstats[$step->questionid]->wrong = 0;
                } else if ($subquestionstats[$step->questionid]->maxmark != $step->maxmark) {
                    $subquestionstats[$step->questionid]->differentweights = true;
                }
                // Redmine 1302. Compute the number of results.
                if (abs($step->maxmark - $step->mark) <= 1e-7) {
                    $subquestionstats[$step->questionid]->correct++;
                } else if ($step->mark > 0 && $step->mark < $step->maxmark &&
                         (abs($step->maxmark - $step->mark) > 1e-7)) {
                    $subquestionstats[$step->questionid]->partially++;
                } else {
                    $subquestionstats[$step->questionid]->wrong++;
                }

                $this->initial_steps_walker($step,
                        $subquestionstats[$step->questionid], false);

                $number = $this->questions[$step->questionid]->number;
                $subquestionstats[$step->questionid]->usedin[$number] = $number;

                $randomselectorstring = $this->questions[$step->questionid]->category .
                        '/' . $this->questions[$step->questionid]->questiontext;
                if (!isset($this->randomselectors[$randomselectorstring])) {
                    $this->randomselectors[$randomselectorstring] = array();
                }
                $this->randomselectors[$randomselectorstring][$step->questionid] = $step->questionid;
            }
        }

        foreach ($this->randomselectors as $key => $notused) {
            ksort($this->randomselectors[$key]);
        }

        // Compute the statistics of question id, if we need any.
        $this->subquestions = question_load_questions(array_keys($subquestionstats));
        foreach ($this->subquestions as $qid => $subquestion) {
            $subquestion->_stats = $subquestionstats[$qid];
            $subquestion->maxmark = $subquestion->_stats->maxmark;
            $subquestion->_stats->randomguessscore = $this->get_random_guess_score($subquestion);

            $this->initial_question_walker($subquestion->_stats);

            if ($subquestionstats[$qid]->differentweights) {
                // TODO output here really sucks, but throwing is too severe.
                global $OUTPUT;
                echo $OUTPUT->notification(
                        get_string('erroritemappearsmorethanoncewithdifferentweight',
                        'offlinequiz_statistics', $this->subquestions[$qid]->name));
            }

            if ($subquestion->_stats->usedin) {
                sort($subquestion->_stats->usedin, SORT_NUMERIC);
                $subquestion->_stats->positions = implode(',', $subquestion->_stats->usedin);
            } else {
                $subquestion->_stats->positions = '';
            }
        }

        // Finish computing the averages, and put the subquestion data into the
        // corresponding questions.

        // This cannot be a foreach loop because we need to have both
        // $question and $nextquestion available.
        foreach ($this->questions as $question) {
            $nextquestion = next($this->questions);
            $question->_stats->allattempts = $this->allattempts;
            $question->_stats->positions = $question->number;
            $question->_stats->maxmark = $question->maxmark;
            $question->_stats->randomguessscore = $this->get_random_guess_score($question);

            $this->initial_question_walker($question->_stats);

            if ($question->qtype == 'random') {
                $randomselectorstring = $question->category.'/'.$question->questiontext;
                if ($nextquestion && $nextquestion->qtype == 'random') {
                    $nextrandomselectorstring = $nextquestion->category . '/' .
                            $nextquestion->questiontext;
                    if ($randomselectorstring == $nextrandomselectorstring) {
                        continue; // Next loop iteration.
                    }
                }
                if (isset($this->randomselectors[$randomselectorstring])) {
                    $question->_stats->subquestions = implode(',',
                            $this->randomselectors[$randomselectorstring]);
                }
            }
        }

        // Go through the records one more time.
        foreach ($this->lateststeps as $step) {
            $this->secondary_steps_walker($step, $this->questions[$step->questionid]->_stats);
        }

        $sumofcovariancewithoverallmark = 0;
        foreach ($this->questions as $slot => $question) {
            $this->secondary_question_walker($question->_stats);

            $this->sumofmarkvariance += $question->_stats->markvariance;

            if ($question->_stats->covariancewithoverallmark >= 0) {
                if(!is_null($question->_stats->covariancewithoverallmark)) {
                    $sumofcovariancewithoverallmark += sqrt($question->_stats->covariancewithoverallmark);
                }
                $question->_stats->negcovar = 0;
            } else {
                $question->_stats->negcovar = 1;
            }
        }

        foreach ($this->subquestions as $subquestion) {
            $this->secondary_question_walker($subquestion->_stats);
        }

        foreach ($this->questions as $question) {
            if ($sumofcovariancewithoverallmark) {
                if ($question->_stats->negcovar) {
                    $question->_stats->effectiveweight = null;
                } else {
                    $question->_stats->effectiveweight = 100 * sqrt($question->_stats->covariancewithoverallmark)
                                                         / $sumofcovariancewithoverallmark;
                }
            } else {
                $question->_stats->effectiveweight = null;
            }
        }
    }

    /**
     * Update $stats->totalmarks, $stats->markarray, $stats->totalothermarks
     * and $stats->othermarksarray to include another state.
     *
     * @param object $step the state to add to the statistics.
     * @param object $stats the question statistics we are accumulating.
     * @param bool $positionstat whether this is a statistic of position of question.
     */
    protected function initial_steps_walker($step, $stats, $positionstat = true) {
        $stats->s++;
        $stats->totalmarks += $step->mark;
        $stats->markarray[] = $step->mark;

        if ($positionstat) {
            $stats->totalothermarks += $step->sumgrades - $step->mark;
            $stats->othermarksarray[] = $step->sumgrades - $step->mark;

        } else {
            $stats->totalothermarks += $step->sumgrades;
            $stats->othermarksarray[] = $step->sumgrades;
        }
    }

    /**
     * Perform some computations on the per-question statistics calculations after
     * we have been through all the states.
     *
     * @param object $stats quetsion stats to update.
     */
    protected function initial_question_walker($stats) {
        if ($stats->s != 0) {
            $stats->markaverage = $stats->totalmarks / $stats->s;
        } else {
            $stats->markaverage = 0;
        }

        if ($stats->maxmark != 0) {
            $stats->facility = $stats->markaverage / $stats->maxmark;
        } else {
            $stats->facility = null;
        }

        if ($stats->s != 0) {
            $stats->othermarkaverage = $stats->totalothermarks / $stats->s;
        } else {
            $stats->othermarkaverage = 0;
        }
        sort($stats->markarray, SORT_NUMERIC);
        sort($stats->othermarksarray, SORT_NUMERIC);

        $stats->correct = 0;
        $stats->partially = 0;
        $stats->wrong = 0;
    }

    /**
     * Now we know the averages, accumulate the date needed to compute the higher
     * moments of the question scores.
     *
     * @param object $step the state to add to the statistics.
     * @param object $stats the question statistics we are accumulating.
     * @param bool $positionstat whether this is a statistic of position of question.
     */
    protected function secondary_steps_walker($step, $stats) {
        $markdifference = $step->mark - $stats->markaverage;
        if ($stats->subquestion) {
            $othermarkdifference = $step->sumgrades - $stats->othermarkaverage;
        } else {
            $othermarkdifference = $step->sumgrades - $step->mark - $stats->othermarkaverage;
        }
        $overallmarkdifference = $step->sumgrades - $this->summarksavg;

        $sortedmarkdifference = array_shift($stats->markarray) - $stats->markaverage;
        $sortedothermarkdifference = array_shift($stats->othermarksarray) - $stats->othermarkaverage;

        $stats->markvariancesum += pow($markdifference, 2);
        $stats->othermarkvariancesum += pow($othermarkdifference, 2);
        $stats->covariancesum += $markdifference * $othermarkdifference;
        $stats->covariancemaxsum += $sortedmarkdifference * $sortedothermarkdifference;
        $stats->covariancewithoverallmarksum += $markdifference * $overallmarkdifference;

        // Redmine 1302. Compute the number of results.
        if (abs($step->maxmark - $step->mark) <= 1e-7) {
            $stats->correct++;
        } else if ($step->mark > 0 && $step->mark < $step->maxmark &&
                   (abs($step->maxmark - $step->mark) > 1e-7)) {
            $stats->partially++;
        } else {
            $stats->wrong++;
        }
    }

    /**
     * Perform more per-question statistics calculations.
     *
     * @param object $stats quetsion stats to update.
     */
    protected function secondary_question_walker($stats) {
        if ($stats->s > 1) {
            $stats->markvariance = $stats->markvariancesum / ($stats->s - 1);
            $stats->othermarkvariance = $stats->othermarkvariancesum / ($stats->s - 1);
            $stats->covariance = $stats->covariancesum / ($stats->s - 1);
            $stats->covariancemax = $stats->covariancemaxsum / ($stats->s - 1);
            $stats->covariancewithoverallmark = $stats->covariancewithoverallmarksum / ($stats->s - 1);
            $stats->sd = sqrt($stats->markvariancesum / ($stats->s - 1));

        } else {
            $stats->markvariance = null;
            $stats->othermarkvariance = null;
            $stats->covariance = null;
            $stats->covariancemax = null;
            $stats->covariancewithoverallmark = null;
            $stats->sd = null;
        }

        if ($stats->markvariance * $stats->othermarkvariance) {
            $stats->discriminationindex = 100 * $stats->covariance / sqrt($stats->markvariance * $stats->othermarkvariance);
        } else {
            $stats->discriminationindex = null;
        }

        if ($stats->covariancemax) {
            $stats->discriminativeefficiency = 100 * $stats->covariance / $stats->covariancemax;
        } else {
            $stats->discriminativeefficiency = null;
        }
    }

    /**
     * @param object $questiondata
     * @return number the random guess score for this question.
     */
    protected function get_random_guess_score($questiondata) {
        return question_bank::get_qtype(
                $questiondata->qtype, false)->get_random_guess_score($questiondata);
    }

    /**
     * Used when computing CIC.
     * @return number
     */
    public function get_sum_of_mark_variance() {
        return $this->sumofmarkvariance;
    }
}
