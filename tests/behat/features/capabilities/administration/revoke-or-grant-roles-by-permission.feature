@account @user @stability @stability-4 @perfect @api @3111945 @revoke-or-grant-roles-by-permission
Feature: Revoking or granting roles by permission
  Benefit: A user with the SM role can't grant himself the administrator role.
  Role: SM
  Goal/desire: Prevent role abuse.

  Scenario: Successfully see user list
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    And I should see "Name or email contains"
    And I should see "Block the selected users"
    And I should see "Cancel the selected user accounts"
