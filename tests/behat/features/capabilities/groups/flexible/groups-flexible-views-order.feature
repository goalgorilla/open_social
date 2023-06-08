@api @javascript @flexible-groups @flexible-groups-order
Feature: Flexible groups are correctly ordered on views pages.
  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: Verified should see correct groups order on "My groups" page.
    Given users:
      | name   | status | pass    | roles    |
      | John   | 1      | secret  | verified |
    Given groups:
      | label      | field_group_description | field_flexible_group_visibility | author | type            | created  |
      | Group AAA  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group BBB  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group CCC  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group DDD  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group EEE  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group FFF  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group GGG  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group HHH  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group III  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |
      | Group JJJ  | Happy new 2001 year     | public                          | John   | flexible_group  | 01/01/01 |

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
