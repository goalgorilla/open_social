@api @event @eventenrollment @javascript @stability @perfect @GPI-10 @profile @stability-2 @enrollment-manage
Feature: Manage event enrollment
  Benefit: In order to attend an Event
  Role: As a Verified
  Goal/desire: I want to manage event enrollment

  @verified
  Scenario: Successfully manage enrollment
    Given I enable the module "social_event_managers"
    And users:
      | name            | pass            | mail                        | status | roles        |
      | event_creator   | event_creator   | event_creator@example.com   | 1      | sitemanager  |
      | event_organiser | event_organiser | event_organiser@example.com | 1      | verified     |
      | event_enrollee  | event_enrollee  | event_enrollee@example.com  | 1      | verified     |
    When I am logged in as "event_creator"
    And I am on "user"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title                                  | My Behat Event                         |
      | edit-field-event-date-0-value-date     | 2025-01-01                             |
      | edit-field-event-date-end-0-value-date | 2025-01-01                             |
      | edit-field-event-date-0-value-time     | 11:00:00                               |
      | edit-field-event-date-end-0-value-time | 11:00:00                               |
      | field_content_visibility               | community                              |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click the xth "0" element with the css "#edit-group-settings summary"
    And I set alias as "mybehatevent"
    And I press "Create event"

    When I am logged in as "event_organiser"
    And I am on "mybehatevent"
    Then I should not see the link "Manage enrollments"

    When I am logged in as "event_creator"
    And I am on "mybehatevent"
    And I click "Edit content"
    And I click the xth "0" element with the css "#attachments summary"
    And I fill in "event_organiser" for "field_event_managers[0][target_id]"
    And I press "Save"
    And I am logged in as "event_organiser"
    And I am on "mybehatevent"
    Then I should see the link "Manage enrollments"

    When I click "Manage enrollments"
    Then I should see the text "0 Enrollees"
    And I should see the text "No one has enrolled for this event"

    When I am logged in as "event_enrollee"
    And I am on "mybehatevent"
    And I press "Enroll"
    And I am logged in as "event_organiser"
    And I am on "mybehatevent"
    And I click "Manage enrollments"
    Then I should see the text "1 Enrollees"
    And I should see the link "Enrollee"
    And I should see the link "Organization"
    And I should see the link "Enroll date"
    And I should see the text "Operation"
    And I should see the link "event_enrollee"

    # as EO we should also get a notification about this enrollment.Ability:
    When I am logged in as "event_organiser"
    And I wait for the queue to be empty
    And I am at "notifications"
    Then I should see text matching "event_enrollee has enrolled to the event My Behat Event you are organizing"
