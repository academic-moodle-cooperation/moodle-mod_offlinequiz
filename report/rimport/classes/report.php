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
 * The results import report for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace offlinequiz_rimport;
defined('MOODLE_INTERNAL') || die();
use mod_offlinequiz\default_report;
use \context_module;
use \moodle_url;
use \moodle_exception;
use \navigation_node;
use \offlinequiz_upload_form;
use \stdClass;

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/upload_form.php');
require_once($CFG->libdir . '/filelib.php');

class report extends default_report {

    public $context;
    /**
     * (non-PHPdoc)
     * @see offlinequiz_default_report::display()
     */
    public function display($offlinequiz, $cm, $course) {
        global $CFG, $COURSE, $DB, $OUTPUT, $USER;

        $this->context = context_module::instance($cm->id);

        $pageoptions = array();
        $pageoptions['id'] = $cm->id;
        $pageoptions['mode'] = 'rimport';

        $reporturl = new moodle_url('/mod/offlinequiz/report.php', $pageoptions);

        $action = optional_param('action', '', PARAM_ACTION);
        if ($action != 'delete') {
            $this->print_header_and_tabs($cm, $course, $offlinequiz, 'rimport');
            if (!$offlinequiz->docscreated) {
                echo $OUTPUT->heading(get_string('nopdfscreated', 'offlinequiz'));
                return true;
            }

            echo $OUTPUT->box_start('linkbox');
            echo $OUTPUT->heading_with_help(get_string('resultimport', 'offlinequiz'), 'importnew', 'offlinequiz');
            echo $OUTPUT->box_end();
        }

        $importform = new offlinequiz_upload_form($reporturl,
                array('offlinequiz' => $offlinequiz, 'context' => $this->context));

        // Has the user submitted a file?
        if ($fromform = $importform->get_data() && confirm_sesskey()) {
            // Work out if this is an uploaded file
            // or one from the filesarea.
            $realfilename = $importform->get_new_filename('newfile');
            //escape filename for security reasons
            $realfilename = preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $realfilename);;
            // Create a new queue job.
            $job = new \stdClass();
            $job->offlinequizid = $offlinequiz->id;
            $job->importuserid = $USER->id;
            $job->timecreated = time();
            $job->timestart = 0;
            $job->timefinish = 0;
            $job->status = 'uploading';
            $job->filename= $realfilename;
            if (!$job->id = $DB->insert_record('offlinequiz_queue', $job)) {
                echo $OUTPUT->notification(get_string('couldnotcreatejob', 'offlinequiz_rimport'), 'notifyproblem');
            }
            // Create a unique temp dir.
            $dirname = "{$CFG->dataroot}/offlinequiz/import/$job->id";
            check_dir_exists($dirname, true, true);

            $importfile = $dirname . '/' . $realfilename;

            if (!$importform->save_file('newfile', $importfile, true)) {
                $job->status = 'error';
                $job->error = 'uploadproblem';
                $job->filename = $realfilename;
                $DB->update_record('offlinequiz_queue', $job);
                throw new moodle_exception('uploadproblem');
            }
            $task = \offlinequiz_rimport\task\adhoc\extract_files::instance($job->id);
            //Execute ASAP.
            $task->set_next_run_time(time());
            \core\task\manager::queue_adhoc_task($task, true);
            // Notify the user.
            echo $OUTPUT->notification(get_string('addingfilestoqueue', 'offlinequiz_rimport'), 'notifysuccess');
            echo $OUTPUT->continue_button($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id . '&mode=correct');
        } else {

            // Print info about offlinequiz_queue jobs.
            $sql = 'SELECT COUNT(*) as count
                      FROM {offlinequiz_queue} q
                      JOIN {offlinequiz_queue_data} qd on q.id = qd.queueid
                     WHERE (qd.status = :status1 OR qd.status = :status3)
                       AND q.offlinequizid = :offlinequizid
                       AND q.status = :status2
                    ';
            $newforms = $DB->get_record_sql($sql, array('offlinequizid' => $offlinequiz->id, 'status1' => 'new',
                    'status2' => 'new', 'status3' => ''));
            $processingforms = $DB->get_record_sql($sql, array('offlinequizid' => $offlinequiz->id, 'status1' => 'processing',
                    'status2' => 'processing', 'status3' => 'new'));

            if ($newforms->count > 0) {
                echo $OUTPUT->notification(get_string('newformsinqueue', 'offlinequiz_rimport', $newforms->count), 'notifysuccess');
            }
            if ($processingforms->count > 0) {
                echo $OUTPUT->notification(get_string('processingformsinqueue', 'offlinequiz_rimport', $processingforms->count),
                        'notifysuccess');
            }

            $action = optional_param('action', '', PARAM_ACTION);

            switch ($action) {
                case 'delete':
                    if (confirm_sesskey()) {

                        $selectedpageids = array();
                        $params = (array) data_submitted();

                        foreach ($params as $key => $value) {
                            if (preg_match('!^p([0-9]+)$!', $key, $matches)) {
                                $selectedpageids[] = $matches[1];
                            }
                        }

                        foreach ($selectedpageids as $pageid) {
                            if ($pageid && ($page = $DB->get_record('offlinequiz_scanned_pages', array('id' => $pageid)))) {
                                offlinequiz_delete_scanned_page($page, $this->context);
                            }
                        }

                        redirect($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id . '&amp;mode=rimport');
                    } else {
                        throw new \moodle_exception('invalidsesskey');
                    }
                    break;
                default:
                    // Display the upload form.
                    $importform->display();
            }
        }
    }

    // Add navigation nodes to mod_offlinequiz_result.
    public function add_to_navigation(navigation_node $navigation, $cm, $offlinequiz): navigation_node
    {     
        $parentnode = $navigation->get('mod_offlinequiz_results');
        $parentnode->add(text: get_string('importforms', 'offlinequiz_rimport'),
                        action:  new moodle_url('/mod/offlinequiz/report.php', ['q' => $offlinequiz->id, 'mode' => 'rimport']),
                        key: $this->get_navigation_key());
        return $navigation;
    }
    public function get_report_title(): string {
        return get_string('resultimport', 'offlinequiz');
    }
    public function get_navigation_key(): string {
        return 'tabofflinequizupload';
    }
}
