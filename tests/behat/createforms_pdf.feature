@mod @mod_offlinequiz @createforms @amc
Feature: Within a moodle instance, a teacher should be able to create all forms of the offline quiz as PDF.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the forms as PDF.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name             | template    |
      | Test questions   | multichoice | Multi-choice-001 | two_of_four |

    And the following "mod_offlinequiz > offlinequizzes" exist:
      | name            | course | fileformat |
      | testofflinequiz | C1     | PDF        |
    And the following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory | qtype       | questionname     | group |
      | Test questions   | multichoice | Multi-choice-001 | A     |

  @javascript
  Scenario: Login as a teacher, add a new offline quiz to a course and there some multiple choice questions. Then create the forms as PDF for the offline quiz.
    Given  I am on the "testofflinequiz" "offlinequiz activity" page logged in as teacher1
    And I follow "Forms"
    And I press "Create forms"
    Then I should see "Question form for group A"
    Then I should see "Answer form for group A"
    Then I should see "Correction form for group A"
