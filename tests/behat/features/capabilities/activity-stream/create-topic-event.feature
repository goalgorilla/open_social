@api
Feature: See and get notified when content is created
  Benefit: So I can discover new content on the platform
  Role: As a Verified
  Goal/desire: I want see and get notified when content is created

  @public
  Scenario: See public topic and event
    Given users:
      | name       | status | pass        | roles    |
      | CreateUser | 1      | CreateUser  | verified |
      | SeeUser    | 1      | SeeUser     | verified |
    And I am logged in as "CreateUser"
    And I am on the homepage
    And events authored by current user:
      | title                  | body                  | field_event_date | status | field_content_visibility |
      | My Behat Event created | Body description text | +8 days          | 1      | public                   |
    And I am viewing the event "My Behat Event created"

    And topics authored by current user:
      | title                  | body                  | field_content_visibility | field_topic_type | status |
      | My Behat Topic created | Body description text | public                   | News             | 1      |
    And I am viewing the topic "My Behat Topic created"

    When I wait for the queue to be empty
    And I go to "user"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    And I am logged in as "SeeUser"
    And I click "CreateUser"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    And I am on the homepage
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    And I go to "explore"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    And I am an anonymous user
    And I am on the homepage
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    And I go to "explore"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

  @community
  Scenario: See community topic and event
    Given users:
      | name        | status | pass        | roles    |
      | CreateUser  | 1      | CreateUser  | verified |
      | SeeUser     | 1      | SeeUser     | verified |
    And I am logged in as "CreateUser"
    And I am on the homepage
    And events authored by current user:
      | title                  | body                  | field_event_date | status | field_content_visibility |
      | My Behat Event created | Body description text | +8 days          | 1      | community                |
    And I am viewing the event "My Behat Event created"

    And topics authored by current user:
      | title                  | body                  | field_content_visibility | field_topic_type | status |
      | My Behat Topic created | Body description text | community                | News             | 1      |
    And I am viewing the topic "My Behat Topic created"

    When I wait for the queue to be empty
    And I go to "user"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    And I am logged in as "SeeUser"
    And I click "CreateUser"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    And I am on the homepage
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"
    And I go to "explore"
    And I should see "CreateUser created an event"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic"
    And I should see "My Behat Topic created"

    And I am an anonymous user
    And I am on the homepage
    And I should not see "CreateUser created an event"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic"
    And I should not see "My Behat Topic created"
    And I go to "explore"
    And I should not see "CreateUser created an event"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic"
    And I should not see "My Behat Topic created"

  @group
  Scenario: See community event in a group
    Given users:
      | name        | status | pass        | roles    |
      | CreateUser  | 1      | CreateUser  | verified |
      | SeeUser     | 1      | SeeUser     | verified |
    And I am logged in as "CreateUser"
    And I am on "group/add/flexible_group"

    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I click radio button "Public"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Test open group" in the "Hero block"

    And I click "Events"
    And I click "Create Event"
    And I fill in the following:
      | Title                                  | Test group event |
      | edit-field-event-date-0-value-date     | 2025-01-01       |
      | edit-field-event-date-end-0-value-date | 2025-01-01       |
      | edit-field-event-date-0-value-time     | 11:00:00         |
      | edit-field-event-date-end-0-value-time | 11:00:00         |
      | Location name                          | GG HQ            |
    And I select "NL" from "Country"
    And I wait for AJAX to finish
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Create event"
    And I should see "Test group event"
    And I click "Test open group"
    And I wait for the queue to be empty
    And I am on "user"
    And I should see "CreateUser created an event in Test open group"
    And I should see "Test group event"

    And I am logged in as "SeeUser"
    And I am on the profile of "CreateUser"
    And I should see "CreateUser created an event in Test open group"
    And I am on the homepage
    And I should not see "CreateUser created an event in Test open group"
    And I go to "explore"
    And I should see "CreateUser created an event"
    And I should see "Test group event"

    And I am an anonymous user
    And I go to "explore"
    And I should not see "CreateUser created an event in Test open group"
    And I should not see "Test group event"
