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
 * Upgrade script for the offlinequiz module
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

/**
 * upgrade function for offlinequiz
 * @param mixed $oldversion
 * @return bool
 */
function xmldb_offlinequiz_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015112002) {
        // Define field questionfilename to be added to offlinequiz_groups.
        $table = new xmldb_table('offlinequiz_groups');
        $field = new xmldb_field('questionfilename', XMLDB_TYPE_CHAR, '1000', null, null, null, null, 'templateusageid');

        // Conditionally launch add field questionfilename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('answerfilename', XMLDB_TYPE_CHAR, '1000', null, null, null, null, 'questionfilename');

        // Conditionally launch add field answerfilename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('correctionfilename', XMLDB_TYPE_CHAR, '1000', null, null, null, null, 'answerfilename');

        // Conditionally launch add field correctionfilename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_update_form_file_names();

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015112002, 'offlinequiz');
    }

    if ($oldversion < 2015112007) {
        // Define field printstudycodefield to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('printstudycodefield', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'shuffleanswers');

        // Conditionally launch add field printstudycodefield.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015112007, 'offlinequiz');
    }

    if ($oldversion < 2016042100) {
        // Define field showquestioninfo to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('showquestioninfo', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'fileformat');

        // Conditionally launch add field showquestioninfo.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2016042100, 'offlinequiz');
    }

    if ($oldversion < 2016101700) {
        print('<div class="alert alert-block"><span>
              Due to a bug in the offline-quiz module,
              answer forms with multiple pages were not recognized properly.
              Therefore, the number of pages has to be re-calculated for each offline-quiz.
              This may take a while, depending on the number offline-quizzes in your Moodle platform.
              </span>
              </div>' );
        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_update_refresh_all_pagecounts();
        upgrade_mod_savepoint(true, 2016101700, 'offlinequiz');
    }

    // Information about the new Cron-Job in Moodle-API.
    if ($oldversion < 2017020201) {
        global $PAGE;
        global $OUTPUT;
        if (!optional_param('croninfo_read', false, PARAM_BOOL)) {
            if (!CLI_SCRIPT) {
                print('
                <div class="alert alert-block"><span>
                The offline quiz cron works now with the Cron-API. This means, that the additional cronjob is not needed anymore.
                If you configured a cronjob for the Cron-API you have either the option to disable the job in the Cron-API
                or disable your own cron, which is only needed, if you run the evaluation on a dedicated server.
                For more information read chapter III of the README.md, which comes with the plugin.<br></span>
                <br><b>Continuing the upgrade:<br></b>
                If you have read and understood this message click the link below to continue the upgrade.
                <br>
                <br><b><a href=' . $PAGE->url->__toString() . '&croninfo_read=true> CONTINUE </a> </b>
                <br>
                </div>' );
                echo $OUTPUT->footer();
                die;
                return false;
            } else {
                print('The offline quiz cron works now with the Cron-API.'
                      . ' This means, that the additional cronjob is not needed anymore.'
                      . ' If you configured a cronjob for the Cron-API'
                      . ' you have either the option to disable the job in the Cron-API'
                      . ' or disable your own cron, which is only needed, if you run the evaluation on a dedicated server.'
                      . ' For more information read chapter III of the README.md, which comes with the plugin.');
            }
        }
        upgrade_mod_savepoint(true, 2017020201, 'offlinequiz');
    }

    // Add id_digits containing amount of digits to match idnumber against.
    if ($oldversion < 2017020202) {
        // Define field id_digits to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('id_digits', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'showtutorial');

        // Conditionally launch add field id_digits.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $DB->set_field('offlinequiz', 'id_digits', get_config('offlinequiz', 'ID_digits'));

            // Offlinequiz savepoint reached.
            upgrade_mod_savepoint(true, 2017020202, 'offlinequiz');
        }
    }
    if ($oldversion < 2017042501) {
        // Changing precision of field pagenumber on table offlinequiz_scanned_pages to (20).
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $field = new xmldb_field('pagenumber', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'userkey');

        // Launch change of precision for field pagenumber.
        $dbman->change_field_precision($table, $field);

        // Define field info to be added to offlinequiz_queue_data.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('info', XMLDB_TYPE_TEXT, null, null, null, null, null, 'error');

        // Conditionally launch add field info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2017042501, 'offlinequiz');
    }
    if ($oldversion < 2017081102) {
        $table = new xmldb_table('offlinequiz_page_corners');
        $index = new xmldb_index('offlinequiz_page_corners_scannedpageid_idx', XMLDB_INDEX_NOTUNIQUE, ['scannedpageid']);
        // Conditionally launch add index offlinequiz_userid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $index = new xmldb_index('offlinequiz_scanned_pages_resultid_idx', XMLDB_INDEX_NOTUNIQUE, ['resultid']);
        // Conditionally launch add index offlinequiz_userid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, 2017081102, 'offlinequiz');
    }

    if ($oldversion < 2018011601) {
        // Define field id_digits to be added to offlinequiz, if not defined.
        // This might miss due to an error in an old moodle-version.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('id_digits', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'showtutorial');

        // Conditionally launch add field id_digits.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $DB->set_field('offlinequiz', 'id_digits', get_config('offlinequiz', 'ID_digits'));
        }

        // Define field disableimgnewlines to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('disableimgnewlines', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'id_digits');

        // Conditionally launch add field disableimgnewlines.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2018011601, 'offlinequiz');
    }
    if ($oldversion < 2018100300) {
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field(
            'algorithmversion',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'disableimgnewlines'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018100300, 'offlinequiz');
    }

    if ($oldversion < 2018112700) {
        // Define index offlinequiz_userid_idx (not unique) to be added to offlinequiz_results.
        $table = new xmldb_table('offlinequiz_choices');
        $index1 = new xmldb_index('offlinequiz_choices_slotnumber_idx', XMLDB_INDEX_NOTUNIQUE, ['slotnumber']);
        $index2 = new xmldb_index('offlinequiz_choices_choicenumber_idx', XMLDB_INDEX_NOTUNIQUE, ['choicenumber']);

        if (!$dbman->index_exists($table, $index1)) {
            $dbman->add_index($table, $index1);
        }
        if (!$dbman->index_exists($table, $index2)) {
            $dbman->add_index($table, $index2);
        }
        $sql = 'SELECT c1.id
                FROM   {offlinequiz_choices} c1,
                       {offlinequiz_choices} c2
                WHERE  c1.scannedpageid = c2.scannedpageid
                AND    c1.slotnumber = c2.slotnumber
                AND    c1.choicenumber = c2.choicenumber
                AND    c1.id < c2.id';
        $idstodelete = $DB->get_fieldset_sql($sql);
        if ($idstodelete) {
            $chunks = array_chunk($idstodelete, 250, true);
            $i = 1;
            echo "Delete choices in " . count($chunks) . " chunks of 250 entries..." . PHP_EOL;
            foreach ($chunks as $curchunk) {
                echo "Delete chunk " . ($i++) . " of " . count($chunks) . "!" . PHP_EOL;
                [$querysql, $queryparams] = $DB->get_in_or_equal($curchunk);
                $DB->delete_records_select('offlinequiz_choices', 'id ' .  $querysql, $queryparams);
            }
        }

        upgrade_mod_savepoint(true, 2018112700, 'offlinequiz');
    }
    if ($oldversion < 2018121100) {
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field(
            'experimentalevaluation',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'algorithmversion'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018121100, 'offlinequiz');
    }

    if ($oldversion < 2019050800) {
        // Rename field groupnumber on table offlinequiz_groups to NEWNAMEGOESHERE.
        $table = new xmldb_table('offlinequiz_groups');
        $field = new xmldb_field('number', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'offlinequizid');

        // Launch rename field groupnumber.
        $dbman->rename_field($table, $field, 'groupnumber');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2019050800, 'offlinequiz');
    }
    if ($oldversion < 2019050801) {
        // Define field id to be added to offlinequiz_p_lists.
        $table = new xmldb_table('offlinequiz_p_lists');
        $field = new xmldb_field('number', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'offlinequizid');

        // Launch rename field groupnumber.
        $dbman->rename_field($table, $field, 'listnumber');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2019050801, 'offlinequiz');
    }
    if ($oldversion < 2019051401) {
        // Changing type of field info on table offlinequiz_scanned_pages to char.
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $field = new xmldb_field('info', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'error');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        // Changing type of field status on table offlinequiz_scanned_p_pagesto char.
        $table = new xmldb_table('offlinequiz_scanned_p_pages');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'time');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        // Changing type of field error on table offlinequiz_scanned_p_pagesto char.
        $table = new xmldb_table('offlinequiz_scanned_p_pages');
        $field = new xmldb_field('error', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'status');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        // Changing type of field status on table offlinequiz_queue_data to char.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'filename');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        // Changing type of field error on table offlinequiz_queue_data to char.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('error', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'status');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        // Changing type of field info on table offlinequiz_queue_data to char.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('info', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'error');

        // Launch change of type for field info.
        $dbman->change_field_type($table, $field);

        upgrade_mod_savepoint(true, 2019051401, 'offlinequiz');
    }

    if ($oldversion < 2020051200) {
        // Define table offlinequiz_attempts to be dropped.
        $table = new xmldb_table('offlinequiz_attempts');

        // Conditionally launch drop table for offlinequiz_attempts.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2020051200, 'offlinequiz');
    }

    if ($oldversion < 2021070801.01) {
        // Define field completionpass to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('completionpass', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'experimentalevaluation');

        // Conditionally launch add field completionpass.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2021070801.01, 'offlinequiz');
    }

    if ($oldversion < 2021070801.03) {
        // Changing precision of field info on table offlinequiz_scanned_pages to (255).
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $field = new xmldb_field('info', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'error');

        // Launch change of precision for field info.
        $dbman->change_field_precision($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2021070801.03, 'offlinequiz');
    }
    if ($oldversion < 2023022000) {
        // Define field participantsusage to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('participantsusage', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'grade');

        // Conditionally launch add field participantsusage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2023022000, 'offlinequiz');
    }
    if ($oldversion < 2023092600) {
        // Define field documentquestionid to be added to offlinequiz_group_questions.
        $table = new xmldb_table('offlinequiz_group_questions');
        $field = new xmldb_field('documentquestionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'maxmark');

        // Conditionally launch add field documentquestionid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2023092600, 'offlinequiz');
    }

    if ($oldversion < 2023102104) {
        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_fix_question_versions();
        upgrade_mod_savepoint(true, 2023102104, 'offlinequiz');
    }
    if ($oldversion < 2024012200) {
        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        $subquery = "SELECT qr.id AS rid
                          FROM {question_references} qr
                     LEFT JOIN {context} c ON qr.usingcontextid = c.id
                         WHERE qr.component = 'mod_offlinequiz'
                           AND c.id IS NULL";
        $DB->delete_records_subquery('question_references', 'id', 'rid', $subquery);
        upgrade_mod_savepoint(true, 2024012200, 'offlinequiz');
    }
    if ($oldversion < 2024012202) {
        $subquery = "SELECT ogq.id AS rid
                          FROM {offlinequiz_group_questions} ogq
                     LEFT JOIN {offlinequiz} o ON ogq.offlinequizid = o.id
                         WHERE o.id IS NULL";
        $DB->delete_records_subquery('offlinequiz_group_questions', 'id', 'rid', $subquery);
        upgrade_mod_savepoint(true, 2024012202, 'offlinequiz');
    }
    if ($oldversion < 2024041900) {
        // Changing type of field participantsusage on table offlinequiz to int.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('participantsusage', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'grade');
        // Launch change of type for field participantsusage.
        $dbman->change_field_type($table, $field);
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041900, 'offlinequiz');
    }
    if ($oldversion < 2024041901) {
        // Changing type of field participantsusage on table offlinequiz to int.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('participantsusage', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'grade');
        // Launch change of type for field participantsusage.
        $dbman->change_field_default($table, $field);
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041901, 'offlinequiz');
    }
    if ($oldversion < 2024041902) {
        // Changing nullability of field algorithmversion on table offlinequiz to null.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('algorithmversion', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'disableimgnewlines');
        // Launch change of nullability for field algorithmversion.
        $dbman->change_field_notnull($table, $field);
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041902, 'offlinequiz');
    }
    if ($oldversion < 2024041903) {
        // Changing the default of field listnumber on table offlinequiz_p_lists to 1.
        $table = new xmldb_table('offlinequiz_p_lists');
        $field = new xmldb_field('listnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'name');
        // Launch change of default for field listnumber.
        $dbman->change_field_type($table, $field);
        $dbman->change_field_default($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041903, 'offlinequiz');
    }
    if ($oldversion < 2024041904) {
        // Changing nullability of field status on table offlinequiz_scanned_p_pages to not null.
        $table = new xmldb_table('offlinequiz_scanned_p_pages');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'time');
        $DB->set_field('offlinequiz_scanned_p_pages', 'status', 'ok', ['status' => null]);
        // Launch change of nullability for field status.
        $dbman->change_field_notnull($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041904, 'offlinequiz');
    }
    if ($oldversion < 2024041905) {
        // Changing type of field status on table offlinequiz_queue to char.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'timefinish');

        // Launch change of type for field status.
        $dbman->change_field_type($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041905, 'offlinequiz');
    }
    if ($oldversion < 2024041906) {
        // Changing type of field status on table offlinequiz_queue_data to char.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'filename');
        $DB->set_field('offlinequiz_queue_data', 'status', 'new', ['status' => null]);
        // Launch change of type for field status.
        $dbman->change_field_notnull($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024041906, 'offlinequiz');
    }
    if ($oldversion < 2024043002) {
        // Define field pdffont to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('pdffont', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'printstudycodefield');

        // Conditionally launch add field pdffont.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024043002, 'offlinequiz');
    }
    if ($oldversion < 2024072500) {
        // Define field queuedataid to be added to offlinequiz_scanned_pages.
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $field = new xmldb_field('queuedataid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'resultid');

        // Conditionally launch add field queuedataid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024072500, 'offlinequiz');
    }

    if ($oldversion < 2024072600) {
        // Define field filename to be added to offlinequiz_queue.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('filename', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'status');

        // Conditionally launch add field filename.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024072600, 'offlinequiz');
    }
    if ($oldversion < 2024073001) {
        // Define field errormessage to be added to offlinequiz_queue.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('error', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'status');

        // Conditionally launch add field errormessage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2024073001, 'offlinequiz');
    }
    if ($oldversion < 2025020100) {
        // Define field pdffont to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('pdffont', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'printstudycodefield');

        // Conditionally launch add field pdffont.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2025020100, 'offlinequiz');
    }
    if ($oldversion < 2025062400) {
        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_fix_question_references();
        upgrade_mod_savepoint(true, 2025062400, 'offlinequiz');
    }
    if ($oldversion < 2025062600) {
        // Name of the plugin you want to uninstall.
        $plugincomponent = 'offlinequiz_regrade';

        // Check if plugin is installed.
        if (core_plugin_manager::instance()->get_plugin_info($plugincomponent)) {
            uninstall_plugin('offlinequiz', 'regrade');
        }

        // Always upgrade the savepoint.
        upgrade_mod_savepoint(true, 2025062600, 'offlinequiz');
    }
    if ($oldversion < 2025080402) {
        // Define field errormessage to be added to offlinequiz_queue.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('error', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'status');

        // Conditionally launch add field errormessage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2025080402, 'offlinequiz');
    }

    return true;
}
