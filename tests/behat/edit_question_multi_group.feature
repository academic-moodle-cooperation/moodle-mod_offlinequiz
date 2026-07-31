@mod @mod_offlinequiz @oqquestionbank @amc @debug
Feature: See if I can edit questions in only one group
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions in one group and add other questions in other groups

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
      | name            | course | fileformat | numgroups |
      | testofflinequiz | C1     | PDF        | 2         |
    And the following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory | qtype       | questionname     | group |
      | Test questions   | multichoice | Multi-choice-001 | A     |
      | Test questions   | multichoice | Multi-choice-001 | B     |

  @javascript
  Scenario: When I delete a question in one offlinequiz it doesn't get deleted in the other group
    Given I am on the "testofflinequiz" "offlinequiz activity" page logged in as teacher1
    And I follow "Questions"
    And I delete "Multi-choice-001" in the offlinequiz by clicking the delete icon
    And I switch to group "B"
    Then I should see "Multi-choice-001"
