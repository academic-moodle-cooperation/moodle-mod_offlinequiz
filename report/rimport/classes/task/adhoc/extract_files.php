<?php


namespace offlinequiz_rimport\task\adhoc;

/**
 * An example of an adhoc task.
 */
class extract_files extends \core\task\adhoc_task {
    
    public static function instance(
        int $queueid
        ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'queueid' => $queueid,
        ]);
        return $task;
    }
    
    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        $data = $this->get_custom_data();
        $queue = $DB->get_record('offlinequiz_queue',['id' => $data->queueid]);
        $queue->timestart = time();
        $queue->status = 'processing';
        $DB->update_record('offlinequiz_queue',$queue);
        try {
        $dirname = "{$CFG->dataroot}/offlinequiz/import/$queue->id";
        $importfile = "$dirname/$queue->filename";
        $files = array();
        require_once $CFG->libdir . '/filelib.php';
        $mimetype = mimeinfo('type', $importfile);
        if ($mimetype == 'application/zip') {
            $fp = get_file_packer('application/zip');
            $files = $fp->extract_to_pathname($importfile, $dirname);
            if ($files) {
                unlink($importfile);
                $files = get_directory_list($dirname);
                foreach ($files as $file) {
                    $mimetype = \mimeinfo('type', $file);
                    if ($mimetype == 'application/pdf') {
                        $this->extract_pdf_to_tiff($dirname, $dirname . '/' . $file);
                    }
                }
                $files = get_directory_list($dirname);
            } else {
                $queue->status = 'error';
                $queue->error = 'couldnotunzip';
                $DB->update_record('offlinequiz_queue', $queue);
            }
        } else if ($mimetype == 'image/tiff') {
            // Extract each TIFF subfiles into a file.
            // (it would be better to know if there are subfiles, but it is pretty cheap anyway).
            $newfile = "$importfile-%d.tiff";
            $handle = popen("convert '$importfile' '$newfile'", 'r');
            fread($handle, 1);
            while (!feof($handle)) {
                fread($handle, 1);
            }
            pclose($handle);
            if (count(get_directory_list($dirname)) > 1) {
                // It worked, remove original.
                unlink($importfile);
            }
            $files = get_directory_list($dirname);
        } else if ($mimetype == 'application/pdf') {
            $files = $this->extract_pdf_to_tiff ( $dirname, $importfile );
        } else if (preg_match('/^image/' , $mimetype)) {
            
            $files[] = $queue->filename;
        }
        $added = count($files);
        $threshold = get_config('offlinequiz', 'blackwhitethreshold');
        // Add the files to the job.
        foreach ($files as $file) {
            if ($threshold && $threshold > 0 && $threshold < 100) {
                $this->convert_black_white("$dirname/$file", $threshold);
            }
            $jobfile = new \stdClass();
            $jobfile->queueid = $queue->id;
            $jobfile->filename = "$dirname/$file";
            $jobfile->status = 'new';
            if (!$jobfile->id = $DB->insert_record('offlinequiz_queue_data', $jobfile)) {
                echo $OUTPUT->notification(get_string('couldnotcreatejobfile', 'offlinequiz_rimport'), 'notifyproblem');
                $added--;
            }
       }
       $queue->status = 'finished';
       $queue->timefinished = time();
       
    } finally {
        // Just in case if there is an error and it's still processing write that into the queue.
        if($queue->status == 'processing') {
            $queue->status = 'error';
            $queue->error = 'couldnotextractpages';
            $DB->update_record('offlinequiz_queue', $queue);
        }
        }
    }

    /**
     * @param dirname
     * @param importfile
     */
    private function extract_pdf_to_tiff($dirname, $importfile) {
        // Extract each page to a separate file.
        $newfile = "$importfile-%03d.tiff";
        $handle = popen("convert -type grayscale -density 300 '$importfile' '$newfile'", 'r');
        fread($handle, 1);
        while (!feof($handle)) {
            fread($handle, 1);
        }
        pclose($handle);
        if (count(get_directory_list($dirname)) > 1) {
            // It worked, remove original.
            unlink($importfile);
        }
        $files = get_directory_list($dirname);
        return $files;
    }

    private function convert_black_white($file, $threshold) {
        $command = "convert " . realpath($file) . " -threshold $threshold% " . realpath($file);
        popen($command, 'r');
    }
}