@mod @mod_offlinequiz @adding @description @amc
Feature: Within a moodle instance, an administrator should be able to set the field "Description" of an offlinequiz instance as required via the general adminsetting at "Common activity settings".
   In order to have a consistent structure of settings
   As an admin
   I need to be able to define in the adminsettings whether the activity description is required and also is valid within a offlinequiz instance.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@teacher.com |
	And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 0|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Switch as an admin to the adminsetting for the description field and define as required field. Then login as a teacher and try to add an offlinequiz instance without a description.

    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Common activity settings" in site administration
    And I set the field "Require activity description" to "Yes"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    When I add a "Offline Quiz" to section "2" and I fill the form with:
      | Offline quiz name | Add an offlinequiz to the current course |
    Then I should see "- Required"