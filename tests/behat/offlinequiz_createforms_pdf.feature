@mod @mod_offlinequiz @createforms
Feature: Within a moodle instance, a teacher should be able to create all forms of the offline quiz as PDF.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the forms as PDF.

  @ javascript
  Scenario: Login as a teacher, add a new offlinequiz to a course and there some multiple choice questions. Then create the forms as PDF for the offline quiz.
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
      | questioncategory | qtype     | name              | user     | questiontext    |
      | Test questions   | essay     | question 1 name | admin    | Question 1 text |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Questions" node in "Course administration > Question bank"
    And I click on "Edit" "link" in the "question 1 name" "table_row"
    And I set the following fields to these values:
      | Tags | foo |
    And I press "id_submitbutton"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Add an offline quiz and multiple choice questions to create files as PDF |
      | Description | Add an offline quiz and multiple choice questions to create files as PDF |
    And I follow "Add an offline quiz and multiple choice questions to create files as PDF"
    And I navigate to "Group Questions" in current page administration
    And I click on ".add-menu" "css_element"
    And I click on ".questionbank" "css_element"
    
    @javascript
  Scenario: The questions can be filtered by tag
    When I set the field "Filter by tags..." to "foo"
    And I press key "13" in the field "Filter by tags..."
    Then I should see "question 1 name" in the "categoryquestions" "table"
    
    And I follow "Create forms"
    And I follow "PDF forms"
    And I press "Create forms"
    Then I should see "Question form for group A"
    Then I should see "Answer form for group A"
    Then I should see "Correction form for group A"
