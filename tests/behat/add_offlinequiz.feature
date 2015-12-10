@mod @mod_offlinequiz
Feature: Add an offlinequiz
  In order to evaluate students
  As a teacher
  I need to create an offlinequiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz name        |
      | Description | Test offline quiz description |
      | Display description on course page | 1 | 
    And I log out
    And I log in as "student1"
    And I follow "Course 1"

   @javascript
   Scenario: Add and configure offline quiz with Javascript enabled
    Then I should see "Test offline quiz name"
    And I log out
