@wip @api @DS-1255 @activity_stream @topic @create
Feature: See and get notified when content is created
  Benefit: So I can discover new content on the platform
  Role: As a LU
  Goal/desire: I want see and get notified when content is created

  @public
  Scenario: See public topic and event
    Given users:
      | name       | status | pass        |
      | CreateUser | 1      | CreateUser  |
      | SeeUser    | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    And I am on the homepage
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | field_event_date         | +8 days                |
      | status                   | 1                      |
      | field_content_visibility | public                 |

    Given I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | public                 |

    When I wait for the queue to be empty
    And I go to "user"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    Given I am logged in as "SeeUser"
    And I click "CreateUser"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    When I am on the homepage
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    Given I am an anonymous user
    When I am on the homepage
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

  @community
  Scenario: See community topic and event
    Given users:
      | name        | status | pass        |
      | CreateUser  | 1      | CreateUser  |
      | SeeUser     | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    And I am on the homepage
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | field_event_date         | +8 days                |
      | status                   | 1                      |
      | field_content_visibility | community              |

    And I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | community              |

    When I wait for the queue to be empty
    And I go to "user"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    Given I am logged in as "SeeUser"
    And I click "CreateUser"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    When I am on the homepage
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    Given I am an anonymous user
    When I am on the homepage
    Then I should not see "CreateUser created an event"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic"
    And I should not see "My Behat Topic created"
    When I go to "explore"
    Then I should not see "CreateUser created an event"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic"
    And I should not see "My Behat Topic created"

    @group
  Scenario: See community event in a group
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
      When I am on "user"
    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"

    Given I am logged in as "SeeUser"
    And I click "CreateUser"
    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"
    When I am on the homepage
    Then I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"
    When I go to "explore"
    Then I should not see "CreateUser created an event"
    And I should not see "Test group event"

    Given I am an anonymous user
    When I am on the homepage
    Then I should not see "CreateUser created an event in Test open group"
    And I should not see "Test group event"
    When I go to "explore"
    Then I should not see "CreateUser created an event in Test open group"
    And I should not see "Test group event"
