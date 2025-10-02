<?php
// This file is part of Moodle - http://moodle.org/
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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Step\When;
require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin offlinequiz_overview
 *
 * @package    offlinequiz_overview
 * @category   test
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_offlinequiz_overview extends behat_base {

    /**
     * Displays all results in the overview summary
     *
     * @When /^I "(?P<selectsname>.+)" in the offlinequiz result overview of the offlinequiz "(?P<entityname>.+)"$/
     *
     * @param string $entityname The name of the offlinequiz
     * @param string $selectname
     */
    #[When('I :selectname in the offlinequiz result overview of the offlinequiz :entityname')]
    #[\core\attribute\example('I "Show all results" in the offlinequiz result overview of the offlinequiz "testofflinequiz"')]
    public function i_in_offlinequiz_results_overview_of($selectname, $entityname): void {
        global $CFG, $DB;
        $this->execute('behat_navigation::i_am_on_page_instance', [$this->escape($entityname), 'offlinequiz activity']);
        $this->execute("behat_general::i_click_on", [
            'Evaluated:', 'link']);
        $this->execute('behat_forms::i_set_the_field_with_xpath_to', ["//*[@id='menunoresults']", $selectname]);
        $this->execute("behat_general::i_click_on", ["Go", "button"]);
    }


    /**
     * To see if a string is displayed multiple times
     * @Then /^I should see "(?P<text>.+)" (?P<count>\d+) times$/
     * @param mixed $text
     * @param mixed $count
     * @throws \Exception
     * @return void
     */
    public function i_should_see_text_times($text, $count) {
        $page = $this->getSession()->getPage();
        $elements = $page->findAll('xpath', "//*[contains(text(), '$text')]");
        if (count($elements) != (int)$count) {
            throw new Exception("Expected '$text' $count times, but found ".count($elements));
        }
    }
}
