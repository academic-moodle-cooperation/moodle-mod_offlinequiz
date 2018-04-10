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
 * The results overview report for offlinequizzes
 *
 * @package mod
 * @subpackage offlinequiz
 * @author Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright 2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since Moodle 2.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 */
require_once($CFG->libdir . '/tablelib.php');
require_once('results_table.php');
require_once($CFG->libdir . '/gradelib.php');

class offlinequiz_overview_report extends offlinequiz_default_report {

    /**
     * (non-PHPdoc)
     *
     * @see offlinequiz_default_report::display()
     */
    public function display($offlinequiz, $cm, $course) {
        global $CFG, $OUTPUT, $SESSION, $DB;

        // Define some strings.
        $strtimeformat = get_string('strftimedatetime');
        $letterstr = ' ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        offlinequiz_load_useridentification();
        $offlinequizconfig = get_config('offlinequiz');

        // Deal with actions.
        $action = optional_param('action', '', PARAM_ACTION);

        $context = context_module::instance($cm->id);
        $systemcontext = context_system::instance();

        // Set table options.
        $noresults = optional_param('noresults', 0, PARAM_INT);
        $pagesize = optional_param('pagesize', 10, PARAM_INT);
        $groupid = optional_param('group', 0, PARAM_INT);

        // Only print headers if not asked to download data or delete data.
        if ((!$download = optional_param('download', null, PARAM_TEXT)) && !$action == 'delete') {
            $this->print_header_and_tabs($cm, $course, $offlinequiz, 'overview');
            echo $OUTPUT->box_start('linkbox');
            echo $OUTPUT->heading(format_string($offlinequiz->name));
            echo $OUTPUT->heading(get_string('results', 'offlinequiz'));

            require_once($CFG->libdir . '/grouplib.php');
            $groupselecturl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php',
                    array('id' => $cm->id,
                            'mode' => 'overview',
                            'noresults' => $noresults,
                            'pagesize' => $pagesize,
                    		'group' => $groupid
                    ));

            echo groups_print_activity_menu($cm, $groupselecturl, true);
            echo $OUTPUT->box_end();
            echo '<br/>';
        }

        if ($pagesize < 1) {
            $pagesize = 10;
        }

        $answerletters = 'abcdefghijklmnopqrstuvwxyz';

        if ($action == 'delete' && confirm_sesskey()) {

            $selectedresultids = array();
            $params = (array) data_submitted();

            foreach ($params as $key => $value) {
                if (preg_match('!^s([0-9]+)$!', $key, $matches)) {
                    $selectedresultids[] = $matches[1];
                }
            }

            if ($selectedresultids) {
                foreach ($selectedresultids as $resultid) {

                    if ($resultid && $todelete = $DB->get_record('offlinequiz_results',
                            array('id' => $resultid
                            ))) {

                        offlinequiz_delete_result($resultid, $context);

                        // Log this event.
                        $params = array('objectid' => $resultid,
                            'relateduserid' => $todelete->userid,
                            'context' => context_module::instance($cm->id),
                            'other' => array('mode' => 'overview'
                            )
                        );
                        $event = \mod_offlinequiz\event\attempt_deleted::create($params);
                        $event->trigger();

                        // Change the status of all related pages with error 'resultexists' to
                        // 'suspended'.
                        $user = $DB->get_record('user',
                                array('id' => $todelete->userid
                                ));
                        $group = $DB->get_record('offlinequiz_groups',
                                array('id' => $todelete->offlinegroupid
                                ));

                        $sql = "SELECT id
                                  FROM {offlinequiz_scanned_pages}
                                 WHERE offlinequizid = :offlinequizid
                                   AND userkey = :userkey
                                   AND groupnumber = :groupnumber
                                   AND status = 'error'
                                   AND (error = 'resultexists' OR error = 'differentresultexists')";
                        $params = array('offlinequizid' => $offlinequiz->id,
                            'userkey' => $user->{$offlinequizconfig->ID_field},
                            'groupnumber' => $group->number
                        );
                        $otherpages = $DB->get_records_sql($sql, $params);
                        foreach ($otherpages as $page) {
                            $DB->set_field('offlinequiz_scanned_pages', 'status', 'suspended',
                                    array('id' => $page->id
                                    ));
                            $DB->set_field('offlinequiz_scanned_pages', 'error', '',
                                    array('id' => $page->id
                                    ));
                        }
                    }
                }
                offlinequiz_grade_item_update($offlinequiz, 'reset');
                offlinequiz_update_grades($offlinequiz);
                redirect(
                        new moodle_url('/mod/offlinequiz/report.php',
                                array('mode' => 'overview', 'id' => $cm->id,
                                    'noresults' => $noresults, 'group' => $groupid,
                                    'pagesize' => $pagesize
                                )));
            }
        }

        // Now check if asked download of data.
        if ($download) {
            $filename = clean_filename(
                    "$course->shortname " . format_string($offlinequiz->name, true));
            $sort = '';
        }

        // Fetch the group data.
        $groups = $DB->get_records('offlinequiz_groups',
                array('offlinequizid' => $offlinequiz->id
                ), 'number', '*', 0, $offlinequiz->numgroups);

        // Define table columns.
        $tablecolumns = array('checkbox', 'picture', 'fullname', $offlinequizconfig->ID_field,
            'timestart', 'offlinegroupid', 'sumgrades'
        );
        $tableheaders = array(
            '<input type="checkbox" class="select-all-checkbox"/>', '',
            get_string('fullname'), get_string($offlinequizconfig->ID_field),
            get_string('importedon', 'offlinequiz'), get_string('group'),
            get_string('grade', 'offlinequiz')
        );

        $checked = array();
        // Get participants list.
        $withparticipants = false;
        if ($lists = $DB->get_records('offlinequiz_p_lists',
                array('offlinequizid' => $offlinequiz->id
                ))) {
            $withparticipants = true;
            $tablecolumns[] = 'checked';
            $tableheaders[] = get_string('present', 'offlinequiz');
            foreach ($lists as $list) {
                $participants = $DB->get_records('offlinequiz_participants',
                        array('listid' => $list->id
                        ));
                foreach ($participants as $participant) {
                    $checked[$participant->userid] = $participant->checked;
                }
            }
        }

        // Set up the table in any case, even if we are downloading a file.
        $params = array('offlinequiz' => $offlinequiz, 'noresults' => $noresults, 'pagesize' => $pagesize, 'group' => $groupid
        );
        $table = new offlinequiz_results_table('mod-offlinequiz-report-overview-report', $params);

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $baseurl = new moodle_url($CFG->wwwroot . '/mod/offlinequiz/report.php',
                array('mode' => 'overview', 'id' => $cm->id, 'noresults' => $noresults, 'group' => $groupid,
                    'pagesize' => $pagesize
                ));
        $table->define_baseurl($baseurl);

        $table->sortable(true);
        $table->no_sorting('checkbox');

        if ($withparticipants) {
            $table->no_sorting('checked');
        }

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class($offlinequizconfig->ID_field, 'userkey');
        $table->column_class('timestart', 'timestart');
        $table->column_class('offlinegroupid', 'offlinegroupid');
        $table->column_class('sumgrades', 'sumgrades');

        $table->set_attribute('cellpadding', '2');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'generaltable generalbox');

        // Start working -- this is necessary as soon as the niceties are over.
        $table->setup();

        if ($download == 'ODS') {
            require_once("$CFG->libdir/odslib.class.php");

            $filename .= ".ods";
            // Creating a workbook.
            $workbook = new MoodleODSWorkbook("-");
            // Sending HTTP headers.
            $workbook->send($filename);
            // Creating the first worksheet.
            $sheettitle = get_string('reportoverview', 'offlinequiz');
            $myxls = $workbook->add_worksheet($sheettitle);
            // Format types.
            $format = $workbook->add_format();
            $format->set_bold(0);
            $formatbc = $workbook->add_format();
            $formatbc->set_bold(1);
            $formatbc->set_align('center');
            $formatb = $workbook->add_format();
            $formatb->set_bold(1);
            $formaty = $workbook->add_format();
            $formaty->set_bg_color('yellow');
            $formatc = $workbook->add_format();
            $formatc->set_align('center');
            $formatr = $workbook->add_format();
            $formatr->set_bold(1);
            $formatr->set_color('red');
            $formatr->set_align('center');
            $formatg = $workbook->add_format();
            $formatg->set_bold(1);
            $formatg->set_color('green');
            $formatg->set_align('center');

            // Here starts workshhet headers.
            $headers = array(get_string($offlinequizconfig->ID_field), get_string('firstname'),
                get_string('lastname'), get_string('importedon', 'offlinequiz'),
            	get_string('group'), get_string('grade', 'offlinequiz'), get_string('letter', 'offlinequiz')
            );
            if (!empty($withparticipants)) {
                $headers[] = get_string('present', 'offlinequiz');
            }
            $colnum = 0;
            foreach ($headers as $item) {
                $myxls->write(0, $colnum, $item, $formatbc);
                $colnum++;
            }
            $rownum = 1;
        } else if ($download == 'Excel') {
            require_once("$CFG->libdir/excellib.class.php");

            $filename .= ".xls";
            // Creating a workbook.
            $workbook = new MoodleExcelWorkbook("-");
            // Sending HTTP headers.
            $workbook->send($filename);
            // Creating the first worksheet.
            $sheettitle = get_string('results', 'offlinequiz');
            $myxls = $workbook->add_worksheet($sheettitle);
            // Format types.
            $format = $workbook->add_format();
            $format->set_bold(0);
            $formatbc = $workbook->add_format();
            $formatbc->set_bold(1);
            $formatbc->set_align('center');
            $formatb = $workbook->add_format();
            $formatb->set_bold(1);
            $formaty = $workbook->add_format();
            $formaty->set_bg_color('yellow');
            $formatc = $workbook->add_format();
            $formatc->set_align('center');
            $formatr = $workbook->add_format();
            $formatr->set_bold(1);
            $formatr->set_color('red');
            $formatr->set_align('center');
            $formatg = $workbook->add_format();
            $formatg->set_bold(1);
            $formatg->set_color('green');
            $formatg->set_align('center');

            // Here starts worksheet headers.
            $headers = array(get_string($offlinequizconfig->ID_field), get_string('firstname'),
                get_string('lastname'), get_string('importedon', 'offlinequiz'),
            	get_string('group'), get_string('grade', 'offlinequiz'), get_string('letter', 'offlinequiz')
            );
            if (!empty($withparticipants)) {
                $headers[] = get_string('present', 'offlinequiz');
            }
            $colnum = 0;
            foreach ($headers as $item) {
                $myxls->write(0, $colnum, $item, $formatbc);
                $colnum++;
            }
            $rownum = 1;
        } else if ($download == 'CSV') {
            $filename .= ".csv";
            header("Content-Encoding: UTF-8");
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Expires: 0");
            header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
            header("Pragma: public");
            echo "\xEF\xBB\xBF"; // UTF-8 BOM.

            $headers = get_string($offlinequizconfig->ID_field) . ", " . get_string('fullname') .
                     ", " . get_string('importedon', 'offlinequiz') . ", " . get_string('group') .
                     ", " . get_string('grade', 'offlinequiz') . ", " . get_string('letter', 'offlinequiz');
            if (!empty($withparticipants)) {
                $headers .= ", " . get_string('present', 'offlinequiz');
            }
            echo $headers . " \n";
        } else if ($download == 'CSVplus1' || $download == 'CSVpluspoints') {
            $filename .= ".csv";
            header("Content-Encoding: UTF-8");
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header("Expires: 0");
            header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
            header("Pragma: public");
            echo "\xEF\xBB\xBF"; // UTF-8 BOM.

            // Print the table headers.
            echo get_string('firstname') . ',' . get_string('lastname') . ',' .
                     get_string($offlinequizconfig->ID_field) . ',' . get_string('group');
            $maxquestions = offlinequiz_get_maxquestions($offlinequiz, $groups);
            for ($i = 0; $i < $maxquestions; $i++) {
                echo ', ' . get_string('question') . ' ' . ($i + 1);
            }
            echo "\n";

            // Print the correct answer bit-strings.
            foreach ($groups as $group) {
                if ($group->templateusageid) {
                    $quba = question_engine::load_questions_usage_by_activity(
                            $group->templateusageid);
                    $slots = $quba->get_slots();
                    echo ', ,' . get_string('correct', 'offlinequiz');
                    echo ',' . $group->number;
                    foreach ($slots as $slot) {
                        $slotquestion = $quba->get_question($slot);
                        $qtype = $slotquestion->get_type_name();
                        if ($qtype == 'multichoice' || $qtype == 'multichoiceset') {
                            $attempt = $quba->get_question_attempt($slot);
                            $order = $slotquestion->get_order($attempt); // Order of the answers.
                            $tempstr = ",";
                            $letters = array();
                            $counter = 0;
                            foreach ($order as $key => $answerid) {
                                $fraction = $DB->get_field('question_answers', 'fraction',
                                        array('id' => $answerid
                                        ));
                                if ($fraction > 0) {
                                    $letters[] = $answerletters[$counter];
                                }
                                $counter++;
                            }
                            if (empty($letters)) {
                                $tempstr .= '99';
                            } else {
                                $tempstr .= implode('/', $letters);
                            }
                            echo $tempstr;
                        }
                    }
                    echo "\n";
                }
            }
        }

        $coursecontext = context_course::instance($course->id);

        $contextids = $coursecontext->get_parent_context_ids(true);

        // Construct the SQL
        // First get roleids for students from leagcy.
        if (!$roles = get_roles_with_capability('mod/offlinequiz:attempt', CAP_ALLOW,
                $systemcontext)) {
            error("No roles with capability 'moodle/offlinequiz:attempt' defined in system context");
        }
        $roleids = array();
        foreach ($roles as $role) {
            $roleids[] = $role->id;
        }

        $rolelist = implode(',', $roleids);

        $select = "SELECT " . $DB->sql_concat('u.id', "'#'", "COALESCE(qa.usageid, 0)") . " AS uniqueid,
        qa.id AS resultid, u.id, qa.usageid, qa.offlinegroupid, qa.status,
        u.id AS userid, u.firstname, u.lastname,
        u.alternatename, u.middlename, u.firstnamephonetic, u.lastnamephonetic,
        u.picture, u." . $offlinequizconfig->ID_field . ",
        qa.sumgrades, qa.timefinish, qa.timestart, qa.timefinish - qa.timestart AS duration ";

        $result = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        list($contexttest, $cparams) = $result;
        list($roletest, $rparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'role');

        $from = " FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
             LEFT JOIN {offlinequiz_results} qa ON u.id = qa.userid AND qa.offlinequizid = :offlinequizid
             ";

        $where = " WHERE ra.contextid $contexttest AND ra.roleid $roletest ";

        $params = array('offlinequizid' => $offlinequiz->id);
        $params = array_merge($params, $cparams, $rparams);

        if (empty($noresults)) {
            $where = $where . " AND qa.userid IS NOT NULL
                                AND qa.status = 'complete' "; // Show ONLY students with results.
        } else if ($noresults == 1) {
            // The value noresults = 1 means only no results, so make the left join ask for only
            // records
            // where the right is null (no results).
            $where .= ' AND qa.userid IS NULL '; // Show ONLY students without results.
        } else if ($noresults == 3) {
            // We want all results, also the partial ones.
            $from = "FROM {user} u
                      JOIN {offlinequiz_results} qa ON u.id = qa.userid ";
            $where = " WHERE qa.offlinequizid = :offlinequizid ";
        } // The value noresults = 2 means we want all students, with or without results.

        if ($groupid) {
            $from .= " JOIN {groups_members} gm ON gm.userid = u.id ";
            $where .= " AND gm.groupid = :groupid ";
            $params['groupid'] = $groupid;
        }

        $countsql = 'SELECT COUNT(DISTINCT(u.id)) ' . $from . $where;

        // Count the records NOW, before funky question grade sorting messes up $from.
        $totalinitials = $DB->count_records_sql($countsql, $params);

        // Add extra limits due to initials bar.
        list($ttest, $tparams) = $table->get_sql_where();

        if (!empty($ttest)) {
            $where .= ' AND ' . $ttest;
            $countsql .= ' AND ' . $ttest;
            $params = array_merge($params, $tparams);
        }

        $total = $DB->count_records_sql($countsql, $params);

        // Add extra limits due to sorting by question grade.
        $tablesort = $table->get_sql_sort();

        $table->pagesize($pagesize, $total);


        // Fix some wired sorting.
        if (empty($tablesort)) {
            $sort = ' ORDER BY u.lastname, u.id ';
        } else {
            $sort = ' ORDER BY ' . $tablesort . ', u.id';
        }

       error_log($select . $from . $where . $sort);
        // Fetch the results.
        if (!$download) {
            $results = $DB->get_records_sql($select . $from . $where . $sort, $params,
                    $table->get_page_start(), $table->get_page_size());
        } else {
            $results = $DB->get_records_sql($select . $from . $where . $sort, $params);
        }

        // Build table rows.
        if (!$download) {
            $table->initialbars(true);
        }
        if (!empty($results) || !empty($noresults)) {
            foreach ($results as $result) {
                $user = $DB->get_record('user',
                        array('id' => $result->userid
                        ));
                $picture = $OUTPUT->user_picture($user,
                        array('courseid' => $course->id
                        ));

                if (!empty($result->resultid)) {
                    $checkbox = '<input type="checkbox" name="s' . $result->resultid . '" value="' .
                             $result->resultid . '"  class="select-multiple-checkbox" />';
                } else {
                    $checkbox = '';
                }

                if (!empty($result) && (empty($result->resultid) || $result->timefinish == 0)) {
                    $resultdate = '-';
                } else {
                    $resultdate = userdate($result->timefinish, $strtimeformat);
                }

                if (!empty($result) && $result->offlinegroupid) {
                    $groupletter = $letterstr[$groups[$result->offlinegroupid]->number];
                } else {
                    $groupletter = '-';
                }

                $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $result->userid .
                         '&amp;course=' . $course->id . '">' . fullname($result) . '</a>';

                if (!$download) {
                    $row = array($checkbox, $picture, $userlink,
                        $result->{$offlinequizconfig->ID_field}, $resultdate, $groupletter
                    );
                } else {
                    $row = array($result->{$offlinequizconfig->ID_field}, $result->firstname,
                        $result->lastname, $resultdate, $groupletter
                    );
                }

                if (!empty($result) && $result->offlinegroupid) {
                    $outputgrade = format_float(
                            $result->sumgrades / $groups[$result->offlinegroupid]->sumgrades *
                                     $offlinequiz->grade, $offlinequiz->decimalpoints);
                } else {
                    $outputgrade = '-';
                }

                if (!$download) {
                    if ($result->status == 'partial') {
                        $row[] = get_string('partial', 'offlinequiz');
                    } else if ($result->sumgrades === null) {
                        $row[] = '-';
                    } else {
                        $row[] = '<a href="review.php?q=' . $offlinequiz->id . '&amp;resultid=' .
                                 $result->resultid . '">' . $outputgrade . '</a>';
                    }
                    if ($withparticipants) {
                        $row[] = !empty($checked[$result->userid]) ?
                                "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/tick.gif\" alt=\"" .
                                 get_string('ischecked', 'offlinequiz') . "\">" :
                                  "<img src=\"$CFG->wwwroot/mod/offlinequiz/pix/cross.gif\" alt=\"" .
                                 get_string('isnotchecked', 'offlinequiz') . "\">";
                    }
                } else if ($download != 'CSVplus1') {
                    $row[] = $result->sumgrades === null ? '-' : $outputgrade;
                    $row[] = $this->get_grade($context, $course->id, $offlinequiz->id, $result->userid);
                    if ($withparticipants) {
                        if (array_key_exists($result->userid, $checked)) {
                            $row[] = $checked[$result->userid] ? get_string('ok') : '-';
                        } else {
                            $row[] = '-';
                        }
                    }
                	

                }

                if (!$download) {
                    $table->add_data($row);
                } else if ($download == 'Excel' or $download == 'ODS') {
                    $colnum = 0;
                    foreach ($row as $item) {
                        $myxls->write($rownum, $colnum, $item, $format);
                        $colnum++;
                    }
                    $rownum++;
                } else if ($download == 'CSV') {
                    $text = implode(',', $row);
                    echo $text . "\n";
                } else if ($download == 'CSVplus1' || $download == 'CSVpluspoints') {
                    $text = $row[1] . ',' . $row[2] . ',' . $row[0] . ',' .
                             $groups[$result->offlinegroupid]->number;
                    if ($pages = $DB->get_records('offlinequiz_scanned_pages',
                            array('resultid' => $result->resultid
                            ), 'pagenumber ASC')) {
                        foreach ($pages as $page) {
                            if ($page->status == 'ok' || $page->status == 'submitted') {
                                $choices = $DB->get_records('offlinequiz_choices',
                                        array('scannedpageid' => $page->id
                                        ), 'slotnumber, choicenumber');
                                $counter = 0;
                                $oldslot = -1;
                                $letters = array();
                                foreach ($choices as $choice) {
                                    if ($oldslot == -1) {
                                        $oldslot = $choice->slotnumber;
                                    } else if ($oldslot != $choice->slotnumber) {
                                        if (empty($letters)) {
                                            $text .= ',99';
                                        } else {
                                            $text .= ',' . implode('/', $letters);
                                        }
                                        $counter = 0;
                                        $oldslot = $choice->slotnumber;
                                        $letters = array();
                                    }
                                    if ($choice->value == 1) {
                                        $letters[] = $answerletters[$counter];
                                    }
                                    $counter++;
                                }
                                if (empty($letters)) {
                                    $text .= ',99';
                                } else {
                                    $text .= ',' . implode('/', $letters);
                                }
                            }
                        }
                    }
                    echo $text . "\n";

                    if ($download == 'CSVpluspoints') {
                        $text = $row[1] . ',' . $row[2] . ',' . $row[0] . ',' .
                                 $groups[$result->offlinegroupid]->number;
                        $quba = question_engine::load_questions_usage_by_activity($result->usageid);
                        $slots = $quba->get_slots();
                        foreach ($slots as $slot) {
                            $slotquestion = $quba->get_question($slot);
                            $attempt = $quba->get_question_attempt($slot);
                            $text .= ',' .
                                     format_float($attempt->get_mark(), $offlinequiz->decimalpoints,
                                            false);
                        }
                        echo $text . "\n";
                    }
                }
            } // End foreach ($results...
        } else if (!$download) {
            $table->print_initials_bar();
        }

        if (!$download) {
            // Print table.
            $table->finish_html();

            if (!empty($results)) {
            	echo '<div>';
                echo '<form id="downloadoptions" action="report.php" method="get">';
                echo ' <input type="hidden" name="id" value="' . $cm->id . '" />';
                echo ' <input type="hidden" name="q" value="' . $offlinequiz->id . '" />';
                echo ' <input type="hidden" name="mode" value="overview" />';
                echo ' <input type="hidden" name="group" value="' . $groupid . '" />';
                echo ' <input type="hidden" name="noresults" value="' . $noresults . '" />';
                echo ' <input type="hidden" name="noheader" value="yes" />';
                echo ' <table class="boxaligncenter"><tr><td>';
                $options = array('Excel' => get_string('excelformat', 'offlinequiz'),
                    'ODS' => get_string('odsformat', 'offlinequiz'),
                    'CSV' => get_string('csvformat', 'offlinequiz'),
                    'CSVplus1' => get_string('csvplus1format', 'offlinequiz'),
                    'CSVpluspoints' => get_string('csvpluspointsformat', 'offlinequiz')
                );
                print_string('downloadresultsas', 'offlinequiz');
                echo "</td><td>";
                echo html_writer::select($options, 'download', '', false);
                echo ' <button type="submit" class="btn btn-primary" > ' . get_string('download') . '</button>';
                echo ' <script type="text/javascript">' . "\n<!--\n" .
                         'document.getElementById("noscriptmenuaction").style.display = "none";' .
                         "\n-->\n" . '</script>';
                echo " </td>\n";
                echo "<td>";
                echo "</td>\n";
                echo '</tr></table></form></div>';
            }
        } else if ($download == 'Excel' || $download == 'ODS') {
            $workbook->close();
            exit();
        } else if ($download == 'CSV' || $download == 'CSVplus1' || $download == 'CSVpluspoints') {
            exit();
        }

        // Print display options.
        echo '<div class="display-options">';
        echo '<form id="options" action="report.php" method="get">';
        echo ' <div>';
        echo '   <p>' . get_string('displayoptions', 'offlinequiz') . ': </p>';
        echo '   <input type="hidden" name="id" value="' . $cm->id . '" />';
        echo '   <input type="hidden" name="q" value="' . $offlinequiz->id . '" />';
        echo '   <input type="hidden" name="mode" value="overview" />';
        echo '   <input type="hidden" name="group" value="' . $groupid . '" />';
        echo '   <input type="hidden" name="noresults" value="' . $noresults . '" />';
        echo '   <input type="hidden" name="detailedmarks" value="0" />';
        echo '   <table id="overview-options" class="boxaligncenter">';
        echo '     <tr align="left">';
        echo '     <td><label for="pagesize">' . get_string('pagesizeparts', 'offlinequiz') .
                  '</label></td>';
        echo '     <td><input type="text" id="pagesize" name="pagesize" size="3" value="' . $pagesize .
                 '" /></td>';
        echo '</tr>';
        echo '<tr align="left">';
        echo '<td colspan="2">';

        $options = array();
        $options[] = get_string('attemptsonly', 'offlinequiz');
        $options[] = get_string('noattemptsonly', 'offlinequiz');
        $options[] = get_string('allstudents', 'offlinequiz');
        $options[] = get_string('allresults', 'offlinequiz');

        echo html_writer::select($options, 'noresults', $noresults, '');
        echo '</td></tr>';
        echo '<tr><td colspan="2" align="center">';
        echo '<button type="submit" class="btn btn-secondary"> ' . get_string('go') . '</button>';
        echo '</td></tr></table>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo "\n";

        return true;
    }
    
    private function get_grade($context,$courseid,$offlinequizid,$userid) {
    	$grading_info = grade_get_grades($courseid, 'mod', 'offlinequiz', $offlinequizid, $userid);
    	$gradeitem = $grading_info->items[0];
    	if($gradeitem != null) {
    		$letters = grade_get_letters($context);
    		return $this->get_gradeletter($letters, $gradeitem, $userid);
    	} else {
    		return '-';
    	}
    }
    
    private function get_gradeletter($letters, $gradeitem,$userid) {
    	if (!$gradeitem) {
    		return '-';
    	}
    	$grade = $gradeitem->grades[$userid];
    	// Map to range.
    	$gradeint = $gradeitem->grademax - $gradeitem->grademin;
    	$value = ($gradeint != 100 || $gradeitem->grademin != 0) ? ($grade->grade - $gradeitem->grademin
    			) * 100 / $gradeint : $grade->grade;
    			
    	// Calculate gradeletter.
    	$value = bounded_number(0, $value, 100); // Just in case.
    	foreach ($letters as $boundary => $letter) {
    		$numboundary = str_replace(',', '.', $boundary);
    		if ($value >= $numboundary) {
    			return format_string($letter);
    		}
    	}
    }
}
