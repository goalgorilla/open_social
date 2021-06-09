@account @user @stability @stability-4 @perfect @api @3111945 @revoke-or-grant-roles-by-permission
Feature: Revoking or granting roles by permission
  Benefit: A user with the SM role can't grant himself the administrator role.
  Role: SM
  Goal/desire: Prevent role abuse.

  Scenario: As a SM I should not be able to grant myself the administrator role
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    # Since we only have the user1 (admin) user and the site manager,
    # the site manager user should be selected.
    When I check the box "edit-views-bulk-operations-bulk-form-0"
    And I select "Add a role to the selected users" from "Action"
    And I press the "Apply to selected items" button
    Then I should not see the text "Administrator"

  Scenario: As a SM I should not be able to revoke the administrator role
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    # Here we select the user1 (admin) account to make sure the SM
    # is not able to revoke the administrator role from a user with this role.
    When I check the box "edit-views-bulk-operations-bulk-form-1"
    And I select "Remove a role from the selected users" from "Action"
    And I press the "Apply to selected items" button
    Then I should not see the text "Administrator"
