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
 * Defines the custom question bank view used on the Edit offlinequiz page.
 *
 * @package       mod_offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_offlinequiz\question\bank;

defined('MOODLE_INTERNAL') || die();

use core\output\datafilter;
use core_question\local\bank\column_base;
use core_question\local\bank\condition;
use core_question\local\bank\column_manager_base;
use core_question\local\bank\question_version_status;
use mod_offlinequiz\question\bank\filter\custom_category_condition;
use qbank_managecategories\category_condition;

require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
/**
 * Subclass to customise the view of the question bank for the offlinequiz editing screen.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_view extends \core_question\local\bank\view {
    /** @var int number of questions per page to show in the add from question bank modal. */
    const DEFAULT_PAGE_SIZE = 20;
    /** @var bool whether the offlinequiz this is used by has been attemptd. */
    protected $offlinequizhasattempts = false;
    /** @var \stdClass the offlinequiz settings. */
    protected $offlinequiz = false;
    /** @var int groupnumber*/
    protected $groupnumber;

    /**
     * @var string $component the component the api is used from.
     */
    public $component = 'mod_offlinequiz';

    /** @var int The maximum displayed length of the category info. */
    const MAX_TEXT_LENGTH = 200;

    /**
     * Constructor
     * @param \question_edit_contexts $contexts
     * @param \moodle_url $pageurl
     * @param \stdClass $course course settings
     * @param \stdClass $cm activity settings.
     * @param \stdClass $offlinequiz offlinequiz settings.
     */
    public function __construct($contexts, $pageurl, $course, $cm, $params, $extraparams) {
        global $DB;
        // Default filter condition.
        if (!isset($params['filter'])) {
            $params['filter']  = [];
            [$categoryid, $contextid] = custom_category_condition::validate_category_param($params['cat']);
            if (!is_null($categoryid)) {
                $category = custom_category_condition::get_category_record($categoryid, $contextid);
                $params['filter']['category'] = [
                    'jointype' => custom_category_condition::JOINTYPE_DEFAULT,
                    'values' => [$category->id],
                    'filteroptions' => ['includesubcategories' => false],
                ];
            }
        }
        $this->init_columns($this->wanted_columns(), $this->heading_column());
        parent::__construct($contexts, $pageurl, $course, $cm, $params, $extraparams);
        [$this->offlinequiz, ] = get_module_from_cmid($cm->id);
        $this->offlinequiz->questions = offlinequiz_get_group_question_ids($this->offlinequiz, $extraparams['groupid']);
        $this->offlinequiz->questionbankentries = offlinequiz_get_group_questionbankentry_ids($this->offlinequiz, $extraparams['groupid']);
        if($extraparams['groupid']) {
            $groupnumber = $DB->get_field('offlinequiz_groups', 'groupnumber', ['id' => $extraparams['groupid']]);
            $this->groupnumber = $groupnumber;
        }
        $this->pagesize = self::DEFAULT_PAGE_SIZE;
    }

    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $customviewcolumns = [
            'mod_offlinequiz\question\bank\add_action_column' . column_base::ID_SEPARATOR  . 'add_action_column',
            'mod_offlinequiz\question\bank\checkbox_column' . column_base::ID_SEPARATOR . 'checkbox_column',
            'qbank_viewquestiontype\question_type_column' . column_base::ID_SEPARATOR . 'question_type_column',
            'mod_offlinequiz\question\bank\question_name_text_column' . column_base::ID_SEPARATOR . 'question_name_text_column',
            'mod_offlinequiz\question\bank\preview_action_column'  . column_base::ID_SEPARATOR  . 'preview_action_column',
        ];

        foreach ($customviewcolumns as $columnid) {
            [$columnclass, $columnname] = explode(column_base::ID_SEPARATOR, $columnid, 2);
            if (class_exists($columnclass)) {
                $questionbankclasscolumns[$columnid] = $columnclass::from_column_name($this, $columnname);
            }
        }

        return $questionbankclasscolumns;
    }

    /**
     * Specify the column heading
     *
     * @return string Column name for the heading
     */
    protected function heading_column(): string {
        return 'mod_offlinequiz\\question\\bank\\question_name_text_column';
    }

    protected function default_sort(): array {
        // Using the extended class for quiz specific sort.
        return [
            'qbank_viewquestiontype__question_type_column' => SORT_ASC,
            'mod_offlinequiz__question__bank__question_name_text_column' => SORT_ASC,
        ];
    }

    /**
     * Let the question bank display know whether the offlinequiz has scanned pages,
     * hence whether some bits of UI, like the add this question to the offlinequiz icon,
     * should be displayed.
     * @param bool $offlinequizhasattempts whether the offlinequiz has scanned pages.
     */
    public function set_offlinequiz_has_scanned_pages($offlinequizhasattempts) {
        $this->offlinequizhasattempts = $offlinequizhasattempts;
        if ($offlinequizhasattempts && isset($this->visiblecolumns['addtoofflinequizaction'])) {
            unset($this->visiblecolumns['addtoofflinequizaction']);
        }
    }

    public function add_to_offlinequiz_url($questionid) {
        global $CFG;
        $params = $this->baseurl->params();
        $params['addquestion'] = $questionid;
        $params['sesskey'] = sesskey();
        $params['cmid'] = $this->cm->id;
        $params['groupnumber'] = $this->groupnumber;
        return new \moodle_url('/mod/offlinequiz/edit.php', $params);
    }

    /**
     * Just use the base column manager in this view.
     *
     * @return void
     */
    protected function init_column_manager(): void {
        $this->columnmanager = new column_manager_base();
    }

    /**
     * Renders the html question bank (same as display, but returns the result).
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @param array $pagevars
     * @param string $tabname
     * @return string HTML code for the form
     */
    public function render($pagevars, $tabname): string {
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Display the controls at the bottom of the list of questions.
     * @param int       $totalnumber Total number of questions that might be shown (if it was not for paging).
     * @param bool      $recurse     Whether to include subcategories.
     * @param \stdClass $category    The question_category row from the database.
     * @param \context  $catcontext  The context of the category being displayed.
     * @param array     $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_bottom_controls(\context $catcontext): void {
        $cmoptions = new \stdClass();
        $cmoptions->hasattempts = !empty($this->offlinequizhasattempts);

        $canuseall = has_capability('moodle/question:useall', $catcontext);

        echo \html_writer::start_tag('div', ['class' => 'pt-2']);
        if ($canuseall) {
            echo \html_writer::empty_tag('input',
                ['name' => 'groupnumber', 'value' => $this->groupnumber,'type' => 'hidden']);
            // Add selected questions to the offlinequiz.
            $params = array(
                    'type' => 'submit',
                    'name' => 'add',
                    'value' => get_string('addtoofflinequiz', 'offlinequiz'),
                    'class' => 'btn btn-primary',
                    'data-action' => 'toggle',
                    'data-togglegroup' => 'qbank',
                    'data-toggle' => 'action',
                    'disabled' => true,
            );
            if ($cmoptions->hasattempts) {
                $params['disabled'] = 'disabled';
            }
            echo \html_writer::empty_tag('input', $params);
        }
        echo \html_writer::end_tag('div');
    }

    /**
     * Override the base implementation in \core_question\local\bank\view
     * because we don't want to print new question form in the fragment
     * for the modal.
     *
     * @param false|mixed|\stdClass $category
     * @param bool $canadd
     */
    protected function create_new_question_form($category, $canadd): void {
    }

    /**
     * Override the base implementation in \core_question\local\bank\view
     * because we don't want to print the headers in the fragment
     * for the modal.
     */
    protected function display_question_bank_header(): void {
    }

    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query(): void {
        // Get the required tables and fields.
        [$fields, $joins] = $this->get_component_requirements(array_merge($this->requiredcolumns, $this->questionactions));

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sortname => $sortorder) {
            [$colname, $subsort] = $this->parse_subsort($sortname);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($sortorder == SORT_DESC, $subsort);
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $onlyready = '((' . "qv.status = '" . question_version_status::QUESTION_STATUS_READY . "'" .'))';
        $this->sqlparams = [];
        $conditions = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $conditions[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        $majorconditions = ['q.parent = 0', $latestversion, $onlyready];
        // Get higher level filter condition.
        $jointype = isset($this->pagevars['jointype']) ? (int)$this->pagevars['jointype'] : condition::JOINTYPE_DEFAULT;
        $nonecondition = ($jointype === datafilter::JOINTYPE_NONE) ? ' NOT ' : '';
        $separator = ($jointype === datafilter::JOINTYPE_ALL) ? ' AND ' : ' OR ';
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $majorconditions);
        $sql .= '   AND q.qtype IN (\'multichoice\', \'multichoiceset\', \'description\') ';
        if (!empty($conditions)) {
            $sql .= ' AND ' . $nonecondition . ' ( ';
            $sql .= implode($separator, $conditions);
            $sql .= ' ) ';
        }
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);

        // Maybe move this part in future to js in modal_offlinequiz_question_bank
        if ($this->get_question_count() <= $this->pagesize) {
            $this->pagevars['qpage'] = 0;
        }
    }

    public function add_standard_search_conditions(): void {
        foreach ($this->plugins as $componentname => $plugin) {
            if (\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                $pluginentrypointobject = new $plugin();
                if ($componentname === 'qbank_managecategories') {
                    $pluginentrypointobject = new offlinequiz_managecategories_feature();
                }
                if ($componentname === 'qbank_viewquestiontext' || $componentname === 'qbank_deletequestion') {
                    continue;
                }
                $pluginobjects = $pluginentrypointobject->get_question_filters($this);
                foreach ($pluginobjects as $pluginobject) {
                    $this->add_searchcondition($pluginobject, $pluginobject->get_condition_key());
                }
            }
        }
    }

    /**
     * Return the offlinequiz settings for the offlinequiz this question bank is displayed in.
     *
     * @return bool|\stdClass
     */
    public function get_offlinequiz() {
        return $this->offlinequiz;
    }

    public function offlinequiz_contains($questionid) {
        global $DB;
        $questionbankentryid = $DB->get_field('question_versions','questionbankentryid', ['questionid' => $questionid]);
        return in_array($questionbankentryid, $this->offlinequiz->questionbankentries);
    }
}
