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
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();


function xmldb_offlinequiz_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // And upgrade begins here. For each one, you'll need one
    // Block of code similar to the next one. Please, delete
    // This comment lines once this file start handling proper
    // Upgrade code.

    // ONLY UPGRADE FROM Moodle 1.9.x (module version 2009042100) is supported.

    if ($oldversion < 2009120700) {

        // Define field counter to be added to offlinequiz_i_log.
        $table = new xmldb_table('offlinequiz_i_log');
        $field = new xmldb_field('counter');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'rawdata');

        // Launch add field counter.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field corners to be added to offlinequiz_i_log.
        $field = new xmldb_field('corners');
        $field->set_attributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, 'counter');

        // Launch add field corners.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field pdfintro to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('pdfintro');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'intro');

        // Launch add field pdfintro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2009120700, 'offlinequiz');
    }

    if ($oldversion < 2010082900) {

        // Define table offlinequiz_p_list to be created.
        $table = new xmldb_table('offlinequiz_p_list');

        // Adding fields to table offlinequiz_p_list.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequiz', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->add_field('list', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // Adding keys to table offlinequiz_p_list.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Launch create table for offlinequiz_p_list.
        $dbman->create_table($table);

        // Define field position to be dropped from offlinequiz_participants.
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('position');

        // Launch drop field position.
        $dbman->drop_field($table, $field);

        // Define field page to be dropped from offlinequiz_participants.
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('page');

        // Launch drop field page.
        $dbman->drop_field($table, $field);

        // Define field list to be added to offlinequiz_participants.
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('list');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'userid');

        // Launch add field list.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2010082900, 'offlinequiz');
    }

    if ($oldversion < 2010090600) {

        // Define index offlinequiz (not unique) to be added to offlinequiz_p_list.
        $table = new xmldb_table('offlinequiz_p_list');
        $index = new XMLDBIndex('offlinequiz');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('offlinequiz'));

        // Launch add index offlinequiz.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('list');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('list'));

        // Launch add index list.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index offlinequiz (not unique) to be added to offlinequiz_participants.
        $table = new xmldb_table('offlinequiz_participants');
        $index = new XMLDBIndex('offlinequiz');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('offlinequiz'));

        // Launch add index offlinequiz.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('list');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('list'));

        // Launch add index list.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('userid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch add index list.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2010090600, 'offlinequiz');
    }

    if ($oldversion < 2011021400) {

        // Define field fileformat to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('fileformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Launch add field fileformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2011021400, 'offlinequiz');
    }

    if ($oldversion < 2011032900) {

        // Define field page to be added to offlinequiz_i_log.
        $table = new xmldb_table('offlinequiz_i_log');
        $field = new xmldb_field('page');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'corners');

        // Launch add field page.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field username to be added to offlinequiz_i_log.
        $field = new xmldb_field('username');
        $field->set_attributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'page');

        // Launch add field username.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index username (not unique) to be added to offlinequiz_i_log.
        $index = new XMLDBIndex('username');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('username'));

        // Launch add index username.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field showgrades to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('showgrades');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'fileformat');

        // Launch add field showgrades.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2011032900, 'offlinequiz');
    }

    if ($oldversion < 2011081700) {
        // Define field showtutorial to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('showtutorial');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'showgrades');

        // Launch add field showtutorial.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2011081700, 'offlinequiz');
    }

    // ------------------------------------------------------
    // UPGRADE for Moodle 2.0 module starts here.
    // ------------------------------------------------------
    // First we do the changes to the main table 'offlinequiz'.
    // ------------------------------------------------------
    if ($oldversion < 2012010100) {

        // Define field docscreated to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('docscreated', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null,
                                 '0', 'questionsperpage');

        // Conditionally launch add field docscreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010100, 'offlinequiz');
    }

    // Fill the new field docscreated.
    if ($oldversion < 2012010101) {

        $offlinequizzes = $DB->get_records('offlinequiz');
        foreach ($offlinequizzes as $offlinequiz) {
            $dirname = $CFG->dataroot . '/' . $offlinequiz->course . '/moddata/offlinequiz/' . $offlinequiz->id . '/pdfs';
            // If the answer pdf file for group 1 exists then we have created the documents.
            if (file_exists($dirname . '/answer-a.pdf')) {
                $DB->set_field('offlinequiz', 'docscreated', 1, array('id' => $offlinequiz->id));
            }
        }
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010101, 'offlinequiz');
    }

    if ($oldversion < 2012010105) {

        // Define table offlinequiz_reports to be created.
        $table = new xmldb_table('offlinequiz_reports');

        // Adding fields to table offlinequiz_reports.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('displayorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('lastcron', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('cron', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('capability', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table offlinequiz_reports.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_reports.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        if (!$DB->get_records_sql("SELECT * FROM {offlinequiz_reports} WHERE name = 'overview'", array())) {
            $record = new stdClass();
            $record->name         = 'overview';
            $record->displayorder = '10000';
            $DB->insert_record('offlinequiz_reports', $record);
        }
        if (!$DB->get_records_sql("SELECT * FROM {offlinequiz_reports} WHERE name = 'rimport'", array())) {
            $record = new stdClass();
            $record->name         = 'rimport';
            $record->displayorder = '9000';
            $DB->insert_record('offlinequiz_reports', $record);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010105, 'offlinequiz');
    }

    // Now we create all the new tables.
    // Create table offlinequiz_groups.
    if ($oldversion < 2012010200) {

        echo $OUTPUT->notification('Creating new tables', 'notifysuccess');

        // Define table offlinequiz_groups to be created.
        $table = new xmldb_table('offlinequiz_groups');

        // Adding fields to table offlinequiz_groups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('sumgrades', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('numberofpages', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('templateusageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table offlinequiz_groups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_groups.
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_groups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010200, 'offlinequiz');
    }

    // Create table offlinequiz_group_questions.
    if ($oldversion < 2012010300) {

        // Define table offlinequiz_group_questions to be created.
        $table = new xmldb_table('offlinequiz_group_questions');

        // Adding fields to table offlinequiz_group_questions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('offlinegroupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('position', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('pagenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('usageslot', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table offlinequiz_group_questions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_group_questions.
        $table->add_index('offlinequiz', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_group_questions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010300, 'offlinequiz');
    }

    if ($oldversion < 2012010400) {

        // Define table offlinequiz_scanned_pages to be created.
        $table = new xmldb_table('offlinequiz_scanned_pages');

        // Adding fields to table offlinequiz_scanned_pages.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('resultid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('warningfilename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('groupnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('userkey', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('pagenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('error', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        // Adding keys to table offlinequiz_scanned_pages.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_scanned_pages.
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_scanned_pages.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010400, 'offlinequiz');
    }

    if ($oldversion < 2012010500) {

        // Define table offlinequiz_choices to be created.
        $table = new xmldb_table('offlinequiz_choices');

        // Adding fields to table offlinequiz_choices.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedpageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('slotnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('choicenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table offlinequiz_choices.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_choices.
        $table->add_index('scannedpageid', XMLDB_INDEX_NOTUNIQUE, array('scannedpageid'));

        // Conditionally launch create table for offlinequiz_choices.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010500, 'offlinequiz');
    }

    if ($oldversion < 2012010600) {

        // Define table offlinequiz_page_corners to be created.
        $table = new xmldb_table('offlinequiz_page_corners');

        // Adding fields to table offlinequiz_page_corners.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedpageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('x', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('y', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('position', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table offlinequiz_page_corners.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_page_corners.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010600, 'offlinequiz');
    }

    if ($oldversion < 2012010700) {

        // Define table offlinequiz_results to be created.
        $table = new xmldb_table('offlinequiz_results');

        // Adding fields to table offlinequiz_results.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('offlinegroupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sumgrades', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('usageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('attendant', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('timefinish', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('preview', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, null, null, '0');

        // Adding keys to table offlinequiz_results.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_results.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010700, 'offlinequiz');
    }

    if ($oldversion < 2012010800) {

        // Define table offlinequiz_scanned_p_pages to be created.
        $table = new xmldb_table('offlinequiz_scanned_p_pages');

        // Adding fields to table offlinequiz_scanned_p_pages.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('listnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null);
        $table->add_field('error', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

        // Adding keys to table offlinequiz_scanned_p_pages.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_scanned_p_pages.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010800, 'offlinequiz');
    }

    if ($oldversion < 2012010900) {

        // Define table offlinequiz_p_choices to be created.
        $table = new xmldb_table('offlinequiz_p_choices');

        // Adding fields to table offlinequiz_p_choices.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedppageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table offlinequiz_p_choices.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_p_choices.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012010900, 'offlinequiz');
    }

    if ($oldversion < 2012011000) {

        // Define table offlinequiz_p_lists to be created.
        $table = new xmldb_table('offlinequiz_p_lists');

        // Adding fields to table offlinequiz_p_lists.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);

        // Adding keys to table offlinequiz_p_lists.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_p_lists.
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_p_lists.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012011000, 'offlinequiz');
    }

    // ------------------------------------------------------
    // New we rename fields in old tables.
    // ------------------------------------------------------

    // Rename fields in offlinequiz_queue table.
    if ($oldversion < 2012020100) {

        echo $OUTPUT->notification('Renaming fields in old tables.', 'notifysuccess');

        // Rename field offlinequiz on table offlinequiz_queue to NEWNAMEGOESHERE.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('offlinequiz');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timefinish');

        // Launch rename field offlinequiz.
        $dbman->rename_field($table, $field, 'offlinequizid');

        $field = new xmldb_field('importadmin');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'id');

        // Launch rename field importadmin.
        $dbman->rename_field($table, $field, 'importuserid');

        // New status field.
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'small', null, null, null, 'processed', 'timefinish');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012020100, 'offlinequiz');
    }

    // Add and rename fields in table offlinquiz_queue_data.
    if ($oldversion < 2012020200) {

        // Define field status to be added to offlinequiz_queue_data.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, 'ok', 'filename');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
            $dbman->change_field_precision($table, $field);
            $dbman->change_field_notnull($table, $field);
            $dbman->change_field_unsigned($table, $field);
        }

        // Add new field 'error'.
        $field = new xmldb_field('error', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'status');

        // Conditionally launch add field error.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Rename field queue to queueid.
        $field = new xmldb_field('queue', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field queueid.
        $dbman->rename_field($table, $field, 'queueid');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012020200, 'offlinequiz');
    }

    // Rename field list on table offlinequiz_participants to listid.
    if ($oldversion < 2012020300) {

        // Rename field list on table offlinequiz_participants to listid.
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('list', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field listid.
        $dbman->rename_field($table, $field, 'listid');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012020300, 'offlinequiz');
    }

    // Migrate the old lists of participants to the new table offlinequiz_p_lists (with 's').
    if ($oldversion < 2012020400) {

        $oldplists = $DB->get_records('offlinequiz_p_list');
        foreach ($oldplists as $oldplist) {
            $newplist = new StdClass();
            $newplist->offlinequizid = $oldplist->offlinequiz;
            $newplist->name = $oldplist->name;
            $newplist->number = $oldplist->list;
            // NOTE.
            // We don't set filename because we can always recreate the PDF files if needed.
            $newplist->id = $DB->insert_record('offlinequiz_p_lists', $newplist);

            // Get all the participants linked to the old list and link them to the new list in offlinequiz_p_lists.
            if ($oldparts = $DB->get_records('offlinequiz_participants', array('listid' => $oldplist->id))) {
                foreach ($oldparts as $oldpart) {
                    $oldpart->listid = $newplist->id;
                    $DB->update_record('offlinequiz_participants', $oldpart);
                }
            }
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012020400, 'offlinequiz');
    }

    // Check if there are inconsistencies in the DB, i.e. uniqueids used by both quizzes and offlinequizzes.
    if ($oldversion < 2012020410) {

        $sql = 'SELECT uniqueid
        FROM {offlinequiz_attempts} qa WHERE
        EXISTS (SELECT id from {quiz_attempts} where uniqueid = qa.uniqueid)';
        $doubleids = $DB->get_fieldset_sql($sql, array());

        // For each double uniqueid create a new uniqueid and change the fields in the tables.
        // Offlinequiz_attempts, question_sessions and question_states.
        echo $OUTPUT->notification('Fixing ' . count($doubleids) . ' question attempt uniqueids that are not unique',
                                   'notifysuccess');

        foreach ($doubleids as $doubleid) {
            echo $doubleid . ', ';
            if ($usage = $DB->get_record('question_usages', array('id' => $doubleid))) {
                $transaction = $DB->start_delegated_transaction();
                unset($usage->id);
                $usage->id = $DB->insert_record('question_usages', $usage);

                $DB->set_field_select('offlinequiz_attempts', 'uniqueid', $usage->id, 'uniqueid = :oldid',
                                      array('oldid' => $doubleid));
                $DB->set_field_select('question_states', 'attempt', $usage->id, 'attempt = :oldid',
                                      array('oldid' => $doubleid));
                $DB->set_field_select('question_sessions', 'attemptid', $usage->id, 'attemptid = :oldid',
                                      array('oldid' => $doubleid));
                $transaction->allow_commit();
            }
        }
        upgrade_mod_savepoint(true, 2012020410, 'offlinequiz');
    }

    // -----------------------------------------------------
    // Update the contextid field in question_usages (compare lib/db/upgrade.php lines 6108 following).
    // -----------------------------------------------------
    if ($oldversion < 2012020500) {

        echo $OUTPUT->notification('Fixing question usages context ID', 'notifysuccess');

        // Update the component field if necessary.
        $DB->set_field('question_usages', 'component', 'mod_offlinequiz', array('component' => 'offlinequiz'));

        // Populate the contextid field.
        $offlinequizmoduleid = $DB->get_field('modules', 'id', array('name' => 'offlinequiz'));
        $DB->execute("
                UPDATE {question_usages} SET contextid = (
                SELECT ctx.id
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.module = $offlinequizmoduleid
                JOIN {offlinequiz_attempts} quiza ON quiza.offlinequiz = cm.instance
                WHERE ctx.contextlevel = " . CONTEXT_MODULE . "
                AND quiza.uniqueid = {question_usages}.id)
                WHERE (
                SELECT ctx.id
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.module = $offlinequizmoduleid
                JOIN {offlinequiz_attempts} quiza ON quiza.offlinequiz = cm.instance
                WHERE ctx.contextlevel = " . CONTEXT_MODULE . "
                AND quiza.uniqueid = {question_usages}.id) IS NOT NULL
                ");

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012020500, 'offlinequiz');
    }

    // -----------------------------------------------------
    // Now we migrate data from the old to the new tables.
    // -----------------------------------------------------

    // We have to delete redundant question instances from offlinequizzes because they are incompatible with the new code.
    if ($oldversion < 2012030100) {

        echo $OUTPUT->notification('Migrating old offline quizzes to new offline quizzes..', 'notifysuccess');

        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_remove_redundant_q_instances();

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030100, 'offlinequiz');
    }

    // Migrate all entries in the offlinequiz_group table to the new tables offlinequiz_groups  and offlinequiz_group_questions.
    if ($oldversion < 2012030101) {

        echo $OUTPUT->notification('Creating new offlinequiz groups', 'notifysuccess');

        $offlinequizzes = $DB->get_records('offlinequiz');

        $counter = 0;
        foreach ($offlinequizzes as $offlinequiz) {
            if (!$DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id))) {
                echo '.';
                $counter++;
                flush();
                ob_flush();
                if ($counter % 100 == 0) {
                    echo "<br/>\n";
                    echo $counter;
                }
                $transaction = $DB->start_delegated_transaction();
                $oldgroups = $DB->get_records('offlinequiz_group', array('offlinequiz' => $offlinequiz->id), 'groupid ASC');
                $newgroups = array();
                foreach ($oldgroups as $oldgroup) {
                    $newgroup = new StdClass();
                    $newgroup->offlinequizid = $offlinequiz->id;
                    $newgroup->number = $oldgroup->groupid;
                    $newgroup->sumgrades = $oldgroup->sumgrades;
                    $newgroup->timecreated = time();
                    $newgroup->timemodified = time();
                    // First we need the ID of the new group.
                    if (!$oldid = $DB->get_field('offlinequiz_groups', 'id', array('offlinequizid' => $offlinequiz->id,
                            'number' => $newgroup->number))) {
                            $newgroup->id = $DB->insert_record('offlinequiz_groups', $newgroup);
                    } else {
                        $newgroup->id = $oldid;
                    }
                    // Now create an entry in offlinquiz_group_questions for each question in the old group layout.
                    $questions = explode(',', $oldgroup->questions);
                    $position = 1;
                    foreach ($questions as $question) {
                        $groupquestion = new StdClass();
                        $groupquestion->offlinequizid = $offlinequiz->id;
                        $groupquestion->offlinegroupid = $newgroup->id;
                        $groupquestion->questionid = $question;
                        $groupquestion->position = $position++;
                        if (!$DB->get_record('offlinequiz_group_questions', array('offlinequizid' => $offlinequiz->id,
                                'offlinegroupid' => $newgroup->id,
                                'questionid' => $question))) {
                                $DB->insert_record('offlinequiz_group_questions', $groupquestion);
                        }
                    }
                    $newgroups[] = $newgroup;

                }
                require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
                list($maxquestions, $maxanswers, $formtype, $questionsperpage) =
                    offlinequiz_get_question_numbers($offlinequiz, $newgroups);
                foreach ($newgroups as $newgroup) {
                    // Now we know the number of pages of the group.
                    $newgroup->numberofpages = ceil($maxquestions / ($formtype * 24));
                    $DB->update_record('offlinequiz_groups', $newgroup);
                }

                $transaction->allow_commit();
            }
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030101, 'offlinequiz');
    }

    // Migrate all entries in the offlinequiz_i_log table to the new tables offlinequiz_scanned_pages, offlinequiz_choices and.
    // Offlinequiz_page_corners. Also migrate the files to the new filesystem.

    // First we mark all offlinequizzes s.t. we upgrade them only once. Many things can go wrong here..
    if ($oldversion < 2012030200) {
        // Define field needsilogupgrade to be added to offlinequiz_attempts.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('needsilogupgrade', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'timeopen');

        // Launch add field needsilogupgrade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('offlinequiz', 'needsilogupgrade', 1);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030200, 'offlinequiz');
    }

    // Then we mark all offlinequiz_attempts to be upgraded.
    if ($oldversion < 2012030300) {
        // Define field needsupgradetonewqe to be added to offlinequiz_attempts.
        $table = new xmldb_table('offlinequiz_attempts');
        $field = new xmldb_field('needsupgradetonewqe', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'sheet');

        // Launch add field needsupgradetonewqe.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('offlinequiz_attempts', 'needsupgradetonewqe', 1);

        // Quiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030300, 'offlinequiz');
    }

    // In a first step we upgrade the offlinequiz_attempts exactly like quiz_attempts (see mod/quiz/db/upgrade.php).
    if ($oldversion < 2012030400) {
        $table = new xmldb_table('question_states');

        if ($dbman->table_exists($table)) {
            // NOTE: We need all attemps, also the ones with sheet=1 because the are the groups' template attempts.

            // Now update all the old attempt data.
            $oldrcachesetting = $CFG->rcache;
            $CFG->rcache = false;

            require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');

            $upgrader = new offlinequiz_attempt_upgrader();
            $upgrader->convert_all_quiz_attempts();

            $CFG->rcache = $oldrcachesetting;
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030400, 'offlinequiz');
    }

    // Then we mark all offlinequiz_attempts to be upgraded.
    if ($oldversion < 2012030500) {
        // Define field resultid to be added to offlinequiz_attempts for later reference.
        set_time_limit(3000);

        $table = new xmldb_table('offlinequiz_attempts');
        $field = new xmldb_field('resultid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Launch add field resultid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Quiz savepoint reached.
        upgrade_mod_savepoint(true, 2012030500, 'offlinequiz');
    }

    // In a second step we convert all offlinequiz_attempts into offlinequiz_results and also upgrade the ilog table.
    if ($oldversion < 2012060101) {

        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');

        $oldrcachesetting = $CFG->rcache;
        $CFG->rcache = false;

        $upgrader = new offlinequiz_ilog_upgrader();
        $upgrader->convert_all_offlinequiz_attempts();

        $CFG->rcache = $oldrcachesetting;
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012060101, 'offlinequiz');
    }

    if ($oldversion < 2012060105) {

        // Changing type of field grade on table offlinequiz_q_instances to number.
        $table = new xmldb_table('offlinequiz_q_instances');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '12, 7', null, XMLDB_NOTNULL, null, '0', 'question');

        // Launch change of type for field grade.
        $dbman->change_field_type($table, $field);
        // Launch change of precision for field grade.
        $dbman->change_field_precision($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012060105, 'offlinequiz');
    }

    if ($oldversion < 2012121200) {

        // Define field introformat to be added to offlinequiz.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Conditionally launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2012121200, 'offlinequiz');
    }

    if ($oldversion < 2013012400) {

        // Define field info to be added to offlinequiz_queue.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('info', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'status');

        // Conditionally launch add field info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013012400, 'offlinequiz');
    }

    if ($oldversion < 2013012410) {

        // Define field info to be added to offlinequiz_queue_data.
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('info', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'error');

        // Conditionally launch add field info.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013012410, 'offlinequiz');
    }

    if ($oldversion < 2013012500) {

        // Changing type of field grade on table offlinequiz to int.
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0', 'time');

        // Launch change for field grade.
        $dbman->change_field_type($table, $field);
        $dbman->change_field_precision($table, $field);
        $dbman->change_field_unsigned($table, $field);

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013012500, 'offlinequiz');
    }

    if ($oldversion < 2013041600) {

        // Rename field question on table offlinequiz_q_instances to questionid.
        $table = new xmldb_table('offlinequiz_q_instances');
        $field = new xmldb_field('question', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'offlinequiz');

        // Launch rename field question.
        $dbman->rename_field($table, $field, 'questionid');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013041600, 'offlinequiz');
    }

    if ($oldversion < 2013041601) {

        // Rename field offlinequiz on table offlinequiz_q_instances to offlinequizid.
        $table = new xmldb_table('offlinequiz_q_instances');
        $field = new xmldb_field('offlinequiz', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch rename field offlinequiz.
        $dbman->rename_field($table, $field, 'offlinequizid');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013041601, 'offlinequiz');
    }

    if ($oldversion < 2013061300) {

        // Define table offlinequiz_hotspots to be created.
        $table = new xmldb_table('offlinequiz_hotspots');

        // Adding fields to table offlinequiz_hotspots.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedpageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('x', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('y', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('blank', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table offlinequiz_hotspots.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_hotspots.
        $table->add_index('scannedpageididx', XMLDB_INDEX_NOTUNIQUE, array('scannedpageid'));

        // Conditionally launch create table for offlinequiz_hotspots.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013061300, 'offlinequiz');
    }

    if ($oldversion < 2013110800) {

        // Define field timecreated to be added to offlinequiz_queue.
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'importuserid');

        // Conditionally launch add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013110800, 'offlinequiz');
    }

    if ($oldversion < 2013112500) {

        // Define index offlinequiz_userid_idx (not unique) to be added to offlinequiz_results.
        $table = new xmldb_table('offlinequiz_results');
        $index = new xmldb_index('offlinequiz_userid_idx', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch add index offlinequiz_userid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2013112500, 'offlinequiz');
    }

    // Moodle v2.8.5+ release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2015060500) {

        // Rename field page on table offlinequiz_group_questions to NEWNAMEGOESHERE.
        $table = new xmldb_table('offlinequiz_group_questions');
        $field = new xmldb_field('pagenumber', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'position');

        // Launch rename field page.
        $dbman->rename_field($table, $field, 'page');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015060500, 'offlinequiz');
    }

    if ($oldversion < 2015060501) {

        // Rename field page on table offlinequiz_group_questions to NEWNAMEGOESHERE.
        $table = new xmldb_table('offlinequiz_group_questions');
        $field = new xmldb_field('usageslot', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'position');

        // Launch rename field page.
        $dbman->rename_field($table, $field, 'slot');

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015060501, 'offlinequiz');
    }

    if ($oldversion < 2015060502) {

        // Define field maxmark to be added to offlinequiz_group_questions.
        $table = new xmldb_table('offlinequiz_group_questions');
        $field = new xmldb_field('maxmark', XMLDB_TYPE_NUMBER, '12, 7', null, XMLDB_NOTNULL, null, '1', 'slot');

        // Conditionally launch add field maxmark.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015060502, 'offlinequiz');
    }

    if ($oldversion < 2015060902) {

        // This upgrade migrates old offlinequiz_q_instances grades (maxgrades) to new
        // maxmark field in offlinequiz_group_questions.
        // It also deletes group questions with questionid 0 (pagebreaks) and inserts the
        // correct page number instead.

        $numofflinequizzes = $DB->count_records('offlinequiz');
        if ($numofflinequizzes > 0) {
            $pbar = new progress_bar('offlinequizquestionstoslots', 500, true);
            $pbar->create();
            $pbar->update(0, $numofflinequizzes,
                        "Upgrading offlinequiz group questions - {0}/{$numofflinequizzes}.");

            $numberdone = 0;
            $offlinequizzes = $DB->get_recordset('offlinequiz', null, 'id', 'id, numgroups');
            foreach ($offlinequizzes as $offlinequiz) {
                $transaction = $DB->start_delegated_transaction();

                $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id),
                        'number', '*');
                $instancesraw = $DB->get_records('offlinequiz_q_instances',
                        array('offlinequizid' => $offlinequiz->id));

                $questioninstances = array();
                foreach ($instancesraw as $instance) {
                    if (!array_key_exists($instance->questionid, $questioninstances)) {
                        $questioninstances[$instance->questionid] = $instance;
                    }
                }

                foreach ($groups as $group) {
                    $groupquestions = $DB->get_records('offlinequiz_group_questions',
                            array('offlinequizid' => $offlinequiz->id, 'offlinegroupid' => $group->id), 'position');
                    // For every group we start on page 1.
                    $currentpage = 1;
                    $currentslot = 1;
                    foreach ($groupquestions as $groupquestion) {
                        $needsupdate = false;
                        if ($groupquestion->questionid == 0) {
                            // We remove the old pagebreaks with questionid==0.
                            $DB->delete_records('offlinequiz_group_questions', array('id' => $groupquestion->id));
                            $currentpage++;
                            continue;
                        }
                        // If the maxmarks in the question instances differs from the default maxmark (1)
                        // of the offlinequiz_group_questions then change it.
                        if (array_key_exists($groupquestion->questionid, $questioninstances)
                            && ($maxmark = floatval($questioninstances[$groupquestion->questionid]->grade))
                            && abs(floatval($groupquestion->maxmark) - $maxmark) > 0.001) {
                                $groupquestion->maxmark = $maxmark;
                                $needsupdate = true;
                        }
                        // If the page number is not correct, then change it.
                        if ($groupquestion->page != $currentpage) {
                            $groupquestion->page = $currentpage;
                            $needsupdate = true;
                        }
                        // If the slot is not set, then fill it.
                        if (!$groupquestion->slot) {
                            $groupquestion->slot = $currentslot;
                            $needsupdate = true;
                        }

                        if ($needsupdate) {
                            $DB->update_record('offlinequiz_group_questions', $groupquestion);
                        }
                        $currentslot++;
                    }
                }

                // Done with this offlinequiz. Update progress bar.
                $numberdone++;
                $pbar->update($numberdone, $numofflinequizzes,
                        "Upgrading offlinequiz group questions - {$numberdone}/{$numofflinequizzes}.");

                $transaction->allow_commit();
            }
        }
        // Offlinequiz savepoint reached.
        upgrade_mod_savepoint(true, 2015060902, 'offlinequiz');
    }

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
                <br><b><a href='. $PAGE->url->__toString() . '&croninfo_read=true> CONTINUE </a> </b>
                <br>
                </div>' );
                echo $OUTPUT->footer(); die;
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
        $index = new xmldb_index('offlinequiz_page_corners_scannedpageid_idx', XMLDB_INDEX_NOTUNIQUE, array('scannedpageid'));
        // Conditionally launch add index offlinequiz_userid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $table = new xmldb_table('offlinequiz_scanned_pages');
        $index = new xmldb_index('offlinequiz_scanned_pages_resultid_idx', XMLDB_INDEX_NOTUNIQUE, array('resultid'));
        // Conditionally launch add index offlinequiz_userid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
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
    // TODO migrate old offlinequiz_q_instances maxmarks to new maxmark field in offlinequiz_group_questions.
    // TODO migrate  offlinequiz_group_questions to fill in page field correctly. For every group use the
    // position field to find new pages and insert them.
    // Adapt offlinequiz code to handle missing zeros as pagebreaks.

    return true;
}
