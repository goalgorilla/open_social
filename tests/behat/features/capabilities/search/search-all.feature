@api @search @stability @DS-3624 @stability-3 @search-all
Feature: Search Everything
  Benefit: In order to find specific content of any type
  Role: As a LU
  Goal/desire: I want to search the site

  Background:
    Given I disable that the registered users to be verified immediately
    And users:
      | name                 | status | roles    |
      | tjakka new user      | 1      |          |
      | tjakka verified user | 1      | verified |
      | blocked user         | 0      | verified |
    And groups with non-anonymous owner:
      | label                  | field_group_description | type           | field_flexible_group_visibility |
      | Tjakka public group    | Tjakka group            | flexible_group | public                          |
      | Tjakka community group | Tjakka group            | flexible_group | community                       |
      | Tjakka secret group    | Tjakka group            | flexible_group | members                         |
    And events with non-anonymous author:
      | title                  | body        | field_content_visibility | field_event_date    |
      | Tjakka public event    | Description | public                   | 2100-01-01T12:00:00 |
      | Tjakka community event | Description | community                | 2100-01-01T12:00:00 |
      | Tjakka group event     | Description | group                    | 2100-01-01T12:00:00 |
    And topics with non-anonymous author:
      | title                  | body          | status | field_content_visibility | field_topic_type |
      | Tjakka public topic    | Description   | 1      | public                   | news             |
      | Tjakka community topic | Description   | 1      | community                | news             |
      | Tjakka group topic     | Description   | 1      | group                    | news             |
    And Search indexes are up to date

  Scenario: Empty state
    Given I am an anonymous user

    When I search all for "not in index"

    Then I should see "No results found"

  Scenario: Anonymous user can use all search
    Given I am an anonymous user

    When I search all for "tjakka"

    Then I should not see "tjakka new user"
    And I should not see "tjakka verified user"
    And I should not see "blocked user"

    And I should see "Tjakka public group"
    And I should not see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should see "Tjakka public event"
    And I should not see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should see "Tjakka public topic"
    And I should not see "Tjakka community topic"
    And I should not see "Tjakka group topic"

  Scenario: Authenticated user can use all search
    Given I am logged in as a user with the authenticated role

    And I search all for "tjakka"

    Then I should not see "tjakka new user"
    And I should not see "tjakka verified user"
    And I should not see "blocked user"

    And I should see "Tjakka public group"
    And I should see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should see "Tjakka public event"
    And I should not see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should see "Tjakka public topic"
    And I should not see "Tjakka community topic"
    And I should not see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Verified users can use all search
    When I am logged in as a user with the verified role

    And I search all for "tjakka"

    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should see "Tjakka public group"
    And I should see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should see "Tjakka public event"
    And I should see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should see "Tjakka public topic"
    And I should see "Tjakka community topic"
    And I should not see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Contentmanager users can use all search
    When I am logged in as a user with the contentmanager role

    And I search all for "tjakka"

    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should see "Tjakka public group"
    And I should see "Tjakka community group"
    And I should see "Tjakka secret group"

    And I should see "Tjakka public event"
    And I should see "Tjakka community event"
    And I should see "Tjakka group event"

    And I should see "Tjakka public topic"
    And I should see "Tjakka community topic"
    And I should see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Sitemanager users can use all search
    When I am logged in as a user with the sitemanager role

    And I search all for "tjakka"

    # @todo it might be a UX improvement if SM can find unverified users.
    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should see "Tjakka public group"
    And I should see "Tjakka community group"
    And I should see "Tjakka secret group"

    And I should see "Tjakka public event"
    And I should see "Tjakka community event"
    And I should see "Tjakka group event"

    And I should see "Tjakka public topic"
    And I should see "Tjakka community topic"
    And I should see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout
