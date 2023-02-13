@administration @account @user @stability @perfect @api @revoke-or-grant-roles-by-permission @no-update
Feature: Revoking or granting roles by permission
  Benefit: A user with the SM role can't grant himself the administrator role.
  Role: SM
  Goal/desire: Prevent role abuse.

  Scenario: As a SM I should not be able to grant myself the administrator role
    Given I am logged in as an "sitemanager"
      And I am on "admin/people"
    # Filter the users list to show only site managers
    When I select "Site manager" from "role"
      And I press "Filter"
      And I check the box "edit-views-bulk-operations-bulk-form-0"
      And I select "Add a role to the selected users" from "Action"
      And I press the "Apply to selected items" button
    Then I should not see the text "Administrator"

  Scenario: As a SM I should not be able to revoke the administrator role
    Given I am logged in as an "sitemanager"
    And I am on "admin/people"
    # Filter the users list to show only administrators
    When I select "Administrator" from "role"
      And I press "Filter"
      And I check the box "edit-views-bulk-operations-bulk-form-0"
      And I select "Remove a role from the selected users" from "Action"
      And I press the "Apply to selected items" button
    Then I should not see the text "Administrator"
