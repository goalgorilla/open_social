@api
Feature: Pagination in search should properly handle different user actions

  Background:
    Given events with non-anonymous author:
      | title                  | created     | body          | status | field_content_visibility | field_event_date |
      | Event one              | -1 seconds  | Shenanigans   | 1      | community                | now              |
      | Event two              | -2 seconds  | Shenanigans   | 1      | community                | now              |
      | Event three            | -3 seconds  | Shenanigans   | 1      | community                | now              |
      | Event four             | -4 seconds  | Shenanigans   | 1      | community                | now              |
      | Event five             | -5 seconds  | Shenanigans   | 1      | community                | now              |
      | Event six              | -6 seconds  | Shenanigans   | 1      | community                | now              |
      | Event seven            | -7 seconds  | Shenanigans   | 1      | community                | now              |
      | Event eight            | -8 seconds  | Shenanigans   | 1      | community                | now              |
      | Event nine             | -9 seconds  | Shenanigans   | 1      | community                | now              |
      | Event ten              | -10 seconds | Shenanigans   | 1      | community                | now              |
      | Event eleven           | -11 seconds | Shenanigans   | 1      | community                | now              |
      | Event twelve           | -12 seconds | Shenanigans   | 1      | community                | now              |
      | Event thirteen         | -13 seconds | Shenanigans   | 1      | community                | now              |
    And Search indexes are up to date

  Scenario Outline: The first ten results are by relevancy first and then showing newest
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"

    Then I should see "Event one"
    Then I should see "Event two"
    Then I should see "Event three"
    Then I should see "Event four"
    Then I should see "Event five"
    Then I should see "Event six"
    Then I should see "Event seven"
    Then I should see "Event eight"
    Then I should see "Event nine"
    Then I should see "Event ten"
    And I should not see "Event eleven"
    And I should not see "Event twelve"
    And I should not see "Event thirteen"

  Examples:
    | view    |
    | all     |
    | content |

  Scenario Outline: The pager splits into pages of 10 results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"

    Then I should not see "Event ten"
    And I should see "Event eleven"

  Examples:
    | view    |
    | all     |
    | content |

  Scenario Outline: New search queries start on the first page properly showing the results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"
    And I search <view> for "four"

    Then I should see "Event four"

  Examples:
    | view    |
    | all     |
    | content |
