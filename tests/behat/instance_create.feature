@mod @mod_offlinequiz @adding @amc
Feature: In a course, a teacher should be able to add a new offlinequiz
    In order to add a new offlinequiz
    As a teacher
    I need to be able to add a new offlinequiz and save it correctly.

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

  @javascript
  Scenario: Add an offlinequiz instance
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a offlinequiz activity to course "Course 1" section "2" and I fill the form with:
      | Offline quiz name | Add an offlinequiz to the current course |
      | Description | Add an offlinequiz to the current course (Description) |
    And I am on the "Add an offlinequiz to the current course" "offlinequiz activity" page logged in as teacher1
    Then I should see "Add an offlinequiz to the current course (Description)"
