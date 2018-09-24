@mod @mod_offlinequiz @adminsetting @amc
Feature: Within a moodle instance, an administrator should be able to set some default values for the entire Moodle installation.
  In order to define the adminsetting of an offline quiz.
  As an admin
  I need to default values for offline quiz settings.

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
    And I set the field "White value of paper" to "Dark grey"
#    And I set the field "Scanned form with grades" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Offline Quiz" to section "1" and I fill the form with:
      | Offline quiz name | Test offline quiz name |
      | Description | Add an offline quiz to the current course |
    And I follow "Test offline quiz name"
    And I navigate to "Edit settings" node in "Offline quiz administration"
    And I expand all fieldsets
    Then I should see "Dark grey"
    #Then "Scanned form with grades" should contain "1"

	
