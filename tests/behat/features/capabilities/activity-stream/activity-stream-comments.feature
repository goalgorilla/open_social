@api @stability @activity_stream @comment @DS-1394
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
    Given I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | public                 |
    When I wait for the queue to be empty
    And I am on the homepage
    And I should see "My Behat Topic created"

    Given I am logged in as "SeeUser"
    When I am on the homepage
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
    When I am on "/user"
    Then I should see "My Behat Topic created"
    And I should see "This is a first topic comment"
    And I should not see "This is a reply topic comment"
    And I click "My Behat Topic created"
    When I fill in the following:
      | Add a comment | This is a second topic comment |
    And I press "Comment"
    When I fill in the following:
      | Add a comment | This is a third topic comment |
    And I press "Comment"
    When I am on "/user"
    And I should see "This is a third topic comment" in the "Main content"
    And I should not see "This is a first topic comment" in the "Main content"

  @group
  Scenario: See community event with comments in a group
    Given users:
      | name        | status | pass        |
      | CreateUser  | 1      | CreateUser  |
      | SeeUser     | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"

    And I click "Test open group"
    And I should see "Test open group" in the "Hero block"

    When I click "Events"
    And I click "Create Event"
    And I fill in the following:
      | Title | Test group event |
      | Date  | 2025-01-01  |
      | Time  | 11:00:00    |
      | Location name       | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    # TODO: Change title of this button when we will have one step
    And I press "Continue to final step"
    And I press "Create node in group"
    Then I should see "Test group event"
    When I click "Test open group"

    When I wait for the queue to be empty
    When I am on "/user"
    Then I should see "CreateUser created an event in group Test open group"
    And I should see "Test group event"

    And I click "Test group event"
    When I fill in the following:
      | Add a comment | This is a first event comment |
    And I press "Comment"
    And I should see "This is a first event comment"
    When I click "Reply"
    And I fill in the following:
      | Add a reply | This is a reply event comment |
    And I press "Reply"
    And I should see "This is a reply event comment"
    When I am on "/user"
    Then I should see "Test group event"
    And I should see "This is a first event comment"
    And I should not see "This is a reply event comment"
    And I click "Test group event"
    When I fill in the following:
      | Add a comment | This is a second event comment |
    And I press "Comment"
    When I fill in the following:
      | Add a comment | This is a third event comment |
    And I press "Comment"

    Given I am logged in as "SeeUser"
    And I click "CreateUser"
    Then I should see "CreateUser created an event in group Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

    And I click "Test open group"
    Then I should see "CreateUser created an event in group Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

    When I am on the homepage
    Then I should see "CreateUser created an event in group Test open group"
    And I should see "Test group event"
    And I should see "This is a third event comment"
    And I should not see "This is a first event comment"
    And I should not see "This is a reply event comment"

    When I go to "explore"
    Then I should not see "CreateUser created an event"
    And I should not see "Test group event"
    And I should not see "This is a third event comment"

    Given I am an anonymous user
    When I am on the homepage
    Then I should not see "CreateUser created an event in group Test open group"
    And I should not see "Test group event"
    And I should not see "This is a third event comment"

    When I go to "explore"
    Then I should not see "CreateUser created an event in group Test open group"
    And I should not see "Test group event"
    And I should not see "This is a third event comment"
