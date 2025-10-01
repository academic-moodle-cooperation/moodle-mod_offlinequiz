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
namespace offlinequiz_rimport\task\adhoc;

/**
 * An example of an adhoc task.
 */
class send_notifications extends \core\task\adhoc_task {
    public static function instance(int $queueid): self
    {
        $task = new self();
        $task->set_custom_data((object) [
            'queueid' => $queueid
        ]);
        return $task;
    }
    public function execute()
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');
        $data = $this->get_custom_data();
        $queue = $DB->get_record('offlinequiz_queue',['id' => $data->queueid]);
        
        $sql = "SELECT count(*) FROM {offlinequiz_queue_data} oqd
                        WHERE oqd.queueid = :queueid AND (oqd.status = 'new' OR oqd.status = 'processing')";
        $count = $DB->count_records_sql($sql, [
            'queueid' => $queue->id
        ]);
        if ($count == 0) {
            $offlinequiz = $DB->get_record('offlinequiz', ['id' => $queue->offlinequizid]);
            $course = $DB->get_record('course', ['id' => $offlinequiz->course]);
            \offlinequiz_update_grades($offlinequiz);
            
            $queue->timefinish = time();
            $queue->status = 'finished';
            $DB->update_record('offlinequiz_queue', $queue);
            
            echo date('Y-m-d-H:i') . ": Import queue with id $queue->id imported.\n\n";
            
            if ($user = $DB->get_record('user', ['id' => $queue->importuserid])) {
                $mailtext = get_string('importisfinished', 'offlinequiz', format_text($offlinequiz->name, FORMAT_PLAIN));
                
                // How many pages have been imported successfully.
                $countsql = "SELECT COUNT(id)
                               FROM {offlinequiz_queue_data}
                              WHERE queueid = :queueid
                                AND status = 'processed'";
                $params = ['queueid' => $queue->id];
                
                $mailtext .= "\n\n" . get_string('importnumberpages', 'offlinequiz', $DB->count_records_sql($countsql, $params));
                
                // How many pages have an error.
                $countsql = "SELECT COUNT(id)
                               FROM {offlinequiz_queue_data}
                              WHERE queueid = :queueid
                                AND status = 'error'";
                $mailtext .= "\n" . get_string('importnumberverify', 'offlinequiz', $DB->count_records_sql($countsql, $params));
                
                $countsql = "SELECT COUNT(*)
                               FROM {offlinequiz_queue_data} oqd
                               JOIN {offlinequiz_scanned_pages} osp ON osp.queuedataid = oqd.id
                              WHERE oqd.queueid = :queueid
                                AND osp.error = 'doublepage'"; 
                $mailtext .= "\n" . get_string('importnumberexisting', 'offlinequiz', $DB->count_records_sql($countsql, ['queueid' => $queue->id]));
                
                $linkoverview = new \moodle_url('/mod/offlinequiz/report.php', ['q' => $queue->offlinequizid, 'mode' => 'overview']);
                $mailtext .= "\n\n" . get_string('importlinkresults', 'offlinequiz', $linkoverview);
                
                $linkupload = new \moodle_url('/mod/offlinequiz/report.php', ['q' => $queue->offlinequizid, 'mode' => 'rimport']);
                $mailtext .= "\n" . get_string('importlinkverify', 'offlinequiz', $linkupload);
                
                $mailtext .= "\n\n" . get_string('importtimestart', 'offlinequiz', userdate($queue->timestart));
                $mailtext .= "\n" . get_string('importtimefinish', 'offlinequiz', userdate($queue->timefinish));
                
                // Send message to user using message api.
                
                $eventdata = new \core\message\message();
                $eventdata->name = 'jobs';
                $eventdata->courseid = $course->id;
                $eventdata->component = 'mod_offlinequiz';
                $eventdata->userfrom = \core\user::NOREPLY_USER; // No user from, this is a system message.
                $eventdata->userto = $user;
                $eventdata->subject = get_string('importmailsubject', 'offlinequiz');
                $eventdata->fullmessage = $mailtext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = $mailtext;
                $eventdata->notification = 1;
                $eventdata->smallmessage = $mailtext;
                
                message_send($eventdata);
            }
        }
        
    }

    
    
}