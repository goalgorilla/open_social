@api @search @stability @DS-3624 @stability-3 @search-all
Feature: Search
  Benefit: In order to find specific content of any type
  Role: As a LU
  Goal/desire: I want to search the site

  Scenario: Successfully search groups
    Given users:
      | name           | status | pass   |
      | tjakka user    | 1      | maxic  |
    Given groups:
      | title             | description     | author        | type                  | language |
      | Tjakka group      | Tjakka group    | tjakka user   | closed_group          | en       |
      | Tjakka group two  | Tjakka group    | tjakka user   | open_group            | en       |
    Given "event" content:
      | title             | body          | status | field_content_visibility |
      | Tjakka event      | Description   | 1      | public                   |
    And "topic" content:
      | title             | body          | status | field_content_visibility |
      | Tjakka topic      | Description   | 1      | public                   |
      | Tjakka topic two  | Description   | 1      | community                |
    And Search indexes are up to date
    And I am on "search/all/tjakka"
    Then I should see "Tjakka event"
    And I should see "Tjakka topic"
    And I should not see "Tjakka topic two"
    When I am logged in as an "authenticated user"
    And I am on "search/all/tjakka"
    Then I should see "Tjakka group"
    And I should see "Tjakka group two"
    And I should see "Tjakka event"
    And I should see "Tjakka topic"
    And I should see "tjakka user"
