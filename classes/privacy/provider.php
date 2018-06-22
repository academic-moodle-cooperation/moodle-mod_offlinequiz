<?php
// …

namespace offlinequiz\privacy;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;

require_once($CFG->libdir . '/questionlib.php');

class provider implements
// This plugin does store personal user data.
\core_privacy\local\metadata\provider {
    public static function get_metadata(collection $collection) : collection {

        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');
        $collection->link_subsystem('core_question', 'privacy:metadata:core_question');
        $collection->link_subsystem('mod_quiz', 'privacy:metadata:mod_quiz');

        // Bsp2: hat eine Tabelle, wo userdaten gespeichert werden
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
         'number' => 'privacy:metadata:offlinequiz_groups:number',
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
         'number' => 'privacy:metadata:offlinequiz_p_lists:number',
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

        $offlinequizconfig = get_config('offlinequiz');

        // Fetch all choice answers.
        $sql = "SELECT c.id FROM {context} c 
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz'
        AND cm.instance IN (
				SELECT l.offlinequizid id
				FROM {offlinequiz_p_lists} l 
				INNER JOIN {offlinequiz_participants} p on l.id = p.listid 
				WHERE userid = :userid
			UNION ALL 
				SELECT p.offlinequizid id
				FROM {offlinequiz_scanned_p_pages} p
				INNER JOIN {offlinequiz_p_choices} c ON p.id = c.scannedppageid
				WHERE c.userid = :userid
			UNION ALL
				SELECT q.offlinequizid id 
				FROM {offlinequiz_queue} q 
				WHERE importuserid = :userid
			UNION ALL
				SELECT sp.offlinequizid id
				FROM {offlinequiz_scanned_pages} sp
				JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey
			    WHERE u.id = :userid)";

        $params = [
          'userid'        => $userid
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
        if (empty($contextlist->get_contextids()->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT cm.instance offlinequizid, c.id contextid
        FROM {context} c 
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'offlinequiz'
        AND cm.instance IN (
				SELECT l.offlinequizid id
				FROM {offlinequiz_p_lists} l 
				INNER JOIN {offlinequiz_participants} p on l.id = p.listid 
				WHERE userid = :userid
			UNION ALL 
				SELECT p.offlinequizid id
				FROM {offlinequiz_scanned_p_pages} p
				INNER JOIN {offlinequiz_p_choices} c ON p.id = c.scannedppageid
				WHERE c.userid = :userid
			UNION ALL
				SELECT q.offlinequizid id 
				FROM {offlinequiz_queue} q 
				WHERE importuserid = :userid
			UNION ALL
				SELECT sp.offlinequizid id
				FROM {offlinequiz_scanned_pages} sp
				INNER JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey
			    WHERE u.id = :userid)
		AND (c.id {$contextsql})";

        $params = ['userid' => $user->id] + $contextparams;

        $offlinequizes = $DB->get_records_sql($sql, $params);
        foreach ($offlinequizes as $offlinequiz) {
        	static::export_offlinequiz($offlinequiz->offlinequizid, \context::instance_by_id($contextid), $user->id);
        }
    }

    private static function export_offlinequiz($offlinequizid, $context, $userid) {
            export_student_data($offlinequizid, $context, $userid);
    }

    private static function export_student_data($offlinequizid, $context, $userid) {
        global $DB;
        $offlinequizconfig = get_config('offlinequiz');
        $exportobject = new \stdClass();

        $sql = "SELECT c.* 
                FROM {offlinequiz_p_choices} c, 
                     {offlinequiz_scanned_p_pages} s 
                WHERE s.id=c.scannedppageid
                AND   s.userid = :userid
				AND   c.offlinequizid";

        $pchoices = $DB->get_records_sql($sql,["userid" => $userid, "offlinequizid" => $offlinequizid]);

        if ($pchoices) {
            $exportobject->participantlists = static::get_scanned_p_page_objects($pchoices);
        }

        $sql = "SELECT sp.*
				FROM {offlinequiz_scanned_pages} sp
				INNER JOIN {user} u ON u." . $offlinequizconfig->ID_field . " = sp.userkey
			    WHERE u.id = :userid
				AND   sp.offlinequizid = :offlinequizid";
        $scannedpages = $DB->get_records_sql($sql , ["userid" => $userid, "offlinequizid" => $offlinequizid]);
        if($scannedpages) {
            $exportobject->scannedpages = static::get_scanned_pages_objects($scannedpages);
        }
        $results = $DB->get_records("offlinequiz_results", ["userid" => $userid, "offlinequizid" => $offlinequizid]);
        if($results) {
        	$exportobject->results = static::get_results($results);
        }
        writer::with_context($context)
        ->export_data($offlinequizid, $exportobject);
    }

    private static function get_scanned_p_page_objects($pchoices) {
        global $DB;

        $scannedpages = [];
        foreach ($pchoices as $pchoice) {
            $scannedpage = $DB->get_record("offlinequiz_scanned_p_page", ["id" => $pchoice->scannedppageid]);
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
        $SQL = "SELECT g.number 
                FROM   {offlinequiz_results} r,
                       {offlinequiz_groups} g 
                WHERE  r.offlinegroupid = g.id
                AND    r.id = :resultid";
        $groupnumber = $DB->get_record_sql($SQL,["resultid" => $result->id]);
        return static::get_group_letter($groupnumber);
    }

    private static function get_scanned_pages_objects($scannedpages) {
        foreach ($scannedpages as $scannedpage) {
            $scannedpageobjects[$scannedpage->id] = static::get_scanned_page_object($scannedpage);
        }
        return $scannedpageobjects;
    }

    private static function get_scanned_page_object($scannedpage) {
        global $DB;
        $exportscannedpage = new \stdClass();
        $exportscannedpage->id = $scannedpage->id;
        $exportscannedpage->result = $scannedpage->resultid;
        $exportscannedpage->group = static::get_group($scannedpage->offlinequizid,$scannedpage->groupnumber);
        $exportscannedpage->pagecorners = static::get_page_corners($scannedpage->id);
        return $exportscannedpage;
    }

    private static function get_page_corners($scannedpageid) {
        global $DB;
        return $DB->get_records("offlinequiz_page_corners", ["scannedpageid" => $scannedpageid]);
    }

    private static function get_group($offlinequizid,$groupnumber) {
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


}
