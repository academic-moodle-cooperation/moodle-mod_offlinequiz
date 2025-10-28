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
 * Code for upgrading Moodle 1.9.x offlinequizzes to Moodle 2.2+
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/modinfolib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');
require_once($CFG->dirroot . '/question/engine/upgrade/logger.php');
require_once($CFG->dirroot . '/question/engine/upgrade/behaviourconverters.php');
require_once($CFG->dirroot . '/question/engine/upgrade/upgradelib.php');

/**
 * Updates the new field names (questionfilename, answerfilename, correctionfilename) in the table
 * offlinequiz_groups for old offline quizzes.
 */
function offlinequiz_update_form_file_names() {
    global $DB;

    $offlinequizzes = $DB->get_records('offlinequiz');

    if (empty($offlinequizzes)) {
        return;
    }

    $progressbar = new progress_bar('filenameupdate');
    $progressbar->create();
    $done = 0;
    $outof = count($offlinequizzes);
    $fs = get_file_storage();
    $letterstr = 'abcdefghijkl';

    $a = new stdClass();
    $a->done = $done;
    $a->outof = $outof;
    $progressbar->update($done, $outof, get_string('upgradingfilenames', 'offlinequiz', $a));

    foreach ($offlinequizzes as $offlinequiz) {
        $cm = get_coursemodule_FROM_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course);
        $context = context_module::instance($cm->id);

        $files = $fs->get_area_files($context->id, 'mod_offlinequiz', 'pdfs');
        $groups = $DB->get_records(
            'offlinequiz_groups',
            ['offlinequizid' => $offlinequiz->id],
            'number',
            '*',
            0,
            $offlinequiz->numgroups
        );
        // Simply load all files in the 'pdfs' filearea in a ZIP file.

        foreach ($groups as $group) {
            $groupletter = $letterstr[$group->number - 1];

            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($filename != '.') {
                    if (0 === strpos($filename, 'form-' . strtolower($groupletter))) {
                        $group->questionfilename = $filename;
                    } else if (0 === strpos($filename, 'answer-' . strtolower($groupletter))) {
                        $group->answerfilename = $filename;
                    } else if (0 === strpos($filename, 'correction-' . strtolower($groupletter))) {
                        $group->correctionfilename = $filename;
                    }
                }
            }
            $DB->update_record('offlinequiz_groups', $group);
        }
        $done += 1;
        $a->done = $done;
        $a->info = $offlinequiz->id;
        $progressbar->update($done, $outof, get_string('upgradingfilenames', 'offlinequiz', $a));
    }
}

/**
 * update refresh all pagecounts
 * @return void
 */
function offlinequiz_update_refresh_all_pagecounts() {
    global $DB;
    $groups = $DB->get_records('offlinequiz_groups');
    if (empty($groups)) {
        return;
    }
    $progressbar = new progress_bar('pagenumberupdate');
    $progressbar->create();
    $done = 0;
    $outof = count($groups);

    $a = new stdClass();
    $a->done = $done;
    $a->outof = $outof;
    $progressbar->update($done, $outof, get_string('pagenumberupdate', 'offlinequiz', $a));
    foreach ($groups as $group) {
        $params = ['id' => $group->id];
        $sql = "SELECT count(*)
                FROM   {question} q,
                       {offlinequiz_group_questions} gq
                WHERE  gq.offlinegroupid = :id
                AND    gq.questionid = q.id
                AND    qtype <> 'description'";
        $questions = $DB->get_field_sql($sql, $params);
        $sql = "SELECT max(count)
                FROM  (
                       SELECT count(*) as count
                       FROM   {question_answers} qa,
                              {offlinequiz_groups} g,
                              {offlinequiz_group_questions} gq
                       WHERE  gq.offlinegroupid = g.id
                       AND    gq.questionid = qa.question
                       AND    g.id = :id
                       GROUP BY gq.id
                      ) as count";
        $maxanswers = $DB->get_field_sql($sql, $params);
        $columns = offlinequiz_get_number_of_columns($maxanswers);
        $pages = offlinequiz_get_number_of_pages($questions, $columns);
        if ($pages > 1 && $pages != $group->numberofpages) {
            $group->numberofpages = $pages;
            $DB->update_record('offlinequiz_groups', $group);
        }
        $done++;
        $progressbar->update($done, $outof, get_string('pagenumberupdate', 'offlinequiz', $a));
    }
}
/**
 * get number of columns
 * @param mixed $maxanswers
 * @return int
 */
function offlinequiz_get_number_of_columns($maxanswers) {
    $i = 1;
    $columnlimits = [1 => 13, 2 => 8, 3 => 6];
    while (array_key_exists($i, $columnlimits) && $columnlimits[$i] > $maxanswers) {
        $i++;
    }
    return $i;
}
/**
 * get number of pages
 * @param mixed $questions
 * @param mixed $columns
 * @return float
 */
function offlinequiz_get_number_of_pages($questions, $columns) {
    return ceil($questions / $columns / 24);
}
/**
 * offlinequiz fix question versions
 * @return void
 */
function offlinequiz_fix_question_versions() {
    global $DB;
    // First set all.
    $sql = "SELECT DISTINCT gq1.id,gq1.offlinegroupid,  gq2.questionid
                       FROM {offlinequiz_group_questions} gq1
                       JOIN {question_versions} qv1 ON qv1.questionid = gq1.questionid
                       JOIN {question_versions} qv2 ON qv2.questionbankentryid = qv1.questionbankentryid
                                                    AND qv1.version < qv2.version
                       JOIN {offlinequiz_group_questions} gq2 ON gq2.questionid = qv2.questionid
                                                             AND gq2.offlinequizid = gq1.offlinequizid";
    $records = $DB->get_records_sql($sql);
    foreach ($records as $record) {
        $DB->set_field('offlinequiz_group_questions', 'questionid', $record->questionid, ['id' => $record->id]);
    }
    $sql = "SELECT qr.id,qv.version FROM {question_references} qr
              JOIN {offlinequiz_group_questions} ogq on ogq.id = qr.itemid
              JOIN {question_versions} qv on qv.questionid = ogq.questionid
              JOIN {question_bank_entries} mbe on mbe.id = qv.questionbankentryid
             WHERE component = 'mod_offlinequiz' and questionarea = 'slot'
               AND qr.version is null or qr.version <> qv.version";
    $records = $DB->get_records_sql($sql);
    foreach ($records as $record) {
        $DB->set_field('question_references', 'version', $record->version, ['id' => $record->id]);
    }

    $sql = "SELECT ogq.id groupquestionid, og.templateusageid templateusageid,
                   qa.id questionattemtid, qa.questionid oldquestionid, ogq.questionid newquestionid
              FROM {offlinequiz_groups} og
              JOIN {question_usages} qu on qu.id = og.templateusageid
              JOIN {offlinequiz_group_questions} ogq on og.id = ogq.offlinegroupid
              JOIN {question_versions} oqv on ogq.questionid = oqv.questionid
              JOIN {question_attempts} qa on qa.questionusageid = qu.id
              JOIN {question_versions} tqv on tqv.questionid = qa.questionid and tqv.questionbankentryid = oqv.questionbankentryid
             WHERE qa.questionid <> ogq.questionid";
    $records = $DB->get_records_sql($sql);
    foreach ($records as $record) {
        $templateusage = question_engine::load_questions_usage_by_activity($record->templateusageid);
        $oldquestionanswers = $DB->get_records('question_answers', ['question' => $record->oldquestionid]);
        $newquestionanswers = array_values($DB->get_records('question_answers', ['question' => $record->newquestionid]));
        $sql = "SELECT qasd.id AS id, qasd.value AS value
                FROM {question_attempt_step_data} qasd
                JOIN {question_attempt_steps} qas ON qas.id = qasd.attemptstepid
                JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
               WHERE qa.questionusageid = :qubaid
                 AND qa.questionid = :questionid
                 AND qasd.name = '_order'";
        $value = $DB->get_record_sql($sql, ['qubaid' => $templateusage->get_id(), 'questionid' => $record->oldquestionid]);
        $values = explode(',', $value->value);
        $replace = [];
        $i = 0;
        foreach ($oldquestionanswers as $oldquestionanswer) {
            $replace[$oldquestionanswer->id] = $newquestionanswers[$i]->id;
            $i++;
        }
        for ($i = 0; $i < count($values); $i++) {
            $values[$i] = $replace[$values[$i]];
        }
        $values = implode(',', $values);
        $DB->set_field('question_attempt_step_data', 'value', $values, ['id' => $value->id]);
        $DB->set_field(
            'question_attempts',
            'questionid',
            $record->newquestionid,
            ['questionid' => $record->oldquestionid, 'questionusageid' => $templateusage->get_id()]
        );
    }
    offlinequiz_fix_question_references();
}
/**
 * fix question references
 * @return void
 */
function offlinequiz_fix_question_references() {
    global $DB;
    $sql = "SELECT ogq.id itemid, c.id usingcontextid, 'mod_offlinequiz' component,
                  'slot' questionarea,  qv.questionbankentryid questionbankentryid, qv.version \"version\"
              FROM {offlinequiz_group_questions} ogq
              JOIN {modules} m ON m.name ='offlinequiz'
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = ogq.offlinequizid
              JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = '70'
              JOIN {question_versions} qv ON qv.questionid = ogq.questionid
         LEFT JOIN {question_references} mqr on component = 'mod_offlinequiz' AND questionarea = 'slot' AND itemid = ogq.id
             WHERE mqr.id is null";
    $sql2 = "INSERT INTO {question_references} (itemid, usingcontextid, component,
                                                        questionarea, questionbankentryid, version) ($sql LIMIT 10000)";
    $thiscount = $DB->count_records('question_references');
    $lastcount = -1;
    try {
        while ($thiscount > $lastcount) {
            $DB->execute($sql2);
            $lastcount = $thiscount;
            $thiscount = $DB->count_records('question_references');
        }
    } catch (Exception $e) {
        // Database doesn't support this type of insert, we have to get them out of the databse and insert them manually.
        while ($records = $DB->get_records_sql($sql, [], 0, 10000)) {
            $DB->insert_records('question_references', $records);
        }
    }
}
