@mod @mod_offlinequiz @adding @amc @duplicate
Feature: In a course, a teacher should be able to duplicate an offlinequiz annd be able to edit the description

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
    And the following "mod_offlinequiz > offlinequizzes" exist:
      | name                  | course | intro                      | introformat |
      | duplicateddescription | C1     | <h4> descriptiontext </h4> | 1           |

  @javascript
  Scenario: If I duplicate an offline test with an html description, the description is displayed formatted
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I switch editing mode on
    And I duplicate "duplicateddescription" activity
    And I am on the "duplicateddescription" "offlinequiz activity" page
    And I click on "Settings" "link"
    #check if the <h4> tag is displayed in the editor
    Then I should not see "h4" in the "#fitem_id_introeditor .tox-editor-container" "css_element"
