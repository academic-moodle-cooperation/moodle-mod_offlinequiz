<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace offlinequiz_rimport\task\adhoc;

use function PHPUnit\Framework\throwException;

/**
 * file extractor for offlinequiz
 *
 * @package       offlinequiz_rimport
 * @subpackage    offlinequiz
 * @author        Thomas Wedekind
 * @copyright     2025 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 5.0
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class extract_files extends \core\task\adhoc_task {
    /**
     * constructor
     * @param int $queueid
     * @return extract_files
     */
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
        $queue = $DB->get_record('offlinequiz_queue', ['id' => $data->queueid]);
        $queue->timestart = time();
        $queue->status = 'processing';
        $cm = get_coursemodule_from_instance('offlinequiz', $queue->offlinequizid);
        $context = \context_module::instance($cm->id);
        $DB->update_record('offlinequiz_queue', $queue);
        try {
            if ($queuedatas = $DB->get_records('offlinequiz_queue_data', ['queueid' => $queue->id])) {
                // This is a rerun. Just queue all the files again and we're done.
                $DB->set_field('offlinequiz_queue_data', 'status', 'new', ['queueid' => $queue->id]);
                $DB->set_field('offlinequiz_queue_data', 'error', '', ['queueid' => $queue->id]);
                foreach ($queuedatas as $queuedata) {
                    $task = \offlinequiz_rimport\task\adhoc\scan_file::instance($queuedata->id);
                    // Execute ASAP.
                    $task->set_next_run_time(time());
                    \core\task\manager::queue_adhoc_task($task, true);
                }
                $DB->set_field('offlinequiz_queue', 'status', 'finished', ['id' => $queue->id]);
                return;
            }
            $dirname = "{$CFG->dataroot}/offlinequiz/import/$queue->id";
            $importfile = "$dirname/$queue->filename";
            if (!file_exists($importfile)) {

                $this->restorefile($context->id, $queue->id, $queue->filename, $importfile);
            }
            $files = [];
            require_once($CFG->libdir . '/filelib.php');
            $mimetype = mimeinfo('type', $importfile);
            if ($mimetype == 'application/zip') {
                $fp = get_file_packer('application/zip');
                $files = $fp->extract_to_pathname($importfile, $dirname);
                if ($files) {
                    $files = get_directory_list($dirname);
                    foreach ($files as $file) {
                        $mimetype = \mimeinfo('type', $file);
                        if ($mimetype == 'application/pdf') {
                            if (!$this->extract_pdf_to_tiff($dirname, $dirname . '/' . $file, $queue, true)) {
                                return;
                            }
                        }
                    }
                    $files = get_directory_list($dirname);
                    $files = $this->remove_original_file($files, $queue->filename);
                } else {
                    $queue->status = 'error';
                    $queue->error = 'couldnotunzip';
                    $queue->timefinish = time();
                    $DB->update_record('offlinequiz_queue', $queue);
                    return;
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
                $result = pclose($handle);
                if ($result) {
                    $queue->status = 'error';
                    $queue->error = 'couldnotextracttiff';
                    $queue->timefinish = time();
                    $DB->update_record('offlinequiz_queue', $queue);
                    return;
                }
                $files = get_directory_list($dirname);
                $files = $this->remove_original_file($files, $queue->filename);
            } else if ($mimetype == 'application/pdf') {
                if (!$files = $this->extract_pdf_to_tiff ( $dirname, $importfile, $queue)) {
                    return;
                }
            } else if (preg_match('/^image/' , $mimetype)) {
                $files[] = $queue->filename;
            } else {
                $queue->status = 'error';
                $queue->error = 'unknownmimetype';
                $queue->timefinish = time();
                $DB->update_record('offlinequiz_queue', $queue);
                return;
            }
            $threshold = get_config('offlinequiz', 'blackwhitethreshold');
            // Add the files to the job.
            foreach ($files as $file) {
                if ($threshold && $threshold > 0 && $threshold < 100) {
                    $this->convert_black_white("$dirname/$file", $threshold);
                }
                $jobfile = new \stdClass();
                $jobfile->queueid = $queue->id;
                $jobfile->filename = "$file";
                $jobfile->status = 'new';
                if (!$jobfile->id = $DB->insert_record('offlinequiz_queue_data', $jobfile)) {
                    $queue->status = 'error';
                    $queue->error = 'couldnotinsertjobs';
                    $queue->timefinish = time();
                    $DB->update_record('offlinequiz_queue', $queue);
                    return;
                }
                $this->create_queuedatafile_entry($context->id, $jobfile->id, $file, "$dirname/$file");
                $task = \offlinequiz_rimport\task\adhoc\scan_file::instance($jobfile->id);
                // Execute ASAP.
                $task->set_next_run_time(time());
                \core\task\manager::queue_adhoc_task($task, true);
            }
        } catch (\exception $e) {
            // Just in case if there is an error and it's still processing write that into the queue.
            if ($queue->status == 'processing') {
                $queue->status = 'error';
                $queue->error = 'couldnotextractpages';
                $DB->update_record('offlinequiz_queue', $queue);
            }
        }
    }

    /**
     * extract the pdf to a tiff
     * @param string dirname
     * @param string importfile
     */
    private function extract_pdf_to_tiff($dirname, $importfile, $queue, $unlink = false) {
        global $DB;
        // Extract each page to a separate file.
        $newfile = "$importfile-%03d.tiff";
        $handle = popen("cd $dirname;convert -type grayscale -density 300 '$importfile' '$newfile'", 'r');
        fread($handle, 1);
        while (!feof($handle)) {
            fread($handle, 1);
        }
        $returncode = pclose($handle);
        if ($returncode) {
            $queue->status = 'error';
            $queue->error = 'couldnotextractpdf';
            $queue->timefinish = time();
            $DB->update_record('offlinequiz_queue', $queue);
            return [];
        }
        $files = get_directory_list($dirname);

        $importfilename = substr($importfile, strrpos($importfile, '/') + 1);
        if ($unlink && count(get_directory_list($dirname)) > 1 && $key = array_search($importfilename, $files)) {
                unlink($importfile);
        }
        $files = $this->remove_original_file($files, $queue->filename);
        return $files;
    }
    /**
     * convert an image to black and white with the provided threshhold
     * @param mixed $file
     * @param mixed $threshold
     * @return void
     */
    private function convert_black_white($file, $threshold) {
        $command = "convert " . escapeshellarg(realpath($file)) .
            " -colorspace gray -threshold $threshold% " .  escapeshellarg(realpath($file));
        popen($command, 'r');
    }
    /**
     * remove original file from the list of files
     * @param mixed $files
     * @param mixed $original
     */
    private function remove_original_file($files, $original) {
        if (($key = array_search($original, $files)) !== false) {
            unset($files[$key]);
        }
        return $files;
    }
    /**
     * restore files to the path they are needed in
     * @param mixed $contextid
     * @param mixed $queueid
     * @param mixed $filename
     * @param mixed $restorepath
     * @return void
     */
    private function restorefile($contextid, $queueid, $filename, $restorepath) {
        $fs = get_file_storage();

        $pathhash = $fs->get_pathname_hash($contextid, 'mod_offlinequiz', 'queue', $queueid, '/', $filename);
        $file = $fs->get_file_by_hash($pathhash);
        $file->copy_content_to($restorepath);
    }
    /**
     * insert queuedata entry in the database
     * @param mixed $contextid
     * @param mixed $queuedataid
     * @param mixed $filename
     * @param mixed $pathname
     * @return \stored_file
     */
    private function create_queuedatafile_entry($contextid, $queuedataid, $filename, $pathname) {
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => $contextid,      // ID of context.
            'component' => 'mod_offlinequiz', // Usually = table name.
            'filearea'  => 'queuedata',      // Usually = table name.
            'itemid'    => $queuedataid,                 // Usually = ID of row in table.
            'filepath'  => '/',                // Any path beginning and ending in.
        ]; // Any filename.

        $filerecord['filename'] = $filename;
        if (!$fs->file_exists($contextid, 'mod_offlinequiz', 'queue', $queuedataid, '/', $filename)) {
            $newfile = $fs->create_file_FROM_pathname($filerecord, $pathname);
        }
        return $newfile;
    }

}
