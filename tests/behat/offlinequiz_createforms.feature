@mod @mod_offlinequiz @createforms
Feature: Within a moodle instance, a teacher should be able to create all forms of the offline quiz.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the forms.

  @ javascript
  Scenario: Login as a teacher, add a new grouptool to a course and there some multiple choice questions. Then create the forms for the offline quiz.
    Given the following "users" exist:
      | username | firstname | lastname | email
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1|
    And the following "course enrolements" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
	And I log in as "teacher1"
	    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz name |
	  | Description | Add an offline quiz and multiple choice questions to create files |
    And I press "Save and display"
    And I follow "Group Questions"
	And I follow "Editing group questions"
	And I follow "Add"
	And I follow "from question bank"
	And I fill the form with:
	   | category | mc-questions |
	   | Select all | 1 |
	And I press "Add to offline quiz"
	And I follow "Create forms"
	And I follow "PDF forms"
	And I press "Create forms"
	Then I should see "Question form for group A"
	Then I should see "Answer form for group A"
	Then I should see "Correction form for group A"