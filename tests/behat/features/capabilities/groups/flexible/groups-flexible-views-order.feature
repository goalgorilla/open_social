@api @javascript @flexible-groups @flexible-groups-order
Feature: Flexible groups are correctly ordered on views pages.
  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: Verified should see correct groups order on "My groups" page.
    Given users:
      | name   | status | pass    | roles    |
      | John   | 1      | secret  | verified |
    Given groups:
      | label    | field_group_description | field_flexible_group_visibility | author | type            | created   |
      | Group AAA  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group BBB  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group CCC  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group DDD  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group EEE  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group FFF  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group GGG  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group HHH  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group III  | John's group            | public                          | John   | flexible_group  | 999999999 |
      | Group JJJ  | John's group            | public                          | John   | flexible_group  | 999999999 |

    When I am logged in as John
    And I click "My groups"
    Then I should see "Group AAA"
    And I should see "Group FFF"
    And I should not see "Group GGG"
    And I should not see "Group JJJ"

    When I click "Older items"
    Then I should see "Group GGG"
    And I should see "Group JJJ"
    And I should not see "Group AAA"
    And I should not see "Group FFF"
