@api
Feature: See comments in activity stream in a group
  Benefit: Participate in discussions on the platform
  Role: As a Verified
  Goal/desire: I do not want to see replies to comments in the activity stream
  Related Stories: DS-923, DS-1394, DS-4211, DS-4886

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
    And comments:
      | author     | target_type | target_label     | parent_subject | status | subject  | field_comment_body             | comment_type |
      | CreateUser | node:event  | Test group event |                | 1      | comment1 | This is a first event comment  | comment      |
      | CreateUser | node:event  | Test group event | comment1       | 1      | reply1   | This is a reply event comment  | comment      |
      | CreateUser | node:event  | Test group event |                | 1      | comment2 | This is a second event comment | comment      |
      | CreateUser | node:event  | Test group event |                | 1      | comment3 | This is a third event comment  | comment      |
    And I wait for the queue to be empty

  Scenario: On the commenter's profile
    Given I am logged in as "SeeUser"

    When I am on the profile of "CreateUser"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

  Scenario: On the group stream
    Given I am logged in as "SeeUser"

    When I am on the stream of group "Test open group"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

  Scenario: On the homepage
    Given I am logged in as "SeeUser"

    When I am on the homepage

    Then I should not see "CreateUser created an event in Test open group"
    And I should not see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"


  Scenario: On the explore page
    Given I am logged in as "SeeUser"

    When I go to "explore"

    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

  Scenario: On the homepage as anonymous user
    Given I am an anonymous user

    When I am on the homepage

    Then I should not see "CreateUser created an event in Test open group"
    And I should not see "Test group event"
    And I should not see "This is a third event comment"

  Scenario: On the explore page as anonymous user
    Given I am an anonymous user

    When I go to "explore"

    Then I should not see "CreateUser created an event in Test open group"
    And I should not see "Test group event"
    And I should not see "This is a third event comment"
