@api
Feature: Requests to join for a group can be managed

  Background:
    Given I enable the module social_group_flexible_group
    And I enable the module social_group_request
    And groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | request                         |
    And users:
      | name           | mail                | status | roles    |
      | Pending Member | pending@example.com | 1      | verified |
    And group membership requests:
      | group      | user           |
      | Test group | Pending Member |

  Scenario: As a group manager I can approve a membership request
    Given I am logged in as a user with the "verified" role
    # Todo: Fix the role declaration.
    And I am a member of "Test group" with the "flexible_group-group_manager" role

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Approve membership"
    And I press "Yes"

    Then I should see the success message "Membership request approved"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should see "Pending Member"

  Scenario: As a group manager I can reject a membership request
    Given I am logged in as a user with the "verified" role
    # Todo: Fix the role declaration.
    And I am a member of "Test group" with the "flexible_group-group_manager" role

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Toggle Dropdown"
    And I click "Reject membership"
    And I press "Yes"

    Then I should see the success message "Membership request rejected"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should not see "Pending Member"

  Scenario: As a sitemanager I can approve a membership request for groups I'm a member of
    Given I am logged in as a user with the "sitemanager" role
    And I am a member of "Test group"

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Approve membership"
    And I press "Yes"

    Then I should see the success message "Membership request approved"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should see "Pending Member"

  Scenario: As a sitemanager I can reject a membership request for groups I'm a member of
    Given I am logged in as a user with the "sitemanager" role
    And I am a member of "Test group"

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Toggle Dropdown"
    And I click "Reject membership"
    And I press "Yes"

    Then I should see the success message "Membership request rejected"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should not see "Pending Member"

  Scenario: As a sitemanager I can approve a membership request for groups I'm not a member of
    Given I am logged in as a user with the "sitemanager" role

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Approve membership"
    And I press "Yes"

    Then I should see the success message "Membership request approved"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should see "Pending Member"

  Scenario: As a sitemanager I can reject a membership request for groups I'm not a member of
    Given I am logged in as a user with the "sitemanager" role

    When I am viewing the "membership-requests" page of group "Test group"
    And I press "Toggle Dropdown"
    And I click "Reject membership"
    And I press "Yes"

    Then I should see the success message "Membership request rejected"
    And I should see "No membership requests available."
    And I am viewing the "members" page of group "Test group"
    And I should not see "Pending Member"
