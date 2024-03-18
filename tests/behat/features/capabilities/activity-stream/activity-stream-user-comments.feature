@api
Feature: See comments in activity stream on the user page
  Benefit: Participate in discussions on the platform
  Role: As a Verified
  Goal/desire: I do not want to see replies to comments in the activity stream
  Related Stories: DS-923, DS-1394, DS-4211, DS-4886

  Scenario: Do not see replies to comments in the activity stream
    Given users:
      | name       | status | pass        | roles    |
      | CreateUser | 1      | CreateUser  | verified |
      | SeeUser    | 1      | SeeUser     | verified |
    And topics:
      | author     | title                  | body         | status | field_content_visibility | field_topic_type |
      | CreateUser | My Behat Topic created | A test topic | 1      | public                   | news             |
    And comments:
      | author  | target_type | target_label           | parent_subject | status | subject  | field_comment_body            | comment_type |
      | SeeUser | node:topic  | My Behat Topic created |                | 1      | comment1 | This is a first topic comment | comment      |
      | SeeUser | node:topic  | My Behat Topic created | comment1       | 1      | reply1   | This is a reply topic comment | comment      |
    And I wait for the queue to be empty

    When I am logged in as "SeeUser"
    And I am on "/user"

    Then I should see "My Behat Topic created"
    And I should see "This is a first topic comment"
    And I should not see "This is a reply topic comment"

  Scenario: Only see the last comment on the activity stream
    Given users:
      | name       | status | pass        | roles    |
      | CreateUser | 1      | CreateUser  | verified |
      | SeeUser    | 1      | SeeUser     | verified |
    And topics:
      | author     | title                  | body         | status | field_content_visibility | field_topic_type |
      | CreateUser | My Behat Topic created | A test topic | 1      | public                   | news             |
    And comments:
      | author  | target_type | target_label           | parent_subject | status | subject  | field_comment_body             | comment_type |
      | SeeUser | node:topic  | My Behat Topic created |                | 1      | comment1 | This is a first topic comment  | comment      |
      | SeeUser | node:topic  | My Behat Topic created | comment1       | 1      | reply1   | This is a reply topic comment  | comment      |
      | SeeUser | node:topic  | My Behat Topic created |                | 1      | comment2 | This is a second topic comment | comment      |
      | SeeUser | node:topic  | My Behat Topic created |                | 1      | comment3 | This is a third topic comment  | comment      |
    And I wait for the queue to be empty

    When I am logged in as "SeeUser"
    And I am on "/user"

    Then I should see "This is a third topic comment" in the "Main content"
    And I should not see "This is a first topic comment" in the "Main content"
