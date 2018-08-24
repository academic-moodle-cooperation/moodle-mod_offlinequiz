@mod @mod_offlinequiz @settings_tutorial
Feature: Within a moodle instance, a teacher should be able to show an offline quiz tutorial for students.
  In order to show an offline quiz tutorial at an offline quiz
  As a teacher
  I need to be able to set this value via the settings.

  @javascript
  Scenario: Login as a teacher, add a new offline quiz to a course and set the value to show an offline quiz tutorial. Then log in as a student and check if the tutorial is shown.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz 'Settings - Tutorial' |
      | Description | Add an offline quiz and multiple choice questions to create files |
      | Show an offline quiz tutorial to students. | Yes |
      | Visible | Show |
    And I log out
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test offline quiz 'Settings - Tutorial'"
    And I press "Start tutorial about the examination"
    Then I should see "Tutorial for offline quizzes"
