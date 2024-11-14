<?php


namespace offlinequiz_rimport\task\adhoc;

/**
 * An example of an adhoc task.
 */
class scan_file extends \core\task\adhoc_task {

    public static function instance(
        int $queuedataid
        ): self {
            $task = new self();
            $task->set_custom_data((object) [
                'queuedataid' => $queuedataid,
            ]);
            return $task;
    }
    public function execute (){
        global $DB;
        $data = $this->get_custom_data();
        $queuedata = $DB->get_record('offlinequiz_queue_data',['id' => $data->queuedataid]);
        $queue = $DB->get_record('offlinequiz_queue', ['id' => $queuedata->queueid]);
        $queuedata->timestart = time();
        $queuedata->status = 'processing';
        $DB->update_record('offlinequiz_queue_data', $queuedata);
        // Set up the context for this job.
        if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $queue->offlinequizid))) {
            $this->log_error($queuedata,'offlinequiznotfound');
        }
        if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
            $this->log_error($queuedata,'coursenotfound');
        }
        
        if (!$cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
            $this->log_error($queuedata,'coursemodulenotfound');
        }
        if (!$context = \context_module::instance($cm->id)) {
            $this->log_error($queuedata,'contextnotfound');
        }
        if ($coursecontext = \context_course::instance($course->id)) {
            $this->log_error($queuedata,'coursecontextnotfound');
        }
        if (!$groups = $DB->get_records('offlinequiz_groups', ['offlinequizid' => $offlinequiz->id],
            'groupnumber', '*', 0, $offlinequiz->numgroups)) {
            $this->log_error($queuedata,'nogroupsfound');
        }
        list($maxquestions, $maxanswers, $formtype, $questionsperpage) = offlinequiz_get_question_numbers($offlinequiz, $groups);
        $doubleentry = 0;
        $pathparts = pathinfo($queuedata->filename);
        $dirname = $pathparts['dirname'];

        set_time_limit(120);
        try {
            $version = $offlinequiz->algorithmversion;
            if ($version < 2) {
                // Create a new scanner for every page.
                $scanner = new \offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);
                // Try to load the image file.
                echo 'job ' . $queue->id . ': evaluating ' . $data->filename . "\n";
                $scannedpage = $scanner->load_image($data->filename);
                if ($scannedpage->status == 'ok') {
                    echo 'job ' . $queue->id . ': image loaded ' . $scannedpage->filename . "\n";
                } else if ($scannedpage->error == 'filenotfound') {
                    echo 'job ' . $queue->id . ': image file not found: ' . $scannedpage->filename . "\n";
                }
                    // Unset the origfilename because we don't need it in the DB.
                unset($scannedpage->origfilename);
                $scannedpage->offlinequizid = $offlinequiz->id;
                
                    // If we could load the image file, the status is 'ok', so we can check the page for errors.
                    if ($scannedpage->status == 'ok') {
                        // We autorotate so check_scanned_page will return a potentially new scanner and the scannedpage.
                        list($scanner, $scannedpage) = offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage,
                            $queue->importuserid, $coursecontext, true);
                    } else {
                        if (property_exists($scannedpage, 'id') && !empty($scannedpage->id)) {
                            $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
                        } else {
                            $scannedpage->queuedataid = $queuedata->id;
                            $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
                        }
                    }
                    echo 'job ' . $queue->id . ': scannedpage id ' . $scannedpage->id . "\n";
                    
                    // If the status is still 'ok', we can process the answers. This potentially submits the page and
                    // checks whether the result for a student is complete.
                    if ($scannedpage->status == 'ok') {
                        // We can process the answers and submit them if possible.
                        $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage,
                            $job->importuserid, $questionsperpage, $coursecontext, true);
                        echo 'job ' . $job->id . ': processed answers for ' . $scannedpage->id . "\n";
                    } else if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
                        // Already process the answers but don't submit them.
                        $scannedpage = offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage,
                            $job->importuserid, $questionsperpage, $coursecontext, false);
                        
                        // Compare the old and the new result wrt. the choices.
                        $scannedpage = offlinequiz_check_different_result($scannedpage);
                    }
                    
                    // If there is something to correct then store the hotspots for retrieval in correct.php.
                    if ($scannedpage->status != 'ok' && $scannedpage->error != 'couldnotgrab'
                        && $scannedpage->error != 'notadjusted' && $scannedpage->error != 'grouperror') {
                            $scanner->store_hotspots($scannedpage->id);
                        }
                        
                        if ($scannedpage->status == 'ok' || $scannedpage->status == 'submitted'
                            || $scannedpage->status == 'suspended' || $scannedpage->error == 'missingpages') {
                                // Mark the file as processed.
                                $DB->set_field('offlinequiz_queue_data', 'status', 'processed', array('id' => $data->id));
                            } else {
                                $DB->set_field('offlinequiz_queue_data', 'status', 'error', array('id' => $data->id));
                                $DB->set_field('offlinequiz_queue_data', 'error', $scannedpage->error, array('id' => $data->id));
                            }
                            if ($scannedpage->error == 'doublepage') {
                                $doubleentry++;
                            }
                } else {
                    $contextid = 0;
                    $engine = new \offlinequiz_result_import\offlinequiz_result_engine($offlinequiz, $context->id, $data->filename, 0);
                    $resultpage = $engine->scanpage();
                    $engine->save_page(2);
                }
                
            } catch (\Exception $e) {
                echo 'job ' . $job->id . ': ' . $e->getMessage() . "\n";
                $DB->set_field('offlinequiz_queue_data', 'status', 'error', array('id' => $data->id));
                $DB->set_field('offlinequiz_queue_data', 'error', 'couldnotgrab', array('id' => $data->id));
                $DB->set_field('offlinequiz_queue_data', 'info', $e->getMessage(), array('id' => $data->id));
                $scannedpage->status = 'error';
                $scannedpage->error = 'couldnotgrab';
                if ($scannedpage->id) {
                    $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
                } else {
                    $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
                }
            }
        } // End foreach jobdata.
        
        offlinequiz_update_grades($offlinequiz);
        
        $job->timefinish = time();
        $DB->set_field('offlinequiz_queue', 'timefinish', $job->timefinish, array('id' => $job->id));
        $job->status = 'finished';
        $DB->set_field('offlinequiz_queue', 'status', 'finished', array('id' => $job->id));
        
        echo date('Y-m-d-H:i') . ": Import queue with id $job->id imported.\n\n";
        
        if ($user = $DB->get_record('user',  array('id' => $job->importuserid))) {
            $mailtext = get_string('importisfinished', 'offlinequiz', format_text($offlinequiz->name, FORMAT_PLAIN));
            
            // How many pages have been imported successfully.
            $countsql = "SELECT COUNT(id)
                               FROM {offlinequiz_queue_data}
                              WHERE queueid = :queueid
                                AND status = 'processed'";
            $params = array('queueid' => $job->id);
            
            $mailtext .= "\n\n". get_string('importnumberpages', 'offlinequiz', $DB->count_records_sql($countsql, $params));
            
            // How many pages have an error.
            $countsql = "SELECT COUNT(id)
                               FROM {offlinequiz_queue_data}
                              WHERE queueid = :queueid
                                AND status = 'error'";
            
            $mailtext .= "\n". get_string('importnumberverify', 'offlinequiz', $DB->count_records_sql($countsql, $params));
            
            $mailtext .= "\n". get_string('importnumberexisting', 'offlinequiz', $doubleentry);
            
            $linkoverview = "$CFG->wwwroot/mod/offlinequiz/report.php?q={$job->offlinequizid}&mode=overview";
            $mailtext .= "\n\n". get_string('importlinkresults', 'offlinequiz', $linkoverview);
            
            $linkupload = "$CFG->wwwroot/mod/offlinequiz/report.php?q={$job->offlinequizid}&mode=rimport";
            $mailtext .= "\n". get_string('importlinkverify', 'offlinequiz', $linkupload);
            
            $mailtext .= "\n\n". get_string('importtimestart', 'offlinequiz', userdate($job->timestart));
            $mailtext .= "\n". get_string('importtimefinish', 'offlinequiz', userdate($job->timefinish));
            
            email_to_user($user, $CFG->noreplyaddress, get_string('importmailsubject', 'offlinequiz'), $mailtext);
        }
    }

   private function log_error($queuedata,$errorcode) {
       global $DB;
       $queuedata->status = 'error';
       $queuedata->error = $errorcode;
       $DB->update_record('offlinequiz_queuedata', $queuedata);
    }

}