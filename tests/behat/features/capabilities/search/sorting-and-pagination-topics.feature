@api
Feature: Pagination in search for topics should properly handle different user actions

  Background:
    Given topics with non-anonymous author:
      | title                  | created     | body          | status | field_content_visibility | field_topic_type |
      | Topic one              | -1 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic two              | -2 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic three            | -3 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic four             | -4 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic five             | -5 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic six              | -6 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic seven            | -7 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic eight            | -8 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic nine             | -9 seconds  | Shenanigans   | 1      | community                | news             |
      | Topic ten              | -10 seconds | Shenanigans   | 1      | community                | news             |
      | Topic eleven           | -11 seconds | Shenanigans   | 1      | community                | news             |
      | Topic twelve           | -12 seconds | Shenanigans   | 1      | community                | news             |
      | Topic thirteen         | -13 seconds | Shenanigans   | 1      | community                | news             |
    And Search indexes are up to date

  Scenario Outline: The first ten results are by relevancy first and then showing newest
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"

    Then I should see "topic one"
    Then I should see "topic two"
    Then I should see "topic three"
    Then I should see "topic four"
    Then I should see "topic five"
    Then I should see "topic six"
    Then I should see "topic seven"
    Then I should see "topic eight"
    Then I should see "topic nine"
    Then I should see "Topic ten"
    And I should not see "Topic eleven"
    And I should not see "Topic twelve"
    And I should not see "Topic thirteen"

  Examples:
    | view    |
    | all     |
    | content |

  Scenario Outline: The pager splits into pages of 10 results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"

    Then I should not see "Topic ten"
    And I should see "Topic eleven"

  Examples:
    | view    |
    | all     |
    | content |

  Scenario Outline: New search queries start on the first page properly showing the results
    Given I am logged in as a user with the verified role

    When I search <view> for "Shenanigans"
    And I click "Next page"
    And I search <view> for "four"

    Then I should see "Topic four"

  Examples:
    | view    |
    | all     |
    | content |
