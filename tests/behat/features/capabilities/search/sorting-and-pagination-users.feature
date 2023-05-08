@api
Feature: Pagination in search for users should properly handle different user actions

  Background:
    Given interests terms:
      | tid | name        |
      | 999 | Shenanigans |
    Given users:
      | name          | created     | field_profile_interests | status |
      | User one      | -1 seconds  | 999                     | 1      |
      | User two      | -2 seconds  | 999                     | 1      |
      | User three    | -3 seconds  | 999                     | 1      |
      | User four     | -4 seconds  | 999                     | 1      |
      | User five     | -5 seconds  | 999                     | 1      |
      | User six      | -6 seconds  | 999                     | 1      |
      | User seven    | -7 seconds  | 999                     | 1      |
      | User eight    | -8 seconds  | 999                     | 1      |
      | User nine     | -9 seconds  | 999                     | 1      |
      | User ten      | -10 seconds | 999                     | 1      |
      | User eleven   | -11 seconds | 999                     | 1      |
      | User twelve   | -12 seconds | 999                     | 1      |
      | User thirteen | -13 seconds | 999                     | 1      |
    And Search indexes are up to date

  Scenario Outline: The first ten results are by relevancy first and then showing newest
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"

    Then I should see "User one"
    Then I should see "User two"
    Then I should see "User three"
    Then I should see "User four"
    Then I should see "User five"
    Then I should see "User six"
    Then I should see "User seven"
    Then I should see "User eight"
    Then I should see "User nine"
    Then I should see "User ten"
    And I should not see "User eleven"
    And I should not see "User twelve"
    And I should not see "User thirteen"

  Examples:
    | view  |
    | all   |
    | users |

  Scenario Outline: The pager splits into pages of 10 results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"

    Then I should not see "User ten"
    And I should see "User eleven"

  Examples:
    | view  |
    | all   |
    | users |

  Scenario Outline: New search queries start on the first page properly showing the results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"
    And I search <view> for "four"

    Then I should see "User four"

  Examples:
    | view  |
    | all   |
    | users |
