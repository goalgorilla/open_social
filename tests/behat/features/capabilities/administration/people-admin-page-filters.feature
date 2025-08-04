@api
Feature: View "Hubs Role" filter on admin people page
  Benefit: In order to filter users by their group roles
  Role: Site Manager
  Goal/desire: See and use the Hubs Role filter on admin people page

  Background:
    Given users:
      | name       | mail               | status |
      | FG Manager      | owner@example.com  | 1      |
      | FG Member | member@example.com | 1      |

    And groups:
      | label          | author | type           | langcode | field_flexible_group_visibility | field_group_allowed_visibility |
      | Flexible Group | FG Manager  | flexible_group | en       | public                          | public                         |

    And group members:
      | group          | user       |
      | Flexible Group | FG Member |

  Scenario: Successfully see Hubs Role filter on admin people page
    When I am logged in as an "sitemanager"
    And I am on "admin/people"

    # Check that the Hubs Role filter is visible.
    Then I should see "Hubs Role"
    And I should see an "#edit-group-roles" element

    # Check that the filter dropdown is functional.
    And I click the element with css selector "#edit-group-roles"
    And I should see "Group manager"
    And I should see "Group member"

    # Check filtering by Group member role.
    And I select "Group member" from "Hubs Role"
    And I press "Filter"
    And I should see "FG Member"
    # Owner is member, so we have it here.
    And I should see "FG Manager"

    And I press "Clear"

    # Check filtering by Group manager role.
    And I select "Group manager" from "Hubs Role"
    And I press "Filter"
    And I should see "FG Manager"
    And I should not see "FG Member"

    And I press "Clear"

    # Check multiple options working properly.
    And I click the element with css selector "#edit-group-roles"
    And I select "Group manager" from "Hubs Role"
    And I select "Group member" from "Hubs Role"
    And I should see "FG Manager"
    And I should see "FG Member"
