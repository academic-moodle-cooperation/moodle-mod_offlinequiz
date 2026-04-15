@mod @mod_offlinequiz @mod_offlinequiz_leaderboard
Feature: Leaderboard visibility and access control

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                  |
      | teacher1 | Teacher   | One      | teacher1@example.com   |
      | student1 | Student   | One      | student1@example.com   |
      | student2 | Student   | Two      | student2@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

  @javascript
  Scenario: Leaderboard button is hidden when leaderboard is disabled
    Given the following "activities" exist:
      | activity    | name        | course | showleaderboard |
      | offlinequiz | Disabled LB | C1     | 0               |
    When I am on the "Disabled LB" "offlinequiz activity" page logged in as student1
    Then I should not see "View leaderboard"

  @javascript
  Scenario: Leaderboard button appears when leaderboard is enabled with names
    Given the following "activities" exist:
      | activity    | name      | course | showleaderboard |
      | offlinequiz | Named LB  | C1     | 1               |
    When I am on the "Named LB" "offlinequiz activity" page logged in as student1
    Then I should see "View leaderboard"

  @javascript
  Scenario: Leaderboard button appears when leaderboard is in anonymous mode
    Given the following "activities" exist:
      | activity    | name         | course | showleaderboard |
      | offlinequiz | Anon LB      | C1     | 2               |
    When I am on the "Anon LB" "offlinequiz activity" page logged in as student1
    Then I should see "View leaderboard"

  @javascript
  Scenario: Leaderboard page shows "not available" when accessed directly with leaderboard disabled
    Given the following "activities" exist:
      | activity    | name        | course | showleaderboard |
      | offlinequiz | Disabled LB | C1     | 0               |
    When I am on the "Disabled LB" "offlinequiz activity" page logged in as student1
    And I navigate to "mod/offlinequiz/leaderboard.php" with course module id
    Then I should see "The leaderboard is not enabled for this quiz"

  @javascript
  Scenario: Leaderboard page shows "no results" message when there are no completed attempts
    Given the following "activities" exist:
      | activity    | name     | course | showleaderboard |
      | offlinequiz | Empty LB | C1     | 1               |
    When I am on the "Empty LB" "offlinequiz activity" page logged in as student1
    And I follow "View leaderboard"
    Then I should see "No results are available yet"

  @javascript
  Scenario: Teacher can set leaderboard mode in activity settings
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I switch editing mode on
    And I add a offlinequiz activity to course "Course 1" section "1" and I fill the form with:
      | Offline quiz name | Leaderboard Settings Test |
      | Show leaderboard  | Enabled (show student names) |
    When I am on the "Leaderboard Settings Test" "offlinequiz activity settings" page
    Then the field "Show leaderboard" matches value "Enabled (show student names)"
