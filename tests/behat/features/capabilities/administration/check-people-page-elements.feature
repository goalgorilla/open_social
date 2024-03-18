@api
Feature: View users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  # @todo https://www.drupal.org/project/social/issues/3334769

  Scenario: Successfully see user list
    Given I am logged in as an "sitemanager"

    When I am on "admin/people"

    # Check "main" container with filters
    Then I should see an "#edit-container-container-0" element
    # Check username ou email filter
    And I should see an "#edit-user" element
    # Check select role filter
    And I should see an "#edit-role" element
    # Check select group filter
    And I should see an "#edit-group" element
    # Check select status filter
    And I should see an "#edit-status" element

    # Check submit button
    And I should see the button "Filter"
    # Check reset button
    And I should see the button "Clear"

    # Check "secondary" container with filters
    And I press the "Show more" button
    And I should see an "#edit-container-container-1" element
    # Check Registration date block filter
    And I should see "Registered"
    And I should see an "#edit-created-op" element
    And I should see an "#edit-created-min" element
    And I should see an "#edit-created-max" element
    # Check Last login block filter
    And I should see "Last active"
    And I should see an "#edit-access-op" element
    And I should see an "#edit-access-min" element
    And I should see an "#edit-access-max" element
    # Check select permission filter
    And I should see an "#edit-permission" element

    # Check People table
    And I should see "Username"
    And I should see "Status"
    And I should see "Roles"
    And I should see "Member for"
    And I should see "Last activity"
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
