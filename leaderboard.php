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
 * Displays the leaderboard for an offline quiz.
 *
 * @package   mod_offlinequiz
 * @copyright 2024 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

$id               = required_param('id', PARAM_INT);
$filteredcohortid = optional_param('cohortid', 0, PARAM_INT);
$activetab        = optional_param('tab', 'leaderboard', PARAM_ALPHA);

$cm          = get_coursemodule_from_id('offlinequiz', $id, 0, false, MUST_EXIST);
$course      = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/offlinequiz:view', $context);

$PAGE->set_url('/mod/offlinequiz/leaderboard.php', ['id' => $id]);
$PAGE->set_title(get_string('leaderboard', 'offlinequiz'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

if (empty($offlinequiz->showleaderboard)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('leaderboardnotavailable', 'offlinequiz'), 'warning');
    echo $OUTPUT->footer();
    die();
}

$anonymous = ((int)$offlinequiz->showleaderboard === 2);

// ============================================================
// Fetch all completed results for this quiz.
// ============================================================
$sql = "SELECT r.id, r.userid, r.usageid, r.sumgrades, og.sumgrades AS maxgrades,
               u.firstname, u.lastname,
               u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
               (SELECT sp.userkey
                  FROM {offlinequiz_scanned_pages} sp
                 WHERE sp.resultid = r.id
              ORDER BY sp.pagenumber ASC
                 LIMIT 1) AS userkey
          FROM {offlinequiz_results} r
          JOIN {offlinequiz_groups} og ON og.id = r.offlinegroupid
          JOIN {user} u ON u.id = r.userid
         WHERE r.offlinequizid = :offlinequizid
           AND r.status = 'complete'
      ORDER BY r.sumgrades DESC, u.lastname ASC, u.firstname ASC";

$results = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);

// ============================================================
// Build cohort maps for filtering and comparison.
// ============================================================
$usercohorts = [];
if (!empty($results)) {
    $userids = array_unique(array_map(fn($r) => (int)$r->userid, array_values($results)));
    [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
    $cohortrows = $DB->get_records_sql(
        "SELECT cm.userid, c.id AS cohortid, c.name AS cohortname
           FROM {cohort_members} cm
           JOIN {cohort} c ON c.id = cm.cohortid
          WHERE cm.userid $insql
          ORDER BY c.name ASC",
        $inparams
    );
    foreach ($cohortrows as $row) {
        $usercohorts[(int)$row->userid][] = ['id' => (int)$row->cohortid, 'name' => $row->cohortname];
    }
}

// Distinct cohorts in the result set for the filter dropdown.
$cohortmap = [];
foreach ($results as $result) {
    foreach ($usercohorts[(int)$result->userid] ?? [] as $c) {
        $cohortmap[$c['id']] = $c['name'];
    }
}
asort($cohortmap);

// Save full results before cohort filter (needed for whole-quiz statistics).
$allresults = $results;

// Apply cohort filter.
if ($filteredcohortid > 0) {
    $results = array_filter($results, function($r) use ($filteredcohortid, $usercohorts) {
        foreach ($usercohorts[(int)$r->userid] ?? [] as $c) {
            if ($c['id'] === $filteredcohortid) {
                return true;
            }
        }
        return false;
    });
}

// ============================================================
// Build leaderboard rows (rank recomputed within filtered set).
// ============================================================
$rows      = [];
$rank      = 0;
$prevgrade = null;
$rowindex  = 0;

foreach ($results as $result) {
    $rowindex++;
    if ($result->sumgrades !== $prevgrade) {
        $rank      = $rowindex;
        $prevgrade = $result->sumgrades;
    }

    $isyou  = ((int)$result->userid === (int)$USER->id);
    $name   = ($anonymous && !$isyou) ? '' : fullname($result);

    $cohortnames = implode(', ', array_column($usercohorts[(int)$result->userid] ?? [], 'name'));

    $maxgrades = (float)$result->maxgrades;
    $percent   = $maxgrades > 0 ? round(((float)$result->sumgrades / $maxgrades) * 100, 1) : 0;

    $rows[] = [
        'rank'    => $rank,
        'name'    => $name,
        'userkey' => $result->userkey ?? '',
        'cohort'  => $cohortnames,
        'score'   => format_float($result->sumgrades, 2),
        'max'     => format_float($maxgrades, 2),
        'percent' => $percent,
        'isyou'   => $isyou,
    ];
}

// Build filter options for the template.
$filteroptions = [];
foreach ($cohortmap as $cohortid => $cohortname) {
    $filteroptions[] = [
        'value'    => $cohortid,
        'label'    => $cohortname,
        'selected' => ($cohortid === $filteredcohortid),
    ];
}

// ============================================================
// STATISTICS — computed on the currently visible (filtered) rows.
// ============================================================

$statscores = array_column($rows, 'percent');
$statscount = count($statscores);

$scoredistcharthtml  = '';
$questioncharthtml   = '';
$cohortcharthtml     = '';
$questionstats       = [];
$questionrows        = [];
$statsdata           = [];

if ($statscount > 0) {
    $statssum = array_sum($statscores);
    $statsavg = round($statssum / $statscount, 1);

    $sorted = $statscores;
    sort($sorted);
    $statsmin = $sorted[0];
    $statsmax = $sorted[$statscount - 1];

    if ($statscount % 2 === 0) {
        $statsmedian = round(($sorted[$statscount / 2 - 1] + $sorted[$statscount / 2]) / 2, 1);
    } else {
        $statsmedian = $sorted[(int)($statscount / 2)];
    }

    if ($statscount > 1) {
        $variance = 0;
        foreach ($sorted as $s) {
            $variance += ($s - $statsavg) ** 2;
        }
        $statsstddev = round(sqrt($variance / ($statscount - 1)), 1);
    } else {
        $statsstddev = 0;
    }

    $passcount    = count(array_filter($statscores, fn($s) => $s >= 50));
    $statspassrate = round(($passcount / $statscount) * 100, 1);

    $statsdata = [
        'count'    => $statscount,
        'avg'      => $statsavg,
        'median'   => $statsmedian,
        'stddev'   => $statsstddev,
        'min'      => $statsmin,
        'max'      => $statsmax,
        'passrate' => $statspassrate,
        'passcount'=> $passcount,
    ];

    // --- Score distribution chart (10 buckets of 10%) ---
    $distlabels = [];
    $distdata   = [];
    for ($i = 0; $i < 10; $i++) {
        $from = $i * 10;
        $to   = ($i + 1) * 10;
        $distlabels[] = $from . '-' . $to . '%';
        $cnt = 0;
        foreach ($statscores as $s) {
            if ($i === 9) {
                if ($s >= 90) {
                    $cnt++;
                }
            } else if ($s >= $from && $s < $to) {
                $cnt++;
            }
        }
        $distdata[] = $cnt;
    }

    $distchart = new \core\chart_bar();
    $distchart->set_title(get_string('leaderboardstatsscoredistribution', 'offlinequiz'));
    $distseries = new \core\chart_series(
        get_string('leaderboardstatsstudents', 'offlinequiz'),
        $distdata
    );
    $distchart->add_series($distseries);
    $distchart->set_labels($distlabels);
    $scoredistcharthtml = $OUTPUT->render($distchart);

    // --- Per-question success rate chart (filtered by cohort if active) ---
    $questionparams = ['offlinequizid' => $offlinequiz->id];
    $cohortjoin = '';
    if ($filteredcohortid > 0) {
        $cohortjoin = 'JOIN {cohort_members} qcm ON qcm.userid = r.userid AND qcm.cohortid = :cohortid';
        $questionparams['cohortid'] = $filteredcohortid;
    }

    $questionrows = $DB->get_records_sql(
        "SELECT qa.questionid,
                COUNT(1)                                                                          AS total,
                SUM(CASE WHEN qas.fraction >= 1.0           THEN 1 ELSE 0 END)                  AS correct,
                SUM(CASE WHEN qas.fraction > 0
                          AND qas.fraction < 1.0            THEN 1 ELSE 0 END)                  AS partial,
                SUM(CASE WHEN COALESCE(qas.fraction, -1) <= 0 THEN 1 ELSE 0 END)               AS wrong
           FROM {offlinequiz_results}    r
           $cohortjoin
           JOIN {question_attempts}      qa  ON qa.questionusageid = r.usageid
           JOIN {question_attempt_steps} qas ON qas.id = (
                   SELECT MAX(qas2.id)
                     FROM {question_attempt_steps} qas2
                    WHERE qas2.questionattemptid = qa.id
                )
          WHERE r.offlinequizid = :offlinequizid
            AND r.status        = 'complete'
            AND r.usageid       > 0
       GROUP BY qa.questionid
       ORDER BY qa.questionid ASC",
        $questionparams
    );

    $qnum         = 1;
    $qchartlabels = [];
    $qchartdata   = [];

    foreach ($questionrows as $qrow) {
        $qtotal      = max((int)$qrow->total, 1);
        $correctpct  = round(((int)$qrow->correct / $qtotal) * 100, 1);
        $partialpct  = round(((int)$qrow->partial / $qtotal) * 100, 1);
        $wrongpct    = round(((int)$qrow->wrong   / $qtotal) * 100, 1);

        $questionstats[] = [
            'qnum'       => $qnum,
            'total'      => (int)$qrow->total,
            'correct'    => (int)$qrow->correct,
            'partial'    => (int)$qrow->partial,
            'wrong'      => (int)$qrow->wrong,
            'correctpct' => $correctpct,
            'partialpct' => $partialpct,
            'wrongpct'   => $wrongpct,
        ];

        $qchartlabels[] = 'Q' . $qnum;
        $qchartdata[]   = $correctpct;
        $qnum++;
    }

    if (!empty($questionstats)) {
        $qchart = new \core\chart_bar();
        $qchart->set_title(get_string('leaderboardstatsquestions', 'offlinequiz'));
        $qseries = new \core\chart_series(
            get_string('leaderboardstatscorrect', 'offlinequiz') . ' (%)',
            $qchartdata
        );
        $qchart->add_series($qseries);
        $qchart->set_labels($qchartlabels);
        $questioncharthtml = $OUTPUT->render($qchart);
    }

    // --- Cohort comparison chart (average % per cohort, all results) ---
    if (!empty($cohortmap)) {
        $cohortscores = [];
        foreach ($allresults as $res) {
            $maxg = (float)$res->maxgrades;
            $pct  = $maxg > 0 ? ((float)$res->sumgrades / $maxg) * 100 : 0;
            foreach ($usercohorts[(int)$res->userid] ?? [] as $c) {
                $cohortscores[$c['id']][] = $pct;
            }
        }
        $cohortlabels = [];
        $cohortavgs   = [];
        foreach ($cohortmap as $cid => $cname) {
            if (!empty($cohortscores[$cid])) {
                $cohortlabels[] = $cname;
                $cohortavgs[]   = round(
                    array_sum($cohortscores[$cid]) / count($cohortscores[$cid]),
                    1
                );
            }
        }
        if (!empty($cohortlabels)) {
            $cchart = new \core\chart_bar();
            $cchart->set_title(get_string('leaderboardstatscohorts', 'offlinequiz'));
            $cseries = new \core\chart_series(
                get_string('leaderboardstatsavgscore', 'offlinequiz'),
                $cohortavgs
            );
            $cchart->add_series($cseries);
            $cchart->set_labels($cohortlabels);
            $cohortcharthtml = $OUTPUT->render($cchart);
        }
    }
}

// ============================================================
// Current user's own result link ("My Copy" tab).
// ============================================================
$myresult = $DB->get_record('offlinequiz_results', [
    'offlinequizid' => $offlinequiz->id,
    'userid'        => $USER->id,
    'status'        => 'complete',
]);
$myreviewurl = '';
$canreview = false;
if ($myresult && $myresult->timefinish) {
    // Reflect the same gate-2 check that review.php enforces: at least one of
    // attempt / marks / sheetfeedback / gradedsheetfeedback must be enabled.
    $reviewbits = (int)$offlinequiz->review;
    $canreview = (bool)(
        ($reviewbits & OFFLINEQUIZ_REVIEW_ATTEMPT) ||
        ($reviewbits & OFFLINEQUIZ_REVIEW_MARKS) ||
        ($reviewbits & OFFLINEQUIZ_REVIEW_SHEET) ||
        ($reviewbits & OFFLINEQUIZ_REVIEW_GRADEDSHEET)
    );
    if ($canreview && offlinequiz_results_open($offlinequiz)) {
        $myreviewurl = (new moodle_url('/mod/offlinequiz/review.php', [
            'q'        => $offlinequiz->id,
            'resultid' => $myresult->id,
        ]))->out(false);
    } else {
        $canreview = false;
    }
}

// ============================================================
// Personal stats for "My Copy" tab.
// ============================================================
$myrank         = 0;
$mypercentile   = 0;
$myscore        = 0;
$mymaxscore     = 0;
$mypercent      = 0;
$mypass         = false;
$myquestionstats = [];

if ($myresult) {
    $pos = 0;
    foreach ($allresults as $r) {
        $pos++;
        if ((int)$r->userid === (int)$USER->id) {
            $myrank = $pos;
            break;
        }
    }
    $totalall     = count($allresults);
    $mypercentile = $totalall > 1 ? round((($totalall - $myrank) / ($totalall - 1)) * 100) : 100;

    $mygroup    = $DB->get_record('offlinequiz_groups', ['id' => $myresult->offlinegroupid]);
    $mymaxscore = (float)($mygroup->sumgrades ?? 0);
    $myscore    = (float)$myresult->sumgrades;
    $mypercent  = $mymaxscore > 0 ? round(($myscore / $mymaxscore) * 100, 1) : 0;
    $mypass     = $mypercent >= 50;

    if (!empty($myresult->usageid) && !empty($questionrows)) {
        $myqrows = $DB->get_records_sql(
            "SELECT qa.questionid, qa.slot, qas.fraction
               FROM {question_attempts} qa
               JOIN {question_attempt_steps} qas ON qas.id = (
                   SELECT MAX(s2.id) FROM {question_attempt_steps} s2
                    WHERE s2.questionattemptid = qa.id)
              WHERE qa.questionusageid = :usageid
              ORDER BY qa.slot ASC",
            ['usageid' => $myresult->usageid]
        );
        $myqindex = [];
        foreach ($myqrows as $qr) {
            $myqindex[$qr->questionid] = (float)$qr->fraction;
        }

        $qn = 1;
        foreach ($questionrows as $qrow) {
            $frac = $myqindex[$qrow->questionid] ?? null;
            $myquestionstats[] = [
                'qnum'      => $qn,
                'mycorrect' => $frac !== null && $frac >= 1.0,
                'mypartial' => $frac !== null && $frac > 0.0 && $frac < 1.0,
                'mywrong'   => $frac !== null && $frac <= 0.0,
                'hasanswer' => $frac !== null,
                'classavg'  => round($qrow->total > 0 ? ($qrow->correct / $qrow->total) * 100 : 0, 1),
            ];
            $qn++;
        }
    }
}

// ============================================================
// Fire event.
// ============================================================
$event = \mod_offlinequiz\event\leaderboard_viewed::create([
    'objectid' => $offlinequiz->id,
    'context'  => $context,
]);
$event->trigger();

// ============================================================
// Build template context.
// ============================================================
$templatecontext = [
    // Meta.
    'quizname'       => format_string($offlinequiz->name),
    'cmid'           => $id,
    'viewurl'        => (new moodle_url('/mod/offlinequiz/view.php', ['id' => $id]))->out(false),

    // Active tab (passed via URL parameter).
    'activetab'        => $activetab,
    'tabisleaderboard' => ($activetab === 'leaderboard'),
    'tabisstatistics'  => ($activetab === 'statistics'),
    'tabismycopy'      => ($activetab === 'mycopy'),

    // Leaderboard tab.
    'filterurl'      => (new moodle_url('/mod/offlinequiz/leaderboard.php', ['id' => $id]))->out(false),
    'anonymous'      => $anonymous,
    'hasresults'     => !empty($rows),
    'rows'           => array_values($rows),
    'filteroptions'  => $filteroptions,
    'hasfilter'      => !empty($filteroptions),
    'filteredcohort'   => $filteredcohortid > 0 ? ($cohortmap[$filteredcohortid] ?? '') : '',
    'filteredcohortid' => $filteredcohortid,
    'isfiltered'       => ($filteredcohortid > 0),

    // Statistics tab.
    'hasstats'           => ($statscount > 0),
    'statscount'         => $statsdata['count']     ?? 0,
    'statsavg'           => $statsdata['avg']        ?? 0,
    'statsmedian'        => $statsdata['median']     ?? 0,
    'statsstddev'        => $statsdata['stddev']     ?? 0,
    'statsmin'           => $statsdata['min']        ?? 0,
    'statsmax'           => $statsdata['max']        ?? 0,
    'statspassrate'      => $statsdata['passrate']   ?? 0,
    'statspasscount'     => $statsdata['passcount']  ?? 0,
    'scoredistchart'     => $scoredistcharthtml,
    'questionchart'      => $questioncharthtml,
    'cohortchart'        => $cohortcharthtml,
    'hascohortchart'     => !empty($cohortcharthtml),
    'questionstats'      => $questionstats,
    'hasquestionstats'   => !empty($questionstats),

    // My copy tab.
    'myreviewurl'         => $myreviewurl,
    'canreview'           => $canreview,
    'hasmyresult'         => !empty($myresult),
    'myrank'              => $myrank,
    'mytotal'             => count($allresults),
    'mypercentile'        => $mypercentile,
    'myscore'             => format_float($myscore, 2),
    'mymaxscore'          => format_float($mymaxscore, 2),
    'mypercent'           => $mypercent,
    'mypass'              => $mypass,
    'hasmyquestionstats'  => !empty($myquestionstats),
    'myquestionstats'     => array_values($myquestionstats),
    'strmyscore'              => get_string('leaderboardmyscore', 'offlinequiz'),
    'strmyrank'               => get_string('leaderboardmyrank', 'offlinequiz'),
    'strmypercentile'         => get_string('leaderboardmypercentile', 'offlinequiz'),
    'strmyresult'             => get_string('leaderboardmyresult', 'offlinequiz'),
    'strmypass'               => get_string('leaderboardmypass', 'offlinequiz'),
    'strmyfail'               => get_string('leaderboardmyfail', 'offlinequiz'),
    'strmyquestionbreakdown'  => get_string('leaderboardmyquestionbreakdown', 'offlinequiz'),
    'strmyresultcol'          => get_string('leaderboardmyresultcol', 'offlinequiz'),
    'strclassavg'             => get_string('leaderboardclassavg', 'offlinequiz'),
    'strreviewnotavailable'   => get_string('leaderboardreviewnotavailable', 'offlinequiz'),

    // Strings — leaderboard.
    'strrank'        => get_string('leaderboardrank', 'offlinequiz'),
    'strname'        => get_string('leaderboardname', 'offlinequiz'),
    'struserkey'     => get_string('leaderboarduserkey', 'offlinequiz'),
    'strcohort'      => get_string('leaderboardcohort', 'offlinequiz'),
    'strscore'       => get_string('leaderboardscore', 'offlinequiz'),
    'strpercent'     => get_string('leaderboardpercent', 'offlinequiz'),
    'stryou'         => get_string('leaderboardyou', 'offlinequiz'),
    'strnoresults'   => get_string('leaderboardnoresults', 'offlinequiz'),
    'strfilterby'    => get_string('leaderboardfilterby', 'offlinequiz'),
    'strallcohorts'  => get_string('leaderboardallcohorts', 'offlinequiz'),
    'strresetfilter' => get_string('leaderboardresetfilter', 'offlinequiz'),

    // Strings — tabs.
    'strtableaderboard'  => get_string('leaderboard', 'offlinequiz'),
    'strtabstatistics'   => get_string('leaderboardtabstatistics', 'offlinequiz'),
    'strtabmycopy'       => get_string('leaderboardtabmycopy', 'offlinequiz'),

    // Strings — statistics.
    'strparticipants'    => get_string('leaderboardstatsparticipants', 'offlinequiz'),
    'stravg'             => get_string('leaderboardstatsavg', 'offlinequiz'),
    'strmedian'          => get_string('leaderboardstatsmedian', 'offlinequiz'),
    'strstddev'          => get_string('leaderboardstatsstddev', 'offlinequiz'),
    'strmin'             => get_string('leaderboardstatsmin', 'offlinequiz'),
    'strmax'             => get_string('leaderboardstatsmax', 'offlinequiz'),
    'strpassrate'        => get_string('leaderboardstatspassrate', 'offlinequiz'),
    'strqnum'            => get_string('leaderboardstatsqnum', 'offlinequiz'),
    'strqtotal'          => get_string('leaderboardstatsqtotal', 'offlinequiz'),
    'strcorrect'         => get_string('leaderboardstatscorrect', 'offlinequiz'),
    'strpartial'         => get_string('leaderboardstatspartial', 'offlinequiz'),
    'strwrong'           => get_string('leaderboardstatswrong', 'offlinequiz'),
    'strnoresult'        => get_string('leaderboardstatsnoresult', 'offlinequiz'),
    'strviewcopy'        => get_string('leaderboardstatsviewcopy', 'offlinequiz'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_offlinequiz/leaderboard', $templatecontext);
echo $OUTPUT->footer();
