@administration @account @profile @stability @perfect @api @view-block-users @no-update
Feature: View users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  # @todo https://www.drupal.org/project/social/issues/3334769

  Scenario: Successfully see user list
    Given I am logged in as an "sitemanager"
    When I am on "admin/people"

    # Check username ou email filter
    Then I should see an "#edit-user" element
      # Check select role filter
      And I should see an "#edit-role" element
      # Check select permission filter
      And I should see an "#edit-permission" element
      # Check select status filter
      And I should see an "#edit-status" element
      # Check select group filter
      And I should see "Group"
      # Check Registration date block filter
      And I should see "Registration date"
      And I should see an "#edit-created-op" element
      And I should see an "#edit-created-min" element
      And I should see an "#edit-created-max" element
      # Check Last login block filter
      And I should see "Last login"
      And I should see an "#edit-login-op" element
      And I should see an "#edit-login-min" element
      And I should see an "#edit-login-max" element
      # Check filter buttons
      And I should see an "#edit-submit-user-admin-people" element

      # Check People table
      And I should see "Username"
      And I should see "Status"
      And I should see "Roles"
      And I should see "Member for"
      And I should see "Last access"
      And I should see "Operation"
      And I should see an "#edit-views-bulk-operations-bulk-form-0" element

      # Check action area
      And I should see an "#edit-action" element
      And I should see an "input.button[disabled]" element
      # Check action 'enablation'
      And I check the box "edit-views-bulk-operations-bulk-form-0"
      And I select "Send email" from "Action"
      And I should not see an "input.button[disabled]" element
      And I should see an "#edit-submit" element

