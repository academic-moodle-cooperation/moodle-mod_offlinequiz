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
 * Strings for component 'offlinequiz_statistics', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   offlinequiz_statistics
 * @author    Juergen Zimmer
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actualresponse'] = 'Actual response';
$string['allattempts'] = 'all attempts';
$string['allattemptsavg'] = 'Average grade of all results';
$string['allattemptscount'] = 'Total number of complete graded results';
$string['allgroups'] = 'All groups';
$string['analysisofresponses'] = 'Analysis of responses';
$string['analysisofresponsesfor'] = 'Analysis of responses for {$a}.';
$string['attempts'] = 'Attempts';
$string['attemptsall'] = 'all attempts';
$string['attemptsfirst'] = 'first attempt';
$string['backtoquestionsandanswers'] = 'Back to main statistics report page.';
$string['bestgrade'] = 'Highest grade achieved';
$string['calculatefrom'] = 'Calculate statistics from';
$string['cic'] = 'Coefficient of internal consistency';
$string['completestatsfilename'] = 'completestats';
$string['correct'] = 'Answered correctly';
$string['count'] = 'Count';
$string['coursename'] = 'Course name';
$string['detailedanalysis'] = 'More detailed analysis of the responses to this question';
$string['differentquestions'] = 'Your offline quiz groups contain different sets of questions.';
$string['differentsumgrades'] = 'Your offline quiz groups have different sums of grades ({$a}). Therefore, the average grade, the median, and the standard deviation cannot be calculated.';
$string['discrimination_index'] = 'Discrimination index';
$string['discriminative_efficiency'] = 'Discriminative efficiency';
$string['downloadeverything'] = 'Download full report as';
$string['duration'] = 'Open for';
$string['effective_weight'] = 'Effective weight';
$string['errordeleting'] = 'Error deleting old {$a} records.';
$string['erroritemappearsmorethanoncewithdifferentweight'] = 'Question ({$a}) appears more than once with different weights in different positions of the test. This is not currently supported by the statistics report and may make the statistics for this question unreliable.';
$string['errormedian'] = 'Error fetching median';
$string['errorpowerquestions'] = 'Error fetching data to calculate variance for question grades';
$string['errorpowers'] = 'Error fetching data to calculate variance for offline quiz grades';
$string['errorrandom'] = 'Error getting sub item data';
$string['errorratio'] = 'Error ratio';
$string['errorstatisticsquestions'] = 'Error fetching data to calculate statistics for question grades';
$string['facility'] = 'Facility index';
$string['firstattempts'] = 'first attempts';
$string['firstattemptsavg'] = 'Average grade of first attempts';
$string['firstattemptscount'] = 'Number of complete graded first attempts';
$string['frequency'] = 'Frequency';
$string['intended_weight'] = 'Intended weight';
$string['kurtosis'] = 'Score distribution kurtosis';
$string['lastcalculated'] = 'Last calculated {$a->lastcalculated} ago there have been {$a->count} attempts since then.';
$string['maxgrade'] = 'Maximum grade achievable';
$string['median'] = 'Median grade';
$string['modelresponse'] = 'Model response';
$string['negcovar'] = 'Negative covariance of grade with total attempt grade';
$string['negcovar_help'] = 'This question\'s grade for this set of attempts on the offline quiz varies in an opposite way to the overall attempt grade. This means overall attempt grade tends to be below average when the grade for this question is above average and vice-versa.

Our equation for effective question weight cannot be calculated in this case. The calculations for effective question weight for other questions in this offline quiz are the effective question weight for these questions if the highlighted questions with a negative covariance are given a maximum grade of zero.

If you edit a offline quiz and give these question(s) with negative covariance a max grade of zero then the effective question weight of these questions will be zero and the real effective question weight of other questions will be as calculated now.';
$string['nostudentsingroup'] = 'There are no students in this group yet';
$string['optiongrade'] = 'Partial credit';
$string['partially'] = 'Answered Partially correctly';
$string['partofquestion'] = '#Answer';
$string['pluginname'] = 'Offline Quiz Statistics';
$string['position'] = 'Position';
$string['positions'] = 'Position(s)';
$string['preferencespage'] = 'Preferences just for this page';
$string['preferencessave'] = 'Save preferences';
$string['privacy:metadata'] = 'This plugin does not store any user related data.';
$string['questionandanswerstats'] = 'Questions and answers';
$string['questionandanswerstatsheader'] = 'Question and answer analysis';
$string['questioninformation'] = 'Question information';
$string['questionname'] = 'Question name';
$string['questionnumber'] = 'Q#';
$string['questionstatistics'] = 'Question statistics';
$string['questionstats'] = 'Question analysis';
$string['questionstatsheader'] = 'Question analysis';
$string['questionstatsfilename'] = 'questionstats';
$string['questiontype'] = 'Question type';
$string['offlinequizinformation'] = 'Offline quiz information';
$string['offlinequizname'] = 'Offline quiz name';
$string['offlinequizoverallstatistics'] = 'Offline quiz overall statistics';
$string['offlinequizstructureanalysis'] = 'Offline quiz structure analysis';
$string['random_guess_score'] = 'Random guess score';
$string['recalculatenow'] = 'Recalculate now';
$string['remarks'] = 'Note';
$string['response'] = 'Response';
$string['skewness'] = 'Score distribution skewness';
$string['standarddeviation'] = 'Standard deviation';
$string['standarddeviationq'] = 'Standard deviation';
$string['standarderror'] = 'Standard error';
$string['statistics'] = 'Statistics';
$string['statistics:componentname'] = 'Offline quiz statistics report';
$string['statisticsforgroup'] = 'Group';
$string['statisticshelp'] = 'Help for offline quiz statistics';
$string['statsoverview'] = 'Overview';
$string['statsoverviewheader'] = 'Quiz information';
$string['statisticsreport'] = 'Statistics report';
$string['statisticsreportgraph'] = 'Statistics for question positions';
$string['statistics:view'] = 'View statistics report';
$string['statsfor'] = 'Offline quiz statistics (for {$a})';
$string['worstgrade'] = 'Lowest grade achieved';
$string['wrong'] = 'Answered wrongly';
