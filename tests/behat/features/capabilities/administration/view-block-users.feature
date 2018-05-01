@account @profile @stability @stability-4 @perfect @api @2840664
Feature: View and block users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  Scenario: Successfully see user list
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    And I should see "Name or email contains"
    And I should see "Block the selected user(s)"
    And I should see "Cancel the selected user account(s)"
    And I should not see "Add the Administrator role to the selected users"

