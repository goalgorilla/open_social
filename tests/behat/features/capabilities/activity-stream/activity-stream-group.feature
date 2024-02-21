@api
Feature: See created content in group activity stream
  This test can be thrown away when activity-stream-group-comments is enabled.
  On the group stream is currently not working which should be fixed as part of
  https://www.drupal.org/project/social/issues/3422859

  Background:
    Given users:
      | name       | status | pass        | roles    |
      | CreateUser | 1      | CreateUser  | verified |
      | SeeUser    | 1      | SeeUser     | verified |
    And groups:
      | author     | label           | field_group_description | type           | field_flexible_group_visibility | field_group_allowed_visibility | field_group_allowed_join_method | langcode |
      | CreateUser | Test open group | Description text        | flexible_group | community                       | public                         | direct                          | en       |
    And events:
      | author     | group           | title            | field_content_visibility | field_event_date    | field_event_date_end | field_event_location | body                  |
      | CreateUser | Test open group | Test group event | public                   | 2025-01-01 11:00:00 | 2025-01-01 11:00:00  | GG HQ                | Body description text |
    And I wait for the queue to be empty

  Scenario: On the author's profile
    Given I am logged in as "SeeUser"

    When I am on the profile of "CreateUser"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"

  @disabled
  Scenario: On the group stream
    Given I am logged in as "SeeUser"

    When I am on the stream of group "Test open group"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"

  Scenario: On the homepage
    Given I am logged in as "SeeUser"

    When I am on the homepage

    Then I should not see "CreateUser created an event in Test open group"

  Scenario: On the explore page
    Given I am logged in as "SeeUser"

    When I go to "explore"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"

  Scenario: On the homepage as anonymous user
    Given I am an anonymous user

    When I am on the homepage

    Then I should not see "CreateUser created an event in Test open group"

  Scenario: On the explore page as anonymous user
    Given I am an anonymous user

    When I go to "explore"

    Then I should see "CreateUser created an event in Test open group"
