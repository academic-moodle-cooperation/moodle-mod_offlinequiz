@mod @mod_offlinequiz @createforms @amc
Feature: Within a moodle instance, a teacher should be able to create all forms of the offline quiz for 6 groups.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, set the value for groups via the settings, add some existing questions and finally create the forms for 6 groups.

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
  Scenario: Login as a teacher, add a new offlinequiz to a course and set the value for groups to 6. Then add there some multiple choice questions and create the forms for all groups within the offline quiz.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Add an offline quiz and multiple choice questions to create files for 6 groups |
      | Description | Add an offline quiz and multiple choice questions to create files for 6 groups |
	  | Number of groups | 6 |
    And I follow "Add an offline quiz and multiple choice questions to create files for 6 groups"
    And I navigate to "Group Questions" in current page administration
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I set the field "groupnumber" to "Questions in group B"
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I set the field "groupnumber" to "Questions in group C"
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I set the field "groupnumber" to "Questions in group D"
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I set the field "groupnumber" to "Questions in group E"
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I set the field "groupnumber" to "Questions in group F"
    And I open the "last" add to quiz menu
    And I follow "from question bank"
    Then I should see "Multi-choice-001" in the "categoryquestions" "table"
    And I set the field with xpath "//tr[contains(normalize-space(.), 'Multi-choice-001')]//input[@type='checkbox']" to "1"
    And I press "Add to offline quiz"
    And I navigate to "Create forms" in current page administration
    And I follow "Download forms"
    Then I should see "Question form for group A"
	Then I should see "Question form for group B"
	Then I should see "Question form for group C"
	Then I should see "Question form for group D"
	Then I should see "Question form for group E"
	Then I should see "Question form for group F"
	Then I should see "Answer form for group A"
	Then I should see "Answer form for group B"
	Then I should see "Answer form for group C"
	Then I should see "Answer form for group D"
	Then I should see "Answer form for group E"
	Then I should see "Answer form for group F"
    Then I should see "Correction form for group A"
	Then I should see "Correction form for group B"
	Then I should see "Correction form for group C"
	Then I should see "Correction form for group D"
	Then I should see "Correction form for group E"
	Then I should see "Correction form for group F"
