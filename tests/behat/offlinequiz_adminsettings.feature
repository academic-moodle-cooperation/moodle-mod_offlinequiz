@mod @mod_offlinequiz @adminsetting
Feature: Within a moodle instance, an administrator should be able to set some default values
  for the entire Moodle installation.
  In order to define the adminsetting of an offline quiz.
  As an administrator I need to have fields to set different default values.

  @javascript
  Scenario: Switch as an admin to the adminsettings of the module offlinequiz and change
            some default values. Then login as a teacher and add a new offline quiz to
            a course and check whether the default value has changed.
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
	And I log in as "admin"
    And I navigate to "Offline Quiz" node in "Site administration > Plugins > Activity modules"
#    And I fill the form with:
#	  | White value of paper | Dark grey |
    And I set the field "White value of paper" to "Dark grey"
	And I press "Save changes"
    And I log out
	And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz name |
	  | Description | Add an offline quiz to the current course |
	And I follow "Test offline quiz name"
	And I navigate to "Edit settings" node in "Offline quiz administration"
    Then I should see "Dark grey"
#	Then "sheetclosed" should contain "1"
	