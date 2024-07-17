@mod @mod_offlinequiz @settings_tutorial @amc
Feature: Within a moodle instance, a teacher should be able to show an offline quiz tutorial for students.
  In order to show an offline quiz tutorial at an offline quiz
  As a teacher
  I need to be able to set this value via the settings.

  Background:
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

  @javascript
  Scenario: Login as a teacher, add a new offline quiz to a course and set the value to show an offline quiz tutorial. Then log in as a student and check if the tutorial is shown.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a offlinequiz activity to course "Course 1" section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz 'Settings - Tutorial' |
      | Description | Add an offline quiz and multiple choice questions to create files |
      | Show an offline quiz tutorial to students. | Yes |
      | Availability | Show on course page |
    And I log out
    And I am on the "Test offline quiz 'Settings - Tutorial'" "offlinequiz activity" page logged in as student1
    Then I should see "Tutorial for offline quizzes"
