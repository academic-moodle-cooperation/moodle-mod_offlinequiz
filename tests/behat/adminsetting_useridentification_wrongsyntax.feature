@mod @mod_offlinequiz @adminsetting @amc
Feature: Within a moodle instance, an administrator should be able to set the value for "User identification" for the entire Moodle installation.

  Scenario: Switch as an admin to the adminsettings of the module offlinequiz and change the value of "User identification" to an invalid syntax then this value can't be saved.
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Offline Quiz" in site administration
    And I set the field "User identification" to "7=idnumber"
    And I press "Save changes"
    Then I should see "Invalid formula for user identification"
    And I navigate to "Plugins > Activity modules > Offline Quiz" in site administration
    Then the field "User identification" matches value "[7]=idnumber"