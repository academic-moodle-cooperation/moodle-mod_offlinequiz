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
 * Step definitions for offlinequiz behat features.
 *
 * @package       mod_offlinequiz
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.9
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
use function PHPUnit\Framework\throwException;
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../question/tests/behat/behat_question_base.php');

use Behat\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Behat\Tester\Exception\PendingException;


/**
 * Steps definitions related to mod_offlinequiz.
 *
 * @copyright 2015 Juergen Zimmer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_offlinequiz extends behat_question_base {

    /**
     * Adds the specified questions to offlinequiz
     *
     *
     * @Given /^the following questions are added to the offlinequiz "(?P<entityname>.+)"$/
     *
     * @param string $entityname The name of the offlinequiz
     * @param TableNode $data
     */
    #[\core\attribute\example('The following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory  | questionname     | group | maxmark |
      | Test questions    | Multi-choice-001 | A     | 1       |')]
    public function the_following_questions_are_added_to_the_offlinequiz($entityname, $data): void {
        global $CFG;
        $groupnumbers = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6];
        require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
        global $DB;
        $offlinequiz = $DB->get_record('offlinequiz', ['name' => $entityname]);
        if (!$offlinequiz) {
            throw new PendingException('unknown offlinequiz name');
        }
        $cm = get_coursemodule_from_instance('offlinequiz', $offlinequiz->id);
        $offlinequiz->cmid = $cm->id;
        $columns = array_flip($data->getRow(0));
        $rows = $data->getRows();
        unset($rows[0]);

        foreach ($rows as $row) {
            if (array_key_exists('maxmark', $columns)) {
                $maxmark = $row[$columns['maxmark']];
            } else {
                $maxmark = 0;
            }
            $groupnummber = $groupnumbers[$row[$columns['group']]];
            if (!$groupnummber) {
                $groupnumber = 1;
            }
            $questioncategory = $DB->get_record('question_categories', ['name' => $row[$columns['questioncategory']]]);
            $addquestion = $DB->get_field('question', 'id', ['name' => $row[$columns['questionname']]]);
            $offlinequiz->groupid = $DB->get_field('offlinequiz_groups', 'id' ,
            ['offlinequizid' => $offlinequiz->id, 'groupnumber' => $groupnummber]);
            offlinequiz_add_offlinequiz_question($addquestion, $offlinequiz, 0, $maxmark);

        }

    }

    /**
     * Adds the specified questions to offlinequiz
     *
     *
     * @When /^I duplicate the following activities:$/
     *
     * @param string $entityname The name of the offlinequiz
     * @param TableNode $data
     */
    #[\core\attribute\example('I duplicate the following activities:
      | name        |
      | duplicate 1 |
      | duplicate 2 |')]
    public function duplicate_the_following_activities($data): void {
        $this->execute('behat_general::i_click_on', [get_string('bulkactions'), 'button']);
        $rows = $data->getRows();
        unset($rows[0]);

        foreach ($rows as $row) {
            $xpath = "//div[contains(@class, 'activity-grid')][.//*[@data-value='{$row[0]}']]";
            $this->execute('behat_general::i_click_on', [ $xpath, 'xpath_element']);
        }
        $this->execute('behat_general::i_click_on', [get_string('duplicate'), 'button']);

    }



}
