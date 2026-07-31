@mod @mod_offlinequiz @oqquestionbank @amc @debug
Feature: See if already added questions are displayed correctly in the questionbank
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the forms as PDF.

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
      | name            | course | fileformat |
      | testofflinequiz | C1     | PDF        |
    And the following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory | qtype       | questionname     | group |
      | Test questions   | multichoice | Multi-choice-001 | A     |
    And the following "core_question > updated questions" exist:
      | questioncategory | question         | name             | questiontext                   | status |
      | Test questions   | Multi-choice-001 | Multi-choice-001 | Answer the updated question    | ready  |

  @javascript
  Scenario: See if I can add an already added question in another version
    Given I am on the "testofflinequiz" "offlinequiz activity" page logged in as teacher1
    And I follow "Questions"
    Then I cannot add the following questions:
      | questionbank                | questioncategory | questionname     |
      | System shared question bank | Test questions   | Multi-choice-001 |
