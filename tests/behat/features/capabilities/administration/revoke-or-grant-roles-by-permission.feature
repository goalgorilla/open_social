@account @user @stability @stability-4 @perfect @api @3111945 @revoke-or-grant-roles-by-permission
Feature: Revoking or granting roles by permission
  Benefit: A user with the SM role can't grant himself the administrator role.
  Role: SM
  Goal/desire: Prevent role abuse.

  Scenario: As a SM I should not be able to grant myself the administrator role
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    Then I should see "Action"

  # SM should not be able to revoke the administrator role
