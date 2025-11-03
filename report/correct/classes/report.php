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

namespace offlinequiz_correct;
use context_module;
use navigation_node;
use moodle_url;
use offlinequiz_correct\table\selectall_table;
use popup_action;
use DateTime;
use pix_icon;
use mod_offlinequiz\default_report;
/**
 * The results import report for offlinequizzes
 *
 * @package       offlinequiz_correct
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.1
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class report extends default_report {
    /**
     * offlinequiz context
     * @var \context_module
     */
    public $context;
    /**
     * list of users for this report
     * @var array
     */
    public static $users = [];
    /**
     * print the error report for this offlinequiz
     * @param \stdClass $offlinequiz
     * @return void
     */
    private function print_error_report($offlinequiz) {
        global $CFG, $DB, $OUTPUT;

        $offlinequizconfig = get_config('offlinequiz');

        $nologs = optional_param('nologs', 0, PARAM_INT);
        $pagesize = optional_param('pagesize', 10, PARAM_INT);

        $letterstr = 'ABCDEFGHIJKL';

        $tableparams = ['q' => $offlinequiz->id, 'mode' => 'correct', 'action' => 'delete',
                'strreallydel'  => addslashes(get_string('deletepagecheck', 'offlinequiz'))];

        $table = new selectall_table('mod_offlinequiz_import_report', 'report.php', $tableparams);

        $tablecolumns = ['checkbox', 'counter', 'userkey', 'groupnumber', 'pagenumber', 'time', 'error', 'info', 'link'];
        $tableheaders = ['', '#', offlinequiz_get_id_field_name(),
                get_string('group'), get_string('page'), get_string('importedon', 'offlinequiz_rimport'),
                get_string('error'), get_string('info'), ''];

        $table->initialbars(true);
        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot . '/mod/offlinequiz/report.php?mode=correct&amp;q=' .
                $offlinequiz->id . '&amp;nologs=' . $nologs .
                '&amp;pagesize=' . $pagesize);

        $table->sortable(true, 'time'); // Sorted by lastname by default.
        $table->initialbars(true);

        $table->column_class('checkbox', 'checkbox');
        $table->column_class('counter', 'counter');
        $table->column_class('username', 'username');
        $table->column_class('group', 'group');
        $table->column_class('page', 'page');
        $table->column_class('time', 'time');
        $table->column_class('error', 'error');
        $table->column_class('link', 'link');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('cellpadding', '4');
        $table->set_attribute('id', 'errorpages');
        $table->set_attribute('class', 'errorpages');
        $table->set_attribute('align', 'center');
        $table->set_attribute('border', '1');

        $table->no_sorting('checkbox');
        $table->no_sorting('counter');
        $table->no_sorting('info');
        $table->no_sorting('link');

        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();

        // Construct the SQL.

        $sql = "SELECT *
                  FROM {offlinequiz_scanned_pages}
                 WHERE offlinequizid = :offlinequizid
                   AND (status = 'error'
                        OR status = 'suspended'
                        OR error = 'missingpages')";

        $params = ['offlinequizid' => $offlinequiz->id];

        // Add extra limits due to sorting by question grade.
        if ($sort = $table->get_sql_sort()) {
            if (
                strpos($sort, 'checkbox') === false && strpos($sort, 'counter') === false &&
                    strpos($sort, 'info') === false && strpos($sort, 'link') === false
            ) {
                $sql .= ' ORDER BY ' . $sort;
            }
        }

        $errorpages = $DB->get_records_sql($sql, $params);

        $strtimeformat = get_string('strftimedatetime');

        // Options for the popup_action.
        $options = [];
        $options['height'] = 1200; // Optional.
        $options['width'] = 1170; // Optional.
        $options['resizable'] = false;

        $counter = 1;

        foreach ($errorpages as $page) {
            if ($page->error == 'filenotfound') {
                $actionlink = '';
            } else {
                if ($page->error == 'missingpages') {
                    $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/image.php?pageid=' . $page->id .
                            '&resultid=' . $page->resultid);
                    $title = get_string('showpage', 'offlinequiz_rimport');
                } else {
                    $url = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/correct.php?pageid=' . $page->id);
                    $title = get_string('correcterror', 'offlinequiz_rimport');
                }

                $actionlink = $OUTPUT->action_link($url, $title, new popup_action('click', $url, 'correct' .
                        $page->id, $options));
            }

            $groupstr = '?';
            $groupnumber = $page->groupnumber;
            if ($groupnumber > 0 && $groupnumber <= $offlinequiz->numgroups) {
                $groupstr = $letterstr[$page->groupnumber - 1];
            }

            $errorstr = '';
            if (!empty($page->error)) {
                $errorstr = get_string('error' . $page->error, 'offlinequiz_rimport');
            }
            if ($page->status == 'suspended') {
                $errorstr = get_string('waitingforanalysis', 'offlinequiz_rimport');
            }
            $row = [
                    '<input type="checkbox" name="p' . $page->id . '" value="' . $page->id . '" class="select-multiple-checkbox"/>',
                    $counter . '&nbsp;',
                    $page->userkey,
                    $groupstr,
                    empty($page->pagenumber) ? '?' : $page->pagenumber,
                    userdate($page->time, $strtimeformat),
                    $errorstr,
                    $page->info,
                    $actionlink,
            ];
            $table->add_data($row);
            $counter++;
        }
        $table->print_html();
    }


    /**
     * display the report
     * @param mixed $offlinequiz
     * @param mixed $cm
     * @param mixed $course
     * @throws \moodle_exception
     * @return bool
     */
    public function display($offlinequiz, $cm, $course) {
        global $CFG, $DB, $OUTPUT;

        $this->context = context_module::instance($cm->id);

        $pageoptions = [];
        $pageoptions['id'] = $cm->id;
        $pageoptions['mode'] = 'correct';

        $action = optional_param('action', '', PARAM_ACTION);
        if ($action == 'download') {
            $queueid = optional_param('queueid', 0, PARAM_INT);
            $queuedataid = optional_param('queuedataid', 0, PARAM_INT);
            if ($queueid) {
                $queue = $DB->get_record('offlinequiz_queue', ['id' => $queueid]);
                if ($queue->offlinequizid == $offlinequiz->id) {
                    $path = "$CFG->dataroot/offlinequiz/import/$queueid/$queue->filename";
                    send_file($path, $queue->filename);
                    die();
                }
            } else if ($queuedataid) {
                $queuedata = $DB->get_record('offlinequiz_queue_data', ['id' => $queuedataid]);
                if ($queuedata) {
                    $queue = $DB->get_record('offlinequiz_queue', ['id' => $queuedata->queueid]);
                    if ($queue->offlinequizid == $offlinequiz->id) {
                        $filename = pathinfo($queuedata->filename)['basename'];
                        $path = "$CFG->dataroot/offlinequiz/import/$queuedata->queueid/$filename";
                        send_file($path, $filename);
                        die();
                    }
                }
            }
        }
        if ($action != 'delete' && $action != 'download') {
            $this->print_header_and_tabs($cm, $course, $offlinequiz, 'correct');
            if (!$offlinequiz->docscreated) {
                echo $OUTPUT->heading(get_string('nopdfscreated', 'offlinequiz'));
                return true;
            }

            echo $OUTPUT->box_start('linkbox');
            echo $OUTPUT->heading_with_help(get_string('correctionheader', 'offlinequiz'), 'correctionheader', 'offlinequiz');
            echo $OUTPUT->box_end();
        }
        // Print info about offlinequiz_queue jobs.
        $sql = 'SELECT COUNT(*) as count
                  FROM {offlinequiz_queue} q
                  JOIN {offlinequiz_queue_data} qd on q.id = qd.queueid
                 WHERE (qd.status = :status1 OR qd.status = :status3)
                   AND q.offlinequizid = :offlinequizid
                   AND q.status = :status2
                ';
        $newforms = $DB->get_record_sql($sql, ['offlinequizid' => $offlinequiz->id, 'status1' => 'new',
                'status2' => 'new', 'status3' => '']);
        $processingforms = $DB->get_record_sql($sql, ['offlinequizid' => $offlinequiz->id, 'status1' => 'processing',
                'status2' => 'processing', 'status3' => 'new']);

        if ($newforms->count > 0) {
            echo $OUTPUT->notification(get_string('newformsinqueue', 'offlinequiz_rimport', $newforms->count), 'notifysuccess');
        }
        if ($processingforms->count > 0) {
            echo $OUTPUT->notification(
                get_string('processingformsinqueue', 'offlinequiz_rimport', $processingforms->count),
                'notifysuccess'
            );
        }

        $action = optional_param('action', '', PARAM_ACTION);

        switch ($action) {
            case 'delete':
                if (confirm_sesskey()) {
                    $selectedpageids = [];
                    $params = (array) data_submitted();

                    foreach ($params as $key => $value) {
                        if (preg_match('!^p([0-9]+)$!', $key, $matches)) {
                            $selectedpageids[] = $matches[1];
                        }
                    }

                    foreach ($selectedpageids as $pageid) {
                        if ($pageid && ($page = $DB->get_record('offlinequiz_scanned_pages', ['id' => $pageid]))) {
                            offlinequiz_delete_scanned_page($page, $this->context);
                        }
                    }

                    redirect($CFG->wwwroot . '/mod/offlinequiz/report.php?q=' . $offlinequiz->id . '&amp;mode=correct');
                } else {
                    throw new \moodle_exception('invalidsesskey');
                }
        }
        // Print the table with answer forms that need correction.
        $this->print_error_report($offlinequiz);
        $this->display_uploaded_files($offlinequiz, $cm);
    }
    /**
     * display of all uploaded files in this offlinequiz
     * @param mixed $offlinequiz
     * @param mixed $cm
     * @return void
     */
    private function display_uploaded_files($offlinequiz, $cm) {
        global $DB, $OUTPUT;
        $queues = $DB->get_records('offlinequiz_queue', ['offlinequizid' => $offlinequiz->id], 'timecreated DESC');

        $sql = "SELECT qd.id queuedataid, q.id queueid, qd.status status, qd.filename filename, sp.id scannedpageid,
                       sp.error error, sp.userkey userkey
                  FROM {offlinequiz_queue} q
                  JOIN {offlinequiz_queue_data} qd on q.id = qd.queueid
             LEFT JOIN {offlinequiz_scanned_pages} sp ON sp.queuedataid = qd.id
                 WHERE q.offlinequizid = :offlinequizid";
        $queuefiles = $DB->get_records_sql($sql, ['offlinequizid' => $offlinequiz->id]);
        $queuefilesmatrix = [];
        if ($queuefiles) {
            foreach ($queuefiles as $queuefile) {
                if ($queuefile->queuedataid) {
                    if (!array_key_exists($queuefile->queueid, $queuefilesmatrix)) {
                        $queuefilesmatrix[$queuefile->queueid] = [];
                    }
                    $queuefilesmatrix[$queuefile->queueid][$queuefile->queuedataid] = $queuefile;
                }
            }
        }
        if ($queues) {
            $context = [];
            $context['queues'] = 0;
            $elements = [];
            foreach ($queues as $queue) {
                $element = [];

                $element['importedby'] = $this->get_user_name($queue->importuserid);
                $importedbylink = new moodle_url(
                    '/user/view.php',
                    ['id' => $queue->importuserid, 'course' => $offlinequiz->course]
                );
                $element['importedbylink'] = $importedbylink->out();
                $link = new moodle_url(
                    '/mod/offlinequiz/report.php',
                    ['action' => 'download', 'mode' => 'correct', 'queueid' => $queue->id, 'id' => $cm->id]
                );
                $element['downloadlink'] = $link->out();
                $element['documentname'] = $queue->filename;
                $element['queueid'] = $queue->id;
                if (key_exists($queue->id, $queuefilesmatrix)) {
                    $element['numberofpages'] = count($queuefilesmatrix[$queue->id]);
                }
                $date = new DateTime();
                $date->setTimestamp(intval($queue->timecreated));
                $element['importdate'] = userdate($date->getTimestamp());
                if ($queue->timestart) {
                    $date->setTimestamp(intval($queue->timestart));
                    $element['starttime'] = userdate($date->getTimestamp());
                } else {
                    $element['starttime'] = get_string('queuenotstarted', 'offlinequiz');
                }
                if ($queue->timefinish) {
                    $date->setTimestamp(intval($queue->timefinish));
                    $element['finishtime'] = userdate($date->getTimestamp());
                } else {
                    $element['finishtime'] = get_string('queuenotfinished', 'offlinequiz');
                }
                $element['expandedcontent'] =
                    $this->get_page_content($cm, $offlinequiz, $queue->id, $queuefilesmatrix);
                $element['collapsible'] = true;

                $element['queuestatusdone'] = false;
                $element['queuestatuserror'] = false;
                $element['queuestatusprocessing'] = false;
                $element['queuestatusnotstarted'] = false;
                if (!$queue->timestart) {
                    $element['queuestatusnotstarted'] = true;
                } else if ($this->queuehaserrors($queue)) {
                    $element['queuestatuserror'] = true;
                    if ($queue->error) {
                        $element['queueerror'] = get_string($queue->error, 'offlinequiz');
                    }
                } else if (!$queue->timefinish) {
                    $element['queuestatusprocessing'] = true;
                } else {
                    $element['queuestatusdone'] = true;
                }
                $elements[] = $element;
            }
            $context['queues'] = $elements;
            $link = new moodle_url(
                '/mod/offlinequiz/report.php',
                ['mode' => 'rimport', 'id' => $cm->id]
            );
            $context['uploadfurtherfileslink'] = $link->out();
            $rendered = $OUTPUT->render_from_template('mod_offlinequiz/correct_queue_list', $context);
            echo $rendered;
        }
    }

    /**
     * page content of this report
     * @param mixed $cm
     * @param mixed $offlinequiz
     * @param mixed $queueid
     * @param mixed $queuepagematrix
     * @return bool|string
     */
    public function get_page_content($cm, $offlinequiz, $queueid = 0, $queuepagematrix = []) {
        $offlinequizconfig = get_config('offlinequiz');
        global $OUTPUT, $DB;
        $options = [];
        $options['height'] = 1200; // Optional.
        $options['width'] = 1170; // Optional.
        $options['resizable'] = false;
        $rendered = '';
        if ($queueid && array_key_exists($queueid, $queuepagematrix)) {
            $context = [];
            $context['files'] = [];

            foreach ($queuepagematrix[$queueid] as $queuedataid => $page) {
                $filecontext = [];
                $filecontext['filename'] = substr($page->filename, strrpos($page->filename, '/') + 1);
                $filecontext['fileurl'] = new moodle_url(
                    '/mod/offlinequiz/report.php',
                    ['action' => 'download', 'mode' => 'correct', 'queuedataid' => $queuedataid, 'id' => $cm->id]
                );
                if ($page->scannedpageid) {
                    $editurl = new moodle_url('/mod/offlinequiz/correct.php', ['pageid' => $page->scannedpageid]);
                    $filecontext['editurl'] = $OUTPUT->action_link(
                        $editurl,
                        $OUTPUT->pix_icon('t/edit', get_string('queuefilecorrectionhovertext', 'offlinequiz')),
                        new popup_action(
                            'click',
                            $editurl,
                            'correct' .
                            $page->scannedpageid,
                            $options
                        )
                    );
                }
                $filecontext['statusmessage'] = get_string('queuefilestatusmessage_' . $page->status, 'offlinequiz');
                if ($page->status == 'OK'  || $page->status == 'error' || $page->status == 'submitted') {
                    $filecontext['evaluated'] = true;
                }
                if (
                    $page->status == 'OK'
                    || $page->status == 'submitted'
                    || $page->status == 'missingpages'
                    || $page->status == 'processed'
                ) {
                    $filecontext['filestatusdone'] = true;
                } else if ($page->status == 'error') {
                    $filecontext['filestatuserror'] = true;
                } else if ($page->status == 'new') {
                    $filecontext['filestatusnotstarted'] = true;
                } else {
                    $filecontext['filestatusprocessing'] = true;
                }
                if ($page->status == 'error' && $page->error) {
                    $filecontext['statusmessage'] = get_string('error' . $page->error, 'offlinequiz_rimport');
                } else if (!empty($page->userkey) && $page->userkey) {
                    $user = offlinequiz_get_user_by_userkey($offlinequiz, $page->userkey);
                    $filecontext['statusmessage'] = fullname($user);
                }
                $context['files'][] = $filecontext;
            }
            $rendered = $OUTPUT->render_from_template('mod_offlinequiz/correct_files_list', $context);
        }
        return $rendered;
    }

    /**
     * check if the queue has errors
     * @param mixed $queue
     * @return bool
     */
    private function queuehaserrors($queue) {
        global $DB;
        if ($queue->status == 'error') {
            return true;
        }
        if ($DB->record_exists('offlinequiz_queue_data', ['queueid' => $queue->id, 'status' => 'error'])) {
            return true;
        }
        return false;
    }
    /**
     * get the user name to a user
     * @param mixed $userid
     * @return mixed|string
     */
    public function get_user_name($userid) {
        global $DB;
        if (!array_key_exists($userid, $this::$users)) {
            $user = $DB->get_record('user', ['id' => $userid]);
            $this::$users[$userid] = fullname($user);
        }
        return $this::$users[$userid];
    }

    /**
     * Add navigation nodes to mod_offlinequiz_result.
     * @param \navigation_node $navigation
     * @param mixed $cm
     * @param mixed $offlinequiz
     * @return navigation_node
     */
    public function add_to_navigation(navigation_node $navigation, $cm, $offlinequiz): navigation_node {
        // TO DO: Move string to subplugin.
        $navnode = navigation_node::create(
            text: get_string('tabofflinequizcorrect', 'offlinequiz'),
            action: new moodle_url(
                '/mod/offlinequiz/report.php',
                ['q' => $offlinequiz->id, 'mode' => 'correct']
            ),
            key: $this->get_navigation_key()
        );

        $parentnode = $navigation->get('mod_offlinequiz_results');
        $parentnode->add_node($navnode);
        return $navigation;
    }
    /**
     * get title of report
     * @return string
     */
    public function get_report_title(): string {
        return get_string('correct', 'offlinequiz');
    }
    /**
     * get the navigation key for this report
     * @return string
     */
    public function get_navigation_key(): string {
        return 'tabofflinequizcorrect';
    }
}
