@mod @mod_offlinequiz @adding
Feature: In a course, a teacher should be able to add a new offlinequiz
    In order to add a new offlinequiz
    As a teacher
    I need to be able to add a new offlinequiz and save it correctly.

  @javascript
  Scenario: Add an offlinequiz instance
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@teacher.com |
	And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 0|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "Offline Quiz" to section "2" and I fill the form with:
      | Offline quiz name | Add an offlinequiz to the current course |
      | Description | Add an offlinequiz to the current course (Description) |
    And I follow "Add an offlinequiz to the current course"
    Then I should see "Add an offlinequiz to the current course (Description)"
