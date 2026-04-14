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

$id              = required_param('id', PARAM_INT);
$filteredcohortid = optional_param('cohortid', 0, PARAM_INT);

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

// Fetch all completed results for this quiz.
$sql = "SELECT r.id, r.userid, r.sumgrades, og.sumgrades AS maxgrades,
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

// Build a map of userid => cohorts [{id, name}] for all users in the result set.
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

// Collect distinct cohorts present in the result set for the filter dropdown.
$cohortmap = []; // cohortid => cohortname
foreach ($results as $result) {
    foreach ($usercohorts[(int)$result->userid] ?? [] as $c) {
        $cohortmap[$c['id']] = $c['name'];
    }
}
asort($cohortmap);

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

// Build rows with rank recomputed within the current (possibly filtered) result set.
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

$templatecontext = [
    'quizname'       => format_string($offlinequiz->name),
    'cmid'           => $id,
    'viewurl'        => (new moodle_url('/mod/offlinequiz/view.php', ['id' => $id]))->out(false),
    'filterurl'      => (new moodle_url('/mod/offlinequiz/leaderboard.php', ['id' => $id]))->out(false),
    'anonymous'      => $anonymous,
    'hasresults'     => !empty($rows),
    'rows'           => array_values($rows),
    'filteroptions'  => $filteroptions,
    'hasfilter'      => !empty($filteroptions),
    'filteredcohort' => $filteredcohortid > 0 ? ($cohortmap[$filteredcohortid] ?? '') : '',
    'isfiltered'     => ($filteredcohortid > 0),
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
];

$event = \mod_offlinequiz\event\leaderboard_viewed::create([
    'objectid' => $offlinequiz->id,
    'context'  => $context,
]);
$event->trigger();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_offlinequiz/leaderboard', $templatecontext);
echo $OUTPUT->footer();
