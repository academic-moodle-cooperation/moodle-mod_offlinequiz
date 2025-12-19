@mod @mod_offlinequiz @offlinequiz_correct @createforms @amc @offlinequiz @_viewings_scans_in_results @_file_upload
Feature: After importing files for evaluating, corrected scans should be possible to view.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to import files and to view the scans after evaluating them.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | 1 | teacher1@example.com | 1111111 |
      | student1 | student | 1 | student1@example.com | 0123456 |
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1|
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name             | template    |
      | Test questions   | multichoice | Multi-choice-001 | two_of_four |
    And the following "mod_offlinequiz > offlinequizzes" exist:
      | name            | course |
      | testofflinequiz | C1     |
    And the following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory | qtype       | questionname             | group |
      | Test questions   | multichoice | Multi-choice-001         | A |
  @javascript
  Scenario: I can view the scans of a student after importing the file
    When I am on the "testofflinequiz" "offlinequiz activity" page logged in as teacher1
    And I follow "Forms"
    And I press "Create forms"
    And I upload the file "/mod/offlinequiz/report/correct/tests/behat/files/view_scanned_page.zip" to the offlinequiz "testofflinequiz" and let it evaluate
    And I "Show all results" in the offlinequiz result overview of the offlinequiz "testofflinequiz"
    And I click on "Grade" "button"
    And I click on "-" "link"
    And I click on "Edit scanned form (Page 1)" "link"
    Then I should see "student1"
