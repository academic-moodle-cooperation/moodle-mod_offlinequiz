@mod @mod_offlinequiz @createforms @amc
Feature: Within a moodle instance, a teacher should be able to create the question forms of the offline quiz as DOCX.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the question forms as DOCX.

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

  @javascript
  Scenario: Login as a teacher, add a new offline quiz to a course and there some multiple choice questions. Then create the question forms as DOCX for the offline quiz.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Add an offline quiz and multiple choice questions to create question forms as DOCX |
      | Description | Add an offline quiz and multiple choice questions to create question forms as DOCX |
      | Format for question sheets | DOCX |
    And I follow "Add an offline quiz and multiple choice questions to create question forms as DOCX"
    And I navigate to "Group Questions" in current page administration
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I navigate to "Create forms" in current page administration
    And I follow "Download forms"
    Then I should see "Question form for group A (DOCX)"
    Then I should see "Answer form for group A"
    Then I should see "Correction form for group A"

