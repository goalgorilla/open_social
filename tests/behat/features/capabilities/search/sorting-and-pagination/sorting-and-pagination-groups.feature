@api
Feature: Pagination in search for groups should properly handle different user actions

  Background:
    Given groups with non-anonymous owner:
      | label          | created     | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Group one      | -1 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group two      | -2 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group three    | -3 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group four     | -4 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group five     | -5 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group six      | -6 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group seven    | -7 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group eight    | -8 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group nine     | -9 seconds  | Shenanigans             | flexible_group | en       | public                          |
      | Group ten      | -10 seconds | Shenanigans             | flexible_group | en       | public                          |
      | Group eleven   | -11 seconds | Shenanigans             | flexible_group | en       | public                          |
      | Group twelve   | -12 seconds | Shenanigans             | flexible_group | en       | public                          |
      | Group thirteen | -13 seconds | Shenanigans             | flexible_group | en       | public                          |
    And Search indexes are up to date

  Scenario Outline: The first ten results are by relevancy first and then showing newest
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"

    Then I should see "Group one"
    Then I should see "Group two"
    Then I should see "Group three"
    Then I should see "Group four"
    Then I should see "Group five"
    Then I should see "Group six"
    Then I should see "Group seven"
    Then I should see "Group eight"
    Then I should see "Group nine"
    Then I should see "Group ten"
    And I should not see "Group eleven"
    And I should not see "Group twelve"
    And I should not see "Group thirteen"

  Examples:
    | view   |
    | all    |
    | groups |

  Scenario Outline: The pager splits into pages of 10 results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"

    Then I should not see "Group ten"
    And I should see "Group eleven"

  Examples:
    | view   |
    | all    |
    | groups |

  Scenario Outline: New search queries start on the first page properly showing the results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"
    And I search <view> for "four"

    Then I should see "Group four"

  Examples:
    | view   |
    | all    |
    | groups |
