<?php
namespace offlinequiz_rimport\task\adhoc;

require_once ($CFG->dirroot . '/mod/offlinequiz/lib.php');
require_once ($CFG->dirroot . '/mod/offlinequiz/locallib.php');
require_once ($CFG->dirroot . '/mod/offlinequiz/evallib.php');
require_once ($CFG->dirroot . '/mod/offlinequiz/report/rimport/scanner.php');

/**
 * An example of an adhoc task.
 */
class scan_file extends \core\task\adhoc_task
{

    public static function instance(int $queuedataid): self
    {
        $task = new self();
        $task->set_custom_data((object) [
            'queuedataid' => $queuedataid
        ]);
        return $task;
    }

    public function execute()
    {
        global $DB, $CFG;
        $data = $this->get_custom_data();
        $queuedata = $DB->get_record('offlinequiz_queue_data', [
            'id' => $data->queuedataid
        ]);
        $queue = $DB->get_record('offlinequiz_queue', [
            'id' => $queuedata->queueid
        ]);
        $queuedata->timestart = time();
        $queuedata->status = 'processing';
        $DB->update_record('offlinequiz_queue_data', $queuedata);
        // Set up the context for this job.
        if (! $offlinequiz = $DB->get_record('offlinequiz', array(
            'id' => $queue->offlinequizid
        ))) {
            $this->log_error($queuedata, 'offlinequiznotfound');
        }
        if (! $course = $DB->get_record('course', array(
            'id' => $offlinequiz->course
        ))) {
            $this->log_error($queuedata, 'coursenotfound');
        }

        if (! $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $course->id)) {
            $this->log_error($queuedata, 'coursemodulenotfound');
        }
        if (! $context = \context_module::instance($cm->id)) {
            $this->log_error($queuedata, 'contextnotfound');
        }
        if (! $coursecontext = \context_course::instance($course->id)) {
            $this->log_error($queuedata, 'coursecontextnotfound');
        }
        if (! $groups = $DB->get_records('offlinequiz_groups', [
            'offlinequizid' => $offlinequiz->id
        ], 'groupnumber', '*', 0, $offlinequiz->numgroups)) {
            $this->log_error($queuedata, 'nogroupsfound');
        }
        $dirname = "{$CFG->dataroot}/offlinequiz/import/$queue->id";
        $importfile = "$dirname/$queuedata->filename";
        if(!file_exists($importfile)) {
            
            $this->restorefile($context->id,$queue->id,$queue->filename, $importfile);
        }
        list ($maxquestions, $maxanswers, $formtype, $questionsperpage) = \offlinequiz_get_question_numbers($offlinequiz, $groups);

        set_time_limit(120);
        try {
            $version = $offlinequiz->algorithmversion;
            if ($version < 2) {
                // Create a new scanner for every page.
                $scanner = new \offlinequiz_page_scanner($offlinequiz, $context->id, $maxquestions, $maxanswers);
                // Try to load the image file.
                echo 'job ' . $queue->id . ': evaluating ' . $queuedata->filename . "\n";
                $scannedpage = $scanner->load_image("$dirname/$queuedata->filename");
                $scannedpage->id = $DB->get_field('offlinequiz_scanned_pages', 'id', ['queuedataid' => $queuedata->id],IGNORE_MISSING);
                if ($scannedpage->status == 'ok') {
                    echo 'job ' . $queue->id . ': image loaded ' . $scannedpage->filename . "\n";
                } else if ($scannedpage->error == 'filenotfound') {
                    echo 'job ' . $queue->id . ': image file not found: ' . $scannedpage->filename . "\n";
                }
                $scannedpage->offlinequizid = $offlinequiz->id;
                $scannedpage->queuedataid = $queuedata->id;

                // If we could load the image file, the status is 'ok', so we can check the page for errors.
                if ($scannedpage->status == 'ok') {
                    // We autorotate so check_scanned_page will return a potentially new scanner and the scannedpage.
                    list ($scanner, $scannedpage) = \offlinequiz_check_scanned_page($offlinequiz, $scanner, $scannedpage, $queue->importuserid, $coursecontext, true);
                } else {
                    if (property_exists($scannedpage, 'id') && ! empty($scannedpage->id)) {
                        $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
                    } else {
                        $scannedpage->id = $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
                    }
                }
                echo 'job ' . $queue->id . ': scannedpage id ' . $scannedpage->id . "\n";

                // If the status is still 'ok', we can process the answers. This potentially submits the page and
                // checks whether the result for a student is complete.
                if ($scannedpage->status == 'ok') {
                    // We can process the answers and submit them if possible.
                    $scannedpage = \offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $queue->importuserid, $questionsperpage, $coursecontext, true);
                    echo 'job ' . $queue->id . ': processed answers for ' . $scannedpage->id . "\n";
                } else if ($scannedpage->status == 'error' && $scannedpage->error == 'resultexists') {
                    // Already process the answers but don't submit them.
                    $scannedpage = \offlinequiz_process_scanned_page($offlinequiz, $scanner, $scannedpage, $queue->importuserid, $questionsperpage, $coursecontext, false);

                    // Compare the old and the new result wrt. the choices.
                    $scannedpage = \offlinequiz_check_different_result($scannedpage);
                }

                // If there is something to correct then store the hotspots for retrieval in correct.php.
                if ($scannedpage->status != 'ok' && $scannedpage->error != 'couldnotgrab' && $scannedpage->error != 'notadjusted' && $scannedpage->error != 'grouperror') {
                    $scanner->store_hotspots($scannedpage->id);
                }

                if ($scannedpage->status == 'ok' || $scannedpage->status == 'submitted' || $scannedpage->status == 'suspended' || $scannedpage->error == 'missingpages') {
                    // Mark the file as processed.
                    $DB->set_field('offlinequiz_queue_data', 'status', 'processed', array(
                        'id' => $queuedata->id
                    ));
                } else {
                    $DB->set_field('offlinequiz_queue_data', 'status', 'error', array(
                        'id' => $queuedata->id
                    ));
                    $DB->set_field('offlinequiz_queue_data', 'error', $scannedpage->error, array(
                        'id' => $queuedata->id
                    ));
                }
            } else {
                $contextid = 0;
                $engine = new \offlinequiz_result_import\offlinequiz_result_engine($offlinequiz, $context->id, $queuedata->filename, 0);
                $resultpage = $engine->scanpage();
                $engine->save_page(2);
            }
            $this->send_notifications($queue->id);
        } catch (\Exception $e) {
            echo 'job ' . $queue->id . ': ' . $e->getMessage() . "\n";
            $DB->set_field('offlinequiz_queue_data', 'status', 'error', array(
                'id' => $queuedata->id
            ));
            $DB->set_field('offlinequiz_queue_data', 'error', 'couldnotgrab', array(
                'id' => $queuedata->id
            ));
            $DB->set_field('offlinequiz_queue_data', 'info', $e->getMessage(), array(
                'id' => $queuedata->id
            ));
            $scannedpage->status = 'error';
            $scannedpage->error = 'couldnotgrab';
            if ($scannedpage->id) {
                $DB->update_record('offlinequiz_scanned_pages', $scannedpage);
            } else {
                $DB->insert_record('offlinequiz_scanned_pages', $scannedpage);
            }
            $this->send_notifications($queue->id);
        }

        
    }
    
    private function send_notifications($queueid) {
        $task = \offlinequiz_rimport\task\adhoc\send_notifications::instance($queueid);
        //Execute ASAP.
        $task->set_next_run_time(time() + 60);
        \core\task\manager::queue_adhoc_task($task, true);
    }
    
    private function restorefile($contextid, $queuedataid, $filename, $restorepath) {
        $fs = get_file_storage();
        
        $pathhash = $fs->get_pathname_hash($contextid, 'mod_offlinequiz', 'queuedata', $queuedataid, '/', $filename);
        $file = $fs->get_file_by_hash($pathhash);
        $file->copy_content_to($restorepath);
    }

    private function log_error($queuedata, $errorcode)
    {
        global $DB;
        $queuedata->status = 'error';
        $queuedata->error = $errorcode;
        $DB->update_record('offlinequiz_queuedata', $queuedata);
    }
}