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
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.8+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_offlinequiz\question\bank;

use core\output\datafilter;
use core_question\local\bank\column_base;
use core_question\local\bank\condition;
use core_question\local\bank\question_version_status;
use mod_offlinequiz\question\bank\filter\custom_category_condition;
use qbank_managecategories\category_condition;

use qbank_deletequestion\hidden_condition;
use core_question\local\bank\filter_condition_manager;

defined('MOODLE_INTERNAL') || die();

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
    /*public function __construct($contexts, $pageurl, $course, $cm, $params, $extraparams) {
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
        /*$this->set_quiz_has_attempts(quiz_has_attempts($this->quiz->id));
        $this->pagesize = self::DEFAULT_PAGE_SIZE;

        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->offlinequiz = $offlinequiz;
    }*/
    public function __construct($contexts, $pageurl, $course, $cm, $params, $extraparams, $offlinequiz = null) {
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
        if (is_null($offlinequiz)) {
            parent::__construct($contexts, $pageurl, $course, $cm, $params, $extraparams);
            [$this->offlinequiz, ] = get_module_from_cmid($cm->id);
        } else {
            parent::__construct($contexts, $pageurl, $course, $cm);
            $this->offlinequiz = $offlinequiz;
        }
    }

    public function get_offlinequiz() {
        return $this->offlinequiz;
    }

    /*protected function wanted_columns(): array {
        global $CFG;

        if (empty($CFG->offlinequizquestionbankcolumns)) {
            $offlinequizquestionbankcolumns = array(
                'add_action_column',
                //'checkbox_column',
                'question_type_column',
                'question_name_text_column',
                'mod_offlinequiz\question\bank\preview_action_column',
            );
        } else {
            $offlinequizquestionbankcolumns = explode(',', $CFG->offlinequizquestionbankcolumns);
        }

        foreach ($offlinequizquestionbankcolumns as $fullname) {
            if (!class_exists($fullname)) {
                if (class_exists('mod_offlinequiz\\question\\bank\\' . $fullname)) {
                    $fullname = 'mod_offlinequiz\\question\\bank\\' . $fullname;
                } else if (class_exists('qbank_previewquestion\\' . $fullname)) {
                    $fullname = 'qbank_previewquestion\\' . $fullname;
                } else if (class_exists('question_bank_' . $fullname)) {
                    debugging('Legacy question bank column class question_bank_' .
                            $fullname . ' should be renamed to mod_offlinequiz\\question\\bank\\' .
                            $fullname, DEBUG_DEVELOPER);
                    $fullname = 'question_bank_' . $fullname;
                } else {
                    throw new \coding_exception("No such class exists: $fullname");
                }
            }
            $this->requiredcolumns[$fullname] = new $fullname($this);
        }
        return $this->requiredcolumns;
    }*/

    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $customviewcolumns = [
            'mod_offlinequiz\question\bank\add_action_column' . column_base::ID_SEPARATOR  . 'add_action_column',
            'core_question\local\bank\checkbox_column' . column_base::ID_SEPARATOR . 'checkbox_column',
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
        return array(
            'qbank_viewquestiontype\\question_type_column' => 1,
            'mod_offlinequiz\\question\\bank\\question_name_text_column' => 1,
        );
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

    public function preview_question_url($question) {
        return offlinequiz_question_preview_url($this->offlinequiz, $question);
    }

    public function add_to_offlinequiz_url($questionid) {
        global $CFG;
        $params = $this->baseurl->params();
        $params['addquestion'] = $questionid;
        $params['sesskey'] = sesskey();
        $params['cmid'] = $this->cm->id;
        return new \moodle_url('/mod/offlinequiz/edit.php', $params);
        /*$addurl = new \moodle_url($this->baseurl, $params);
        return $addurl;*/
    }

    public function offlinequiz_contains($questionid) {
        global $CFG, $DB;

        /*if (in_array($questionid, $this->offlinequiz->questions)) {
            return true;
        }*/
        return false;
    }

    /**
     * Renders the html question bank (same as display, but returns the result).
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @return string HTML code for the form
     */
    /*public function render($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext, $tagids) {
        ob_start();
        $pagevars = [];
        $pagevars['qpage'] = $page;
        $pagevars['qperpage'] = $perpage;
        $pagevars['cat'] = $cat;
        $pagevars['recurse'] = $recurse;
        $pagevars['showhidden'] = $showhidden;
        $pagevars['qbshowtext'] = $showquestiontext;
        $pagevars['qtagids'] = $tagids;
        $pagevars['tabname'] = 'questions';
        $pagevars['qperpage'] = DEFAULT_QUESTIONS_PER_PAGE;
        $pagevars['filter']  = [];
        [$categoryid, $contextid] = category_condition::validate_category_param($pagevars['cat']);
        if (!is_null($categoryid)) {
            $category = category_condition::get_category_record($categoryid, $contextid);
            $pagevars['filter']['category'] = [
                'jointype' => category_condition::JOINTYPE_DEFAULT,
                'values' => [$category->id],
                'filteroptions' => ['includesubcategories' => false],
            ];
        }
        $pagevars['filter']['hidden'] = [
            'jointype' => hidden_condition::JOINTYPE_DEFAULT,
            'values' => [0],
        ];
        $pagevars['jointype'] = datafilter::JOINTYPE_ALL;
        if (!empty($pagevars['filter'])) {
            $pagevars['filter'] = filter_condition_manager::unpack_filteroptions_param($pagevars['filter']);
        }
        if (isset($pagevars['filter']['jointype'])) {
            $pagevars['jointype'] = $pagevars['filter']['jointype'];
            unset($pagevars['filter']['jointype']);
        }

        $this->set_pagevars($pagevars);
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }*/

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


        /*$pagevars = [];
        $pagevars['qpage'] = $page;
        $pagevars['qperpage'] = $perpage;
        $pagevars['cat'] = $cat;
        $pagevars['recurse'] = $recurse;
        $pagevars['showhidden'] = $showhidden;
        $pagevars['qbshowtext'] = $showquestiontext;
        $pagevars['qtagids'] = $tagids;
        $pagevars['tabname'] = 'questions';
        $pagevars['qperpage'] = DEFAULT_QUESTIONS_PER_PAGE;
        $pagevars['filter']  = [];*/
        [$categoryid, $contextid] = category_condition::validate_category_param($pagevars['cat']);
        if (!is_null($categoryid)) {
            $category = category_condition::get_category_record($categoryid, $contextid);
            $pagevars['filter']['category'] = [
                'jointype' => category_condition::JOINTYPE_DEFAULT,
                'values' => [$category->id],
                'filteroptions' => ['includesubcategories' => false],
            ];
        }
        $pagevars['filter']['hidden'] = [
            'jointype' => hidden_condition::JOINTYPE_DEFAULT,
            'values' => [0],
        ];
        $pagevars['jointype'] = datafilter::JOINTYPE_ALL;
        if (!empty($pagevars['filter'])) {
            $pagevars['filter'] = filter_condition_manager::unpack_filteroptions_param($pagevars['filter']);
        }
        if (isset($pagevars['filter']['jointype'])) {
            $pagevars['jointype'] = $pagevars['filter']['jointype'];
            unset($pagevars['filter']['jointype']);
        }

        $this->set_pagevars($pagevars);


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

        echo '<div class="modulespecificbuttonscontainer">';
        if ($canuseall) {

            // Add selected questions to the offlinequiz.
            $params = array(
                    'type' => 'submit',
                    'name' => 'add',
                    'value' => get_string('addtoofflinequiz', 'offlinequiz'),
                    'class' => 'btn btn-primary'
            );
            if ($cmoptions->hasattempts) {
                $params['disabled'] = 'disabled';
            }
            echo \html_writer::empty_tag('input', $params);
        }
        echo "</div>\n";
    }

    protected function display_options_form($showquestiontext, $scriptpath = '/mod/offlinequiz/edit.php',
            $showtextoption = false): void {
        // Overridden just to change the default values of the arguments.
        parent::display_options_form($showquestiontext, $scriptpath, $showtextoption);
    }

    protected function print_category_info($category) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        $strcategory = get_string('category', 'offlinequiz');
        echo '<div class="categoryinfo"><div class="categorynamefieldcontainer">' .
                $strcategory;
        echo ': <span class="categorynamefield">';
        echo shorten_text(strip_tags(format_string($category->name)), 60);
        echo '</span></div><div class="categoryinfofieldcontainer">' .
                '<span class="categoryinfofield">';
        echo shorten_text(strip_tags(format_text($category->info, $category->infoformat,
                $formatoptions, $this->course->id)), 200);
        echo '</span></div></div>';
    }

    protected function display_options($recurse, $showhidden, $showquestiontext) {
        debugging('display_options() is deprecated, see display_options_form() instead.', DEBUG_DEVELOPER);
        echo '<form method="get" action="edit.php" id="displayoptions">';
        echo "<fieldset class='invisiblefieldset'>";
        echo \html_writer::input_hidden_params($this->baseurl,
                array('recurse', 'showhidden', 'qbshowtext'));
        $this->display_category_form_checkbox('recurse', $recurse,
                get_string('includesubcategories', 'question'));
        $this->display_category_form_checkbox('showhidden', $showhidden,
                get_string('showhidden', 'question'));
        echo '<noscript><div class="centerpara"><input type="submit" value="' .
                get_string('go') . '" />';
        echo '</div></noscript></fieldset></form>';
    }

    protected function create_new_question_form($category, $canadd): void {
        // Don't display this.
    }

    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
/*    protected function build_query(): void {
        // Get the required tables and fields.
        $joins = [];
        $fields = ['qv.status', 'qc.id as categoryid', 'qv.version', 'qv.id as versionid', 'qbe.id as questionbankentryid'];
        if (!empty($this->requiredcolumns)) {
            foreach ($this->requiredcolumns as $column) {
                $extrajoins = $column->get_extra_joins();
                foreach ($extrajoins as $prefix => $join) {
                    if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                        throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                    }
                    $joins[$prefix] = $join;
                }
                $fields = array_merge($fields, $column->get_required_fields());
            }
        }
        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $readyonly = "qv.status = '" . \core_question\local\bank\question_version_status::QUESTION_STATUS_READY . "' ";
        $tests = ['q.parent = 0', $latestversion, $readyonly];
        $this->sqlparams = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $sql .= '   AND q.qtype IN (\'multichoice\', \'multichoiceset\', \'description\') ';
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }*/

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
        if (!empty($conditions)) {
            $sql .= ' AND ' . $nonecondition . ' ( ';
            $sql .= implode($separator, $conditions);
            $sql .= ' ) ';
        }
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
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
}
