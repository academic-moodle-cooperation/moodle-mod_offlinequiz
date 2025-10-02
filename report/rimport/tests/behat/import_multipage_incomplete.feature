@mod @mod_offlinequiz @createforms @amc @offlinequiz_rimport @offlinequiz @_file_upload
Feature: If you import a multipage offlinequiz twice it should handle the partial result nicely.
  In order to create the forms of an offline quiz
  As a teacher
  I need to be able to add an offline quiz, add some existing questions and finally create the question forms as DOCX.

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
      | Test questions   | multichoice | Multi-choice-002 | two_of_four |
      | Test questions   | multichoice | Multi-choice-003 | two_of_four |
      | Test questions   | multichoice | Multi-choice-004 | two_of_four |
      | Test questions   | multichoice | Multi-choice-005 | two_of_four |
      | Test questions   | multichoice | Multi-choice-006 | two_of_four |
      | Test questions   | multichoice | Multi-choice-007 | two_of_four |
      | Test questions   | multichoice | Multi-choice-008 | two_of_four |
      | Test questions   | multichoice | Multi-choice-009 | two_of_four |
      | Test questions   | multichoice | Multi-choice-010 | two_of_four |
      | Test questions   | multichoice | Multi-choice-011 | two_of_four |
      | Test questions   | multichoice | Multi-choice-012 | two_of_four |
      | Test questions   | multichoice | Multi-choice-013 | two_of_four |
      | Test questions   | multichoice | Multi-choice-014 | two_of_four |
      | Test questions   | multichoice | Multi-choice-015 | two_of_four |
      | Test questions   | multichoice | Multi-choice-016 | two_of_four |
      | Test questions   | multichoice | Multi-choice-017 | two_of_four |
      | Test questions   | multichoice | Multi-choice-018 | two_of_four |
      | Test questions   | multichoice | Multi-choice-019 | two_of_four |
      | Test questions   | multichoice | Multi-choice-020 | two_of_four |
      | Test questions   | multichoice | Multi-choice-021 | two_of_four |
      | Test questions   | multichoice | Multi-choice-022 | two_of_four |
      | Test questions   | multichoice | Multi-choice-023 | two_of_four |
      | Test questions   | multichoice | Multi-choice-024 | two_of_four |
      | Test questions   | multichoice | Multi-choice-025 | two_of_four |
      | Test questions   | multichoice | Multi-choice-026 | two_of_four |
      | Test questions   | multichoice | Multi-choice-027 | two_of_four |
      | Test questions   | multichoice | Multi-choice-028 | two_of_four |
      | Test questions   | multichoice | Multi-choice-029 | two_of_four |
      | Test questions   | multichoice | Multi-choice-030 | two_of_four |
      | Test questions   | multichoice | Multi-choice-031 | two_of_four |
      | Test questions   | multichoice | Multi-choice-032 | two_of_four |
      | Test questions   | multichoice | Multi-choice-033 | two_of_four |
      | Test questions   | multichoice | Multi-choice-034 | two_of_four |
      | Test questions   | multichoice | Multi-choice-035 | two_of_four |
      | Test questions   | multichoice | Multi-choice-036 | two_of_four |
      | Test questions   | multichoice | Multi-choice-037 | two_of_four |
      | Test questions   | multichoice | Multi-choice-038 | two_of_four |
      | Test questions   | multichoice | Multi-choice-039 | two_of_four |
      | Test questions   | multichoice | Multi-choice-040 | two_of_four |
      | Test questions   | multichoice | Multi-choice-041 | two_of_four |
      | Test questions   | multichoice | Multi-choice-042 | two_of_four |
      | Test questions   | multichoice | Multi-choice-043 | two_of_four |
      | Test questions   | multichoice | Multi-choice-044 | two_of_four |
      | Test questions   | multichoice | Multi-choice-045 | two_of_four |
      | Test questions   | multichoice | Multi-choice-046 | two_of_four |
      | Test questions   | multichoice | Multi-choice-047 | two_of_four |
      | Test questions   | multichoice | Multi-choice-048 | two_of_four |
      | Test questions   | multichoice | Multi-choice-049 | two_of_four |
      | Test questions   | multichoice | Multi-choice-050 | two_of_four |
      | Test questions   | multichoice | Multi-choice-051 | two_of_four |
      | Test questions   | multichoice | Multi-choice-052 | two_of_four |
      | Test questions   | multichoice | Multi-choice-053 | two_of_four |
      | Test questions   | multichoice | Multi-choice-054 | two_of_four |
      | Test questions   | multichoice | Multi-choice-055 | two_of_four |
      | Test questions   | multichoice | Multi-choice-056 | two_of_four |
      | Test questions   | multichoice | Multi-choice-057 | two_of_four |
      | Test questions   | multichoice | Multi-choice-058 | two_of_four |
      | Test questions   | multichoice | Multi-choice-059 | two_of_four |
      | Test questions   | multichoice | Multi-choice-060 | two_of_four |
      | Test questions   | multichoice | Multi-choice-061 | two_of_four |
      | Test questions   | multichoice | Multi-choice-062 | two_of_four |
      | Test questions   | multichoice | Multi-choice-063 | two_of_four |
      | Test questions   | multichoice | Multi-choice-064 | two_of_four |
      | Test questions   | multichoice | Multi-choice-065 | two_of_four |
      | Test questions   | multichoice | Multi-choice-066 | two_of_four |
      | Test questions   | multichoice | Multi-choice-067 | two_of_four |
      | Test questions   | multichoice | Multi-choice-068 | two_of_four |
      | Test questions   | multichoice | Multi-choice-069 | two_of_four |
      | Test questions   | multichoice | Multi-choice-070 | two_of_four |
      | Test questions   | multichoice | Multi-choice-071 | two_of_four |
      | Test questions   | multichoice | Multi-choice-072 | two_of_four |
      | Test questions   | multichoice | Multi-choice-073 | two_of_four |
      | Test questions   | multichoice | Multi-choice-074 | two_of_four |
      | Test questions   | multichoice | Multi-choice-075 | two_of_four |
      | Test questions   | multichoice | Multi-choice-076 | two_of_four |
      | Test questions   | multichoice | Multi-choice-077 | two_of_four |
      | Test questions   | multichoice | Multi-choice-078 | two_of_four |
      | Test questions   | multichoice | Multi-choice-079 | two_of_four |
      | Test questions   | multichoice | Multi-choice-080 | two_of_four |
      | Test questions   | multichoice | Multi-choice-081 | two_of_four |
      | Test questions   | multichoice | Multi-choice-082 | two_of_four |
      | Test questions   | multichoice | Multi-choice-083 | two_of_four |
      | Test questions   | multichoice | Multi-choice-084 | two_of_four |
      | Test questions   | multichoice | Multi-choice-085 | two_of_four |
      | Test questions   | multichoice | Multi-choice-086 | two_of_four |
      | Test questions   | multichoice | Multi-choice-087 | two_of_four |
      | Test questions   | multichoice | Multi-choice-088 | two_of_four |
      | Test questions   | multichoice | Multi-choice-089 | two_of_four |
      | Test questions   | multichoice | Multi-choice-090 | two_of_four |
      | Test questions   | multichoice | Multi-choice-091 | two_of_four |
      | Test questions   | multichoice | Multi-choice-092 | two_of_four |
      | Test questions   | multichoice | Multi-choice-093 | two_of_four |
      | Test questions   | multichoice | Multi-choice-094 | two_of_four |
      | Test questions   | multichoice | Multi-choice-095 | two_of_four |
      | Test questions   | multichoice | Multi-choice-096 | two_of_four |
      | Test questions   | multichoice | Multi-choice-097 | two_of_four |
   And the following "mod_offlinequiz > offlinequizzes" exist:
      | name            | course |
      | testofflinequiz | C1     |
   And the following questions are added to the offlinequiz "testofflinequiz"
      | questioncategory | qtype       | questionname             | group |
      | Test questions   | multichoice | Multi-choice-001         | A |
      | Test questions   | multichoice | Multi-choice-002         | A |
      | Test questions   | multichoice | Multi-choice-003         | A |
      | Test questions   | multichoice | Multi-choice-004         | A |
      | Test questions   | multichoice | Multi-choice-005         | A |
      | Test questions   | multichoice | Multi-choice-006         | A |
      | Test questions   | multichoice | Multi-choice-007         | A |
      | Test questions   | multichoice | Multi-choice-008         | A |
      | Test questions   | multichoice | Multi-choice-009         | A |
      | Test questions   | multichoice | Multi-choice-010         | A |
      | Test questions   | multichoice | Multi-choice-011         | A |
      | Test questions   | multichoice | Multi-choice-012         | A |
      | Test questions   | multichoice | Multi-choice-013         | A |
      | Test questions   | multichoice | Multi-choice-014         | A |
      | Test questions   | multichoice | Multi-choice-015         | A |
      | Test questions   | multichoice | Multi-choice-016         | A |
      | Test questions   | multichoice | Multi-choice-017         | A |
      | Test questions   | multichoice | Multi-choice-018         | A |
      | Test questions   | multichoice | Multi-choice-019         | A |
      | Test questions   | multichoice | Multi-choice-020         | A |
      | Test questions   | multichoice | Multi-choice-021         | A |
      | Test questions   | multichoice | Multi-choice-022         | A |
      | Test questions   | multichoice | Multi-choice-023         | A |
      | Test questions   | multichoice | Multi-choice-024         | A |
      | Test questions   | multichoice | Multi-choice-025         | A |
      | Test questions   | multichoice | Multi-choice-026         | A |
      | Test questions   | multichoice | Multi-choice-027         | A |
      | Test questions   | multichoice | Multi-choice-028         | A |
      | Test questions   | multichoice | Multi-choice-029         | A |
      | Test questions   | multichoice | Multi-choice-030         | A |
      | Test questions   | multichoice | Multi-choice-031         | A |
      | Test questions   | multichoice | Multi-choice-032         | A |
      | Test questions   | multichoice | Multi-choice-033         | A |
      | Test questions   | multichoice | Multi-choice-034         | A |
      | Test questions   | multichoice | Multi-choice-035         | A |
      | Test questions   | multichoice | Multi-choice-036         | A |
      | Test questions   | multichoice | Multi-choice-037         | A |
      | Test questions   | multichoice | Multi-choice-038         | A |
      | Test questions   | multichoice | Multi-choice-039         | A |
      | Test questions   | multichoice | Multi-choice-040         | A |
      | Test questions   | multichoice | Multi-choice-041         | A |
      | Test questions   | multichoice | Multi-choice-042         | A |
      | Test questions   | multichoice | Multi-choice-043         | A |
      | Test questions   | multichoice | Multi-choice-044         | A |
      | Test questions   | multichoice | Multi-choice-045         | A |
      | Test questions   | multichoice | Multi-choice-046         | A |
      | Test questions   | multichoice | Multi-choice-047         | A |
      | Test questions   | multichoice | Multi-choice-048         | A |
      | Test questions   | multichoice | Multi-choice-049         | A |
      | Test questions   | multichoice | Multi-choice-050         | A |
      | Test questions   | multichoice | Multi-choice-051         | A |
      | Test questions   | multichoice | Multi-choice-052         | A |
      | Test questions   | multichoice | Multi-choice-053         | A |
      | Test questions   | multichoice | Multi-choice-054         | A |
      | Test questions   | multichoice | Multi-choice-055         | A |
      | Test questions   | multichoice | Multi-choice-056         | A |
      | Test questions   | multichoice | Multi-choice-057         | A |
      | Test questions   | multichoice | Multi-choice-058         | A |
      | Test questions   | multichoice | Multi-choice-059         | A |
      | Test questions   | multichoice | Multi-choice-060         | A |
      | Test questions   | multichoice | Multi-choice-061         | A |
      | Test questions   | multichoice | Multi-choice-062         | A |
      | Test questions   | multichoice | Multi-choice-063         | A |
      | Test questions   | multichoice | Multi-choice-064         | A |
      | Test questions   | multichoice | Multi-choice-065         | A |
      | Test questions   | multichoice | Multi-choice-066         | A |
      | Test questions   | multichoice | Multi-choice-067         | A |
      | Test questions   | multichoice | Multi-choice-068         | A |
      | Test questions   | multichoice | Multi-choice-069         | A |
      | Test questions   | multichoice | Multi-choice-070         | A |
      | Test questions   | multichoice | Multi-choice-071         | A |
      | Test questions   | multichoice | Multi-choice-072         | A |
      | Test questions   | multichoice | Multi-choice-073         | A |
      | Test questions   | multichoice | Multi-choice-074         | A |
      | Test questions   | multichoice | Multi-choice-075         | A |
      | Test questions   | multichoice | Multi-choice-076         | A |
      | Test questions   | multichoice | Multi-choice-077         | A |
      | Test questions   | multichoice | Multi-choice-078         | A |
      | Test questions   | multichoice | Multi-choice-079         | A |
      | Test questions   | multichoice | Multi-choice-080         | A |
      | Test questions   | multichoice | Multi-choice-081         | A |
      | Test questions   | multichoice | Multi-choice-082         | A |
      | Test questions   | multichoice | Multi-choice-083         | A |
      | Test questions   | multichoice | Multi-choice-084         | A |
      | Test questions   | multichoice | Multi-choice-085         | A |
      | Test questions   | multichoice | Multi-choice-086         | A |
      | Test questions   | multichoice | Multi-choice-087         | A |
      | Test questions   | multichoice | Multi-choice-088         | A |
      | Test questions   | multichoice | Multi-choice-089         | A |
      | Test questions   | multichoice | Multi-choice-090         | A |
      | Test questions   | multichoice | Multi-choice-091         | A |
      | Test questions   | multichoice | Multi-choice-092         | A |
      | Test questions   | multichoice | Multi-choice-093         | A |
      | Test questions   | multichoice | Multi-choice-094         | A |
      | Test questions   | multichoice | Multi-choice-095         | A |
      | Test questions   | multichoice | Multi-choice-096         | A |
      | Test questions   | multichoice | Multi-choice-097         | A |
  @javascript
  Scenario:
    And I am on the "testofflinequiz" "offlinequiz activity" page logged in as teacher1
    And I follow "Forms"
    And I press "Create forms"
    And I upload the file "/mod/offlinequiz/report/rimport/tests/behat/files/import_multipage_incomplete.zip" to the offlinequiz "testofflinequiz" and let it evaluate
    And I "Show all results" in the offlinequiz result overview of the offlinequiz "testofflinequiz"
    Then I should see "student 1" 2 times
    And I should see "partial"
