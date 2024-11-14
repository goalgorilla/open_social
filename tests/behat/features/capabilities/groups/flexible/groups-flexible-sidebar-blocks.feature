@api
Feature: Flexible groups sidebar blocks are correctly displayed
  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: Verified user should see all sidebar blocks in a group
    Given users:
      | name     | status | pass    | roles       |
      | Manager  | 1      | secret  | sitemanager |
      | John     | 1      | secret  | verified    |
    And groups:
      | label              | field_group_description | field_flexible_group_visibility | author  | type            | created  |
      | My Flexible Group  | My Description          | public                          | Manager | flexible_group  | 01/01/01 |

    When I am logged in as John
    And I click "All groups"
    And I click "My Flexible Group"

    Then I should see "Upcoming events" in the "Sidebar second"
    And I should see "Newest topics" in the "Sidebar second"
