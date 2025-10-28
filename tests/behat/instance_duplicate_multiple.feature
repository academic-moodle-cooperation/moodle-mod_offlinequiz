@mod @mod_offlinequiz @adding @amc @duplicate
Feature: In a course, a teacher should be able to duplicate multiple offlinequizzes at once

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@teacher.com |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 0|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "mod_offlinequiz > offlinequizzes" exist:
      | name        | course |
      | duplicate 1 | C1     |
      | duplicate 2 | C1     |

  @javascript
  Scenario: If I duplicate multiple offlinequizzes there is not going to be an error
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I switch editing mode on
    And I duplicate the following activities:
      | name        |
      | duplicate 1 |
      | duplicate 2 |
    Then I should see "duplicate 2 (copy)"
