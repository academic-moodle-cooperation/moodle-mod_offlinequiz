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

namespace mod_offlinequiz\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

require_once($CFG->libdir . '/questionlib.php');

class provider implements
// This plugin has data.
\core_privacy\local\metadata\provider,

// This plugin currently implements the original plugin\provider interface.
\core_privacy\local\request\plugin\provider,
// This plugin implements the userlist-provider.
\core_privacy\local\request\core_userlist_provider
{
    public static function get_metadata(collection $collection) : collection {

        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        $collection->link_subsystem('core_question', 'privacy:metadata:core_question');
        $collection->link_subsystem('mod_quiz', 'privacy:metadata:mod_quiz');

        $collection->add_database_table(
          'offlinequiz',
          [
         'course' => 'privacy:metadata:offlinequiz:course',
         'name' => 'privacy:metadata:offlinequiz:name',
         'introformat' => 'privacy:metadata:offlinequiz:introformat',
         'pdfintro' => 'privacy:metadata:offlinequiz:pdfintro',
         'timeopen' => 'privacy:metadata:offlinequiz:timeopen',
         'timeclose' => 'privacy:metadata:offlinequiz:timeclose',
         'time' => 'privacy:metadata:offlinequiz:time',
         'grade' => 'privacy:metadata:offlinequiz:grade',
         'numgroups' => 'privacy:metadata:offlinequiz:numgroups',
         'decimalpoints' => 'privacy:metadata:offlinequiz:decimalpoints',
         'review' => 'privacy:metadata:offlinequiz:review',
         'docscreated' => 'privacy:metadata:offlinequiz:docscreated',
         'shufflequestions' => 'privacy:metadata:offlinequiz:shufflequestions',
         'printstudycodefield' => 'privacy:metadata:offlinequiz:printstudycodefield',
         'papergray' => 'privacy:metadata:offlinequiz:papergray',
         'fontsize' => 'privacy:metadata:offlinequiz:fontsize',
         'timecreated' => 'privacy:metadata:offlinequiz:timecreated',
         'timemodified' => 'privacy:metadata:offlinequiz:timemodified',
         'fileformat' => 'privacy:metadata:offlinequiz:fileformat',
         'showquestioninfo' => 'privacy:metadata:offlinequiz:showquestioninfo',
         'showgrades' => 'privacy:metadata:offlinequiz:showgrades',
         'showtutorial' => 'privacy:metadata:offlinequiz:showtutorial',
         'id_digits' => 'privacy:metadata:offlinequiz:id_digits',
         'disableimgnewlines' => 'privacy:metadata:offlinequiz:disableimgnewlines'
          ],
          'privacy:metadata:offlinequiz'
          );

        $collection->add_database_table(
          'offlinequiz_choices',
          [
         'scannedpageid' => 'privacy:metadata:offlinequiz_choices:scannedpageid',
         'slotnumber' => 'privacy:metadata:offlinequiz_choices:slotnumber',
         'choicenumber' => 'privacy:metadata:offlinequiz_choices:choicenumber',
         'value' => 'privacy:metadata:offlinequiz_choices:value'
          ],
          'privacy:metadata:offlinequiz_choices'
          );

        $collection->add_database_table(
          'offlinequiz_group_questions',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_group_questions:offlinequizid',
         'offlinegroupid' => 'privacy:metadata:offlinequiz_group_questions:offlinegroupid',
         'questionid' => 'privacy:metadata:offlinequiz_group_questions:questionid',
         'position' => 'privacy:metadata:offlinequiz_group_questions:position',
         'page' => 'privacy:metadata:offlinequiz_group_questions:page',
         'slot' => 'privacy:metadata:offlinequiz_group_questions:slot',
         'maxmark' => 'privacy:metadata:offlinequiz_group_questions:maxmark'
          ],
          'privacy:metadata:offlinequiz_group_questions'
          );

        $collection->add_database_table(
          'offlinequiz_groups',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_groups:offlinequizid',
         'groupnumber' => 'privacy:metadata:offlinequiz_groups:number',
         'sumgrades' => 'privacy:metadata:offlinequiz_groups:sumgrades',
         'numberofpages' => 'privacy:metadata:offlinequiz_groups:numberofpages',
         'templateusageid' => 'privacy:metadata:offlinequiz_groups:templateusageid',
         'qestionfilename' => 'privacy:metadata:offlinequiz_groups:questionfilename',
         'answerfilename' => 'privacy:metadata:offlinequiz_groups:answerfilename',
         'correctionfilename' => 'privacy:metadata:offlinequiz_groups:correctionfilename'
          ],
          'privacy:metadata:offlinequiz_groups'
          );

        $collection->add_database_table(
          'offlinequiz_hotspots',
          [
         'scannedpageid' => 'privacy:metadata:offlinequiz_hotspots:scannedpageid',
         'name' => 'privacy:metadata:offlinequiz_hotspots:name',
         'x' => 'privacy:metadata:offlinequiz_hotspots:x',
         'y' => 'privacy:metadata:offlinequiz_hotspots:y',
         'blank' => 'privacy:metadata:offlinequiz_hotspots:blank',
         'time' => 'privacy:metadata:offlinequiz_hotspots:time'
          ],
          'privacy:metadata:offlinequiz_hotspots'
          );

        $collection->add_database_table(
          'offlinequiz_page_corners',
          [
         'scannedpageid' => 'privacy:metadata:offlinequiz_page_corners:scannedpageid',
         'x' => 'privacy:metadata:offlinequiz_page_corners:x',
         'y' => 'privacy:metadata:offlinequiz_page_corners:y',
         'position' => 'privacy:metadata:offlinequiz_page_corners:position'
          ],
          'privacy:metadata:offlinequiz_page_corners'
          );

        $collection->add_database_table(
          'offlinequiz_participants',
          [
         'listid' => 'privacy:metadata:offlinequiz_participants:listid',
         'userid' => 'privacy:metadata:offlinequiz_participants:userid',
         'checked' => 'privacy:metadata:offlinequiz_participants:checked'
          ],
          'privacy:metadata:offlinequiz_participants'
          );

        $collection->add_database_table(
          'offlinequiz_p_choices',
          [
         'scannedpageid' => 'privacy:metadata:offlinequiz_p_choices:scannedpageid',
         'userid' => 'privacy:metadata:offlinequiz_p_choices:userid',
         'value' => 'privacy:metadata:offlinequiz_p_choices:value'
          ],
          'privacy:metadata:offlinequiz_p_choices'
          );

        $collection->add_database_table(
          'offlinequiz_p_lists',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_p_lists:offlinequizid',
         'name' => 'privacy:metadata:offlinequiz_p_lists:name',
         'listnumber' => 'privacy:metadata:offlinequiz_p_lists:number',
         'filename' => 'privacy:metadata:offlinequiz_p_lists:filename'
          ],
          'privacy:metadata:offlinequiz_p_lists'
          );

        $collection->add_database_table(
          'offlinequiz_queue',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_queue:offlinequizid',
         'importuserid' => 'privacy:metadata:offlinequiz_queue:importuserid',
         'timecreated' => 'privacy:metadata:offlinequiz_queue:timecreated',
         'timestart' => 'privacy:metadata:offlinequiz_queue:timestart',
         'timefinish' => 'privacy:metadata:offlinequiz_queue:timefinish',
         'status' => 'privacy:metadata:offlinequiz_queue:status'
          ],
          'privacy:metadata:offlinequiz_queue'
          );

        $collection->add_database_table(
          'offlinequiz_queue_data',
          [
         'queueid' => 'privacy:metadata:offlinequiz_queue_data:queueid',
         'filename' => 'privacy:metadata:offlinequiz_queue_data:filename',
         'status' => 'privacy:metadata:offlinequiz_queue_data:status',
         'error' => 'privacy:metadata:offlinequiz_queue_data:error'
          ],
          'privacy:metadata:offlinequiz_queue_data'
          );

        $collection->add_database_table(
          'offlinequiz_results',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_results:offlinequizid',
         'offlinegroupid' => 'privacy:metadata:offlinequiz_results:offlinegroupid',
         'userid' => 'privacy:metadata:offlinequiz_results:userid',
         'sumgrades' => 'privacy:metadata:offlinequiz_results:sumgrades',
         'usageid' => 'privacy:metadata:offlinequiz_results:usageid',
         'teacherid' => 'privacy:metadata:offlinequiz_results:teacherid',
         'status' => 'privacy:metadata:offlinequiz_results:status',
         'timestart' => 'privacy:metadata:offlinequiz_results:timestart',
         'timefinish' => 'privacy:metadata:offlinequiz_results:timefinish',
         'timemodified' => 'privacy:metadata:offlinequiz_results:timemodified'
          ],
          'privacy:metadata:offlinequiz_results'
          );

        $collection->add_database_table(
          'offlinequiz_scanned_pages',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_scanned_pages:offlinequizid',
         'resultid' => 'privacy:metadata:offlinequiz_scanned_pages:resultid',
         'filename' => 'privacy:metadata:offlinequiz_scanned_pages:filename',
         'warningfilename' => 'privacy:metadata:offlinequiz_scanned_pages:warningfilename',
         'groupnumber' => 'privacy:metadata:offlinequiz_scanned_pages:groupnumber',
         'userkey' => 'privacy:metadata:offlinequiz_scanned_pages:userkey',
         'pagenumber' => 'privacy:metadata:offlinequiz_scanned_pages:pagenumber',
         'time' => 'privacy:metadata:offlinequiz_scanned_pages:time',
         'status' => 'privacy:metadata:offlinequiz_scanned_pages:status',
         'error' => 'privacy:metadata:offlinequiz_scanned_pages:error'
          ],
          'privacy:metadata:offlinequiz_scanned_pages'
          );

        $collection->add_database_table(
          'offlinequiz_scanned_p_pages',
          [
         'offlinequizid' => 'privacy:metadata:offlinequiz_scanned_p_pages:offlinequizid',
         'listnumber' => 'privacy:metadata:offlinequiz_scanned_p_pages:listnumber',
         'filename' => 'privacy:metadata:offlinequiz_scanned_p_pages:filename',
         'time' => 'privacy:metadata:offlinequiz_scanned_p_pages:time',
         'status' => 'privacy:metadata:offlinequiz_scanned_p_pages:status',
         'error' => 'privacy:metadata:offlinequiz_scanned_p_pages:error'
          ],
          'privacy:metadata:offlinequiz_scanned_p_pages'
          );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $columns = $DB->get_columns("user");
        $offlinequizconfig = get_config('offlinequiz');
        $type = $columns[$offlinequizconfig->ID_field]->type;
        $offlinequizconfig = get_config('offlinequiz');
        // Fetch all choice answers.
        $sql = "SELECT c.id FROM {context} c
        JOIN {course_modules} cm ON cm.id = c.instanceid
        JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz'
        AND cm.instance IN (
            SELECT l.offlinequizid id
                FROM {offlinequiz_p_lists} l
                JOIN {offlinequiz_participants} p on l.id = p.listid
                WHERE userid = :participantsuserid
            UNION ALL
                SELECT p.offlinequizid id
                FROM {offlinequiz_scanned_p_pages} p
                JOIN {offlinequiz_p_choices} c ON p.id = c.scannedppageid
                WHERE c.userid = :choiceuserid
            UNION ALL
                SELECT q.offlinequizid id
                FROM {offlinequiz_queue} q
                WHERE importuserid = :queueuserid
            UNION ALL
                SELECT sp.offlinequizid id
                FROM {offlinequiz_scanned_pages} sp";
        if ($type == "int") {
            $sql  .= " JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = " . $DB->sql_cast_char2int(sp.userkey);
        } else {
            $sql .= " JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey";
        }
               $sql .= " WHERE u.id = :scannedpageuserid)";

        $params = [
          'participantsuserid'        => $userid,
          'choiceuserid'              => $userid,
          'queueuserid'               => $userid,
          'scannedpageuserid'         => $userid
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $offlinequizconfig = get_config('offlinequiz');
        if (empty($contextlist->get_contextids())) {
            return;
        }

        $columns = $DB->get_columns("user");
        $type = $columns[$offlinequizconfig->ID_field]->type;
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT c.id contextid, cm.instance offlinequizid
        FROM {context} c
        JOIN {course_modules} cm ON cm.id = c.instanceid
        JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz' AND contextlevel = 70
        AND cm.instance IN (
                SELECT l.offlinequizid id
                FROM {offlinequiz_p_lists} l
                JOIN {offlinequiz_participants} p on l.id = p.listid
                WHERE userid = :participantsuserid
            UNION ALL
                SELECT p.offlinequizid id
                FROM {offlinequiz_scanned_p_pages} p
                JOIN {offlinequiz_p_choices} c ON p.id = c.scannedppageid
                WHERE c.userid = :choiceuserid
            UNION ALL
                SELECT q.offlinequizid id
                FROM {offlinequiz_queue} q
                WHERE importuserid = :queueuserid
            UNION ALL
                SELECT sp.offlinequizid id
                FROM {offlinequiz_scanned_pages} sp";
        if ($type == "int") {
            $sql  .= " JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = " . $DB->sql_cast_char2int(sp.userkey);
        } else {
            $sql .= " JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey";
        }
               $sql .= " WHERE u.id = :scannedpageuserid)
        AND (c.id {$contextsql})";

        $params = [
                'participantsuserid'        => $user->id,
                'choiceuserid'              => $user->id,
                'queueuserid'               => $user->id,
                'scannedpageuserid'         => $user->id
        ] + $contextparams;

        $offlinequizes = $DB->get_records_sql($sql, $params);
        foreach ($offlinequizes as $offlinequiz) {
            static::export_offlinequiz($offlinequiz->offlinequizid, \context::instance_by_id($offlinequiz->contextid), $user->id);
        }
    }

    public static function get_users_in_context(userlist $userlist) {
        global $DB;
        $sql = "SELECT DISTINCT c.id contextid, cm.instance offlinequizid
                          FROM {context} c
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz' AND contextlevel = 70";
        $contexts = $DB->get_record_sql($sql);
        foreach ($contexts as $context) {
            $sql = "(SELECT userid FROM {offlinequiz_participants} p,
                        {offlinequiz_p_lists} l
                  WHERE l.offlinequizid = :offlinequizid1
                    AND l.id = p.listid
      ) UNION (
      SELECT c.userid FROM {offlinequiz_p_choices} c,
                           {offlinequiz_scanned_pages} p
                     WHERE p.id = c.scannedppageid
                       AND p.offlinequizid = :offlinequizid2
      ) UNION (
      SELECT q.importuserid FROM {offlinequiz_queue} q
                           WHERE q.offlinequizid = :offlinequizid3
      )";
            $userlist->add_from_sql('userid', $sql, ['offlinequizid1' => $context->offlinequizid,
             'offlinequizid2' => $context->offlinequizid, 'offlinequizid3' => $context->offlinequizid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        // Don't remove data from role_capabilities.
        // Because this data affects the whole Moodle, there are override capabilities.
        // Don't belong to the modifier user.
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        $sql = "SELECT distinct(list.id) FROM {offlinequiz_p_lists} list, {context} c
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz' AND contextlevel = 70
                         WHERE c.id  = :contextid";
        $listids = $DB->get_records_sql($sql, ['contextid' => $context->id]);

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        list($listidsql, $listidparams) = $DB->get_in_or_equal($listids, SQL_PARAMS_NAMED);

        $params = listidparams + $userparams;
        // Remove data from role_assignments.
        $DB->delete_records_select('offlinequiz_participants',
        "listid {$listidsql} AND userid {$usersql}", $params);
    }

    private static function export_offlinequiz($offlinequizid, $context, $userid) {
            static::export_student_data($offlinequizid, $context, $userid);
    }

    private static function export_student_data($offlinequizid, $context, $userid) {
        global $DB;
        $offlinequizconfig = get_config('offlinequiz');
        $exportobject = new \stdClass();

        $sql = "SELECT c.*
                FROM {offlinequiz_p_choices} c,
                     {offlinequiz_scanned_p_pages} s
                WHERE s.id = c.scannedppageid
                AND   c.userid = :userid
                AND   s.offlinequizid = :offlinequizid";
        $pchoices = $DB->get_records_sql($sql, ["userid" => $userid, "offlinequizid" => $offlinequizid]);
        if ($pchoices) {
            $exportobject->participantlists = static::get_scanned_p_page_objects($pchoices);
        }

        $sql = "SELECT sp.*
                FROM {offlinequiz_scanned_pages} sp
                JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey
                WHERE u.id = :userid
                AND   sp.offlinequizid = :offlinequizid";
        $scannedpages = $DB->get_records_sql($sql , ["userid" => $userid, "offlinequizid" => $offlinequizid]);
        if ($scannedpages) {
            $exportobject->scannedpages = static::get_scanned_pages_objects($context, $scannedpages);
        }
        $results = $DB->get_records("offlinequiz_results", ["userid" => $userid, "offlinequizid" => $offlinequizid]);
        if ($results) {
            $exportobject->results = static::get_results($results);
        }
        $datafoldername = get_string('privacy:data_folder_name', 'mod_offlinequiz');
        writer::with_context($context)
        ->export_data([$datafoldername], $exportobject);
    }

    private static function get_scanned_p_page_objects($pchoices) {
        global $DB;

        $scannedpages = [];
        foreach ($pchoices as $pchoice) {
            $scannedpage = $DB->get_record("offlinequiz_scanned_p_pages", ["id" => $pchoice->scannedppageid]);
            $exportobject = new \stdClass();
            $exportobject->listnumber = $scannedpage->listnumber;
            $exportobject->time = $scannedpage->time;
            $exportobject->error = $scannedpage->error;
            $exportobject->participant = $pchoice->value;
            $scannedpages[$pchoice->id] = $exportobject;
        }
        return $scannedpages;
    }

    private static function get_results($results) {
        $results = [];
        foreach ($results as $result) {
            $exportobject = new \stdClass();
            $exportobject->group = static::get_group_name_by_result($result);
            $exportobject->sumgrade = $result->sumgrade;
            $exportobject->usageid = $result->usageid;
            $exportobject->status = $result->status;
            $exportobject->timestart = $result->timestart;
            $exportobject->timefinish = $result->timefinish;
            $exportobject->timemodified = $result->timemodified;
            $results[] = $exportobject;
        }
        return $results;
    }

    private static function get_group_name_by_result($result) {
        global $DB;
        $sql = "SELECT g.groupnumber
                FROM   {offlinequiz_results} r,
                       {offlinequiz_groups} g
                WHERE  r.offlinegroupid = g.id
                AND    r.id = :resultid";
        $groupnumber = $DB->get_record_sql($sql, ["resultid" => $result->id]);
        return static::get_group_letter($groupnumber);
    }

    private static function get_scanned_pages_objects($context, $scannedpages) {
        foreach ($scannedpages as $scannedpage) {
            $scannedpageobjects[$scannedpage->id] = static::get_scanned_page_object($scannedpage);
            static::export_file($context, $scannedpage);
        }
        return $scannedpageobjects;
    }

    private static function export_file($context, $scannedpage) {
        $fs = get_file_storage();
        if ( $imagefile = $fs->get_file($context->id, 'mod_offlinequiz', 'imagefiles', 0, '/', $scannedpage->filename)) {
            $datafoldername = get_string('privacy:data_folder_name', 'mod_offlinequiz');
            $scannedpage = get_string('scannedform', 'mod_offlinequiz');
            $filepath = [$datafoldername, $scannedpage];
            writer::with_context($context)->export_file($filepath, $imagefile);
        }
    }

    private static function get_scanned_page_object($scannedpage) {
        $exportscannedpage = new \stdClass();
        $exportscannedpage->id = $scannedpage->id;
        $exportscannedpage->result = $scannedpage->resultid;
        $exportscannedpage->group = static::get_group($scannedpage->offlinequizid, $scannedpage->groupnumber);
        $exportscannedpage->pagecorners = static::get_page_corners($scannedpage->id);
        return $exportscannedpage;
    }

    private static function get_page_corners($scannedpageid) {
        global $DB;
        return $DB->get_records("offlinequiz_page_corners", ["scannedpageid" => $scannedpageid]);
    }

    private static function get_group($offlinequizid, $groupnumber) {
        global $DB;

        $group = $DB->get_record("offlinequiz_groups", ["offlinequizid" => $offlinequizid, "groupnumber" => $groupnumber ]);
        $exportgroup = new \stdClass();
        $exportgroup->letter = static::get_group_letter($groupnumber);
        $exportgroup->sumgrades = $group->sumgrades;
        $exportgroup->questionfilename = $group->questionfilename;
        $exportgroup->answerfilename = $group->answerfilename;
        $exportgroup->correctionfilename = $group->correctionfilename;
        return $exportgroup;
    }

    private static function get_group_letter($groupnumber) {
        switch ($groupnumber) {
            case 1:
             return "A";
             break;
            case 2:
             return "B";
             break;
            case 3:
             return "C";
             break;
            case 4:
             return "D";
             break;
            case 5:
             return "E";
             break;
            case 6:
             return "F";
             break;
            default:
             return "none";
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only offlinequiz module will be handled.
            return;
        }
        $cm = get_coursemodule_from_id('offlinequiz', $context->instanceid);
        if (!$cm) {
            // Only offlinequiz module will be handled.
            return;
        }
        list($course, $cm) = get_course_and_cm_from_cmid($cm);
        if (!$course) {
            // A Module without course? Something that should never happen better do nothing!
            return;
        }
        $users = user_get_participants($course->id);
        foreach ($users as $user) {
            static::delete_data_for_user_in_offlinequiz($cm->instance, $user);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT cm.instance
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid
                JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz' AND contextlevel = 70
                AND (c.id {$contextsql} )";
        $offlinequizes = $DB->get_records_sql($sql, $contextparams);
        foreach ($offlinequizes as $offlinequiz) {
            static::delete_data_for_user_in_offlinequiz($offlinequiz->instance, $contextlist->get_user());
        }
    }

    /**
     * delete all data referring to a user in an offlinequiz
     * @param int $offlinequizid
     * @param \stdClass $user
     */
    private static function delete_data_for_user_in_offlinequiz(int $offlinequizid, $user) {
        $cm = get_coursemodule_from_instance("offlinequiz", $offlinequizid);
        if (! $cm) {
            return false;
        }
        $context = \context_module::instance($cm->id);
        static::delete_results($context, $offlinequizid, $user);
        static::remove_from_lists($offlinequizid, $user);
    }

    /**
     * delete all results referring to a user in an offlinequiz
     * @param int $offlinequizid
     * @param \stdClass $user
     */
    private static function delete_results($context, $offlinequizid, $user) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
        global $DB;
        $select = 'offlinequizid = :oqid AND userid = :userid';
        $resultids = $DB->get_fieldset_select('offlinequiz_results', 'id',
            $select, ['oqid' => $offlinequizid, 'userid' => $user->id]);
        foreach ($resultids as $resultid) {
            // First delete all scannedpages.
            $scannedpages = $DB->get_records_select('offlinequiz_scanned_pages', 'resultid = :resultid', ['resultid' => $resultid]);
            foreach ($scannedpages as $scannedpage) {
                \offlinequiz_delete_scanned_page($scannedpage, $context);
            }
            // Then the corresponding result.
            \offlinequiz_delete_result($resultid, $context);
        }
    }

    /**
     * delete a user from all groups in an offlinequiz
     * @param int $offlinequizid
     * @param \stdClass $user
     */
    private static function remove_from_lists($offlinequizid, $user) {
        global $DB;
        $sql = "SELECT c.id
                FROM   {offlinequiz_p_lists} p
                JOIN   {offlinequiz_participants} c ON c.listid = p.id
                WHERE  p.offlinequizid = :offlinequizid
                AND    c.userid = :userid";
        $participantsid = $DB->get_field_sql($sql, ['offlinequizid' => $offlinequizid, 'userid' => $user->id]);
        if ($participantsid) {
            $DB->delete_records('offlinequiz_participants', ['id' => $participantsid]);
        }
        $sql = "SELECT c.id
                FROM   {offlinequiz_scanned_p_pages} p
                JOIN   {offlinequiz_p_choices} c ON p.id = c.scannedppageid
                WHERE  c.userid = :userid
                AND    p.offlinequizid = :oqid";

        $choiceids = $DB->get_fieldset_sql($sql, ['userid' => $user->id, 'oqid' => $offlinequizid]);
        if ($choiceids) {
            list($choicesql, $choiceparams) = $DB->get_in_or_equal($choiceids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('offlinequiz_p_choices', "id {$choicesql}", $choiceparams);
        }
    }
}
