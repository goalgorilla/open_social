@api @event @eventenrollment @javascript @stability @perfect @GPI-10 @profile @stability-2 @enrollment-manage
Feature: Manage event enrollment
  Benefit: In order to attend an Event
  Role: As a Verified
  Goal/desire: I want to manage event enrollment

  Background:
    Given I enable the module "social_event_managers"

  Scenario: Can't manage an event by default
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the verified role

    When I am viewing the event "Test content"

    Then I should not see the link "Manage enrollments"

  Scenario: Can add an event manager
    # @todo The order matters here because we don't have to be explicit about
    # authors yet, we have solved this for groups but not yet for topics/events.
    Given I am logged in as a user with the verified role
    And events authored by current user:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And users:
      | name     | pass            | mail                        | status | roles        |
      | Jane Doe | event_organiser | event_organiser@example.com | 1      | verified     |

    When I am editing the event "Test content"
    And I expand the "Additional information" section
    # @todo: The fact that we can't do this through a label shows a potential
    #  accessibility issue.
    And I fill in "Jane Doe" for "field_event_managers[0][target_id]"
    And I press "Save"

    Then I should be viewing the event "Test content"
    And I should see "Jane Doe" in the "Organisers" block

  Scenario: Event manager can see the manage enrollments link on events
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the verified role
    And I am an event manager for the "Test content" event

    When I am viewing the event "Test content"

    Then I should see the link "Manage enrollments"

  Scenario: Event manager can see the empty state when there are no enrollments
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the verified role
    And I am an event manager for the "Test content" event

    When I am viewing the event manager page for "Test content"

    Then I should see the text "0 Enrollees"
    And I should see the text "No one has enrolled for this event"

  Scenario: Event manager can see the event enrollments when there are enrollments
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And there are 2 event enrollments for the "Test content" event
    And I am logged in as a user with the verified role
    And I am an event manager for the "Test content" event

    When I am viewing the event manager page for "Test content"

    Then I should see the text "2 Enrollees"
    And I should see the link "Enrollee"
    And I should see the link "Organization"
    And I should see the link "Enroll date"
    And I should see the text "Operation"

  Scenario: Event manager gets a notification for an event enrollment
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the verified role
    And I am an event manager for the "Test content" event

    When there is 1 event enrollment for the "Test content" event
    And I wait for the queue to be empty
    And I am at "notifications"

    Then I should see text matching "has enrolled to the event Test content you are organizing"
