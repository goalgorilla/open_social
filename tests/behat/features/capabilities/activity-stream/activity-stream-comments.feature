@wip @api @DS-1394 @activity_stream @comment
Feature: See comments in activity stream
  Benefit: Participate in discussions on the platform
  Role: As a LU
  Goal/desire: I do not want to see replies to comments in the activity stream

  @public
  Scenario: Do not see replies to comments in the activity stream
    Given users:
      | name       | status | pass        |
      | CreateUser | 1      | CreateUser  |
      | SeeUser    | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | field_event_date         | +8 days                |
      | status                   | 1                      |
      | field_content_visibility | public                 |
    And I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | public                 |
    When I wait for the queue to be empty
    And I am on the homepage
    And I should see "My Behat Event created"
    And I should see "My Behat Topic created"

    Given I am logged in as "SeeUser"
    And I am on the homepage

    And I click "My Behat Event created"
    When I fill in the following:
      | Add a comment | This is a first event comment |
    And I press "Comment"
    And I should see "This is a first event comment" in the "Main content"
    When I click "Reply"
    And I fill in the following:
      | Add a reply | This is a reply event comment |
    And I press "Reply"
    And I should see "This is a reply event comment"
    When I am on the homepage
    Then I should see "My Behat Event created"
    And I should see "This is a first event comment"
    And I should not see "This is a reply event comment"

    And I click "My Behat Topic created"
    When I fill in the following:
      | Add a comment | This is a first topic comment |
    And I press "Comment"
    And I should see "This is a first topic comment" in the "Main content"
    When I click "Reply"
    And I fill in the following:
      | Add a reply | This is a reply topic comment |
    And I press "Reply"
    And I should see "This is a reply topic comment"
    When I am on the homepage
    Then I should see "My Behat Topic created"
    And I should see "This is a first topic comment"
    And I should not see "This is a reply topic comment"
