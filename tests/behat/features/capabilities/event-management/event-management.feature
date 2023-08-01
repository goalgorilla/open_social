@api @event-management @stability @javascript @DS-1258 @stability-2
Feature: Event Management
  Benefit: In order to organise an event
  Role: As a Verified
  Goal/desire: I want to assign event organiser

  Background:
    Given I enable the module "social_language"
    And I enable the module "social_content_translation"

  @verified @perfect @critical
  Scenario: Successfully assign event organiser
    Given I enable the module "social_event_managers"
    And users:
      | name              | mail             | field_profile_organization | status | roles    |
      | event_organiser_1 | eo_1@example.com | GoalGorilla                | 1      | verified |
      | event_organiser_2 | eo_2@example.com | Drupal                     | 1      | verified |
    And groups:
      | label                                    | field_group_description | author            | type        | langcode |
      | Springfield local business collaboration | Description text        | event_organiser_1 | open_group  | en       |
    And I am logged in as an "verified"
    And I am on "user"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title                                  | This is an event with event organisers |
      | edit-field-event-date-0-value-date     | 2025-01-01                             |
      | edit-field-event-date-end-0-value-date | 2025-01-01                             |
      | edit-field-event-date-0-value-time     | 11:00:00                               |
      | edit-field-event-date-end-0-value-time | 11:00:00                               |
      | Location name                          | GG HQ                                  |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click the xth "0" element with the css "#attachments summary"
    And I fill in "event_organiser_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_organiser_2" for "field_event_managers[1][target_id]"
    And I press "Create event"
    Then I should see "This is an event with event organisers has been created."
    And I should see "THIS IS AN EVENT WITH EVENT ORGANISERS"
    And I should see "Body description text" in the "Main content"
    And I should see "Organisers"
    And I should not see the link "All Organisers"

    # Create event in group.
    Given I am on "all-groups"
    And I click "Springfield local business collaboration"
    And I click "Join"
    And I press "Join group"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title                                  | This is an event with event organisers in group |
      | edit-field-event-date-0-value-date     | 2025-01-01                                      |
      | edit-field-event-date-end-0-value-date | 2025-01-01                                      |
      | edit-field-event-date-0-value-time     | 11:00:00                                        |
      | edit-field-event-date-end-0-value-time | 11:00:00                                        |
      | Location name                          | GG HQ                                           |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click the xth "0" element with the css "#attachments summary"
    And I fill in "event_organiser_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_organiser_2" for "field_event_managers[1][target_id]"
    And I press "Create event"
    And I should see "This is an event with event organisers in group"

    # Now test with event_organiser_1
    Given I logout
    And I am logged in as "event_organiser_1"
    And I open the "event" node with title "This is an event with event organisers"
    And I click "Edit content"
    Then I should see "Save"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event organisers in group"
    And I click "Edit content"
    Then I should see "Save"
    And I should not see "Authoring information"

    # Now test with event_organiser_2
    Given I logout
    And I am logged in as "event_organiser_2"
    And I open the "event" node with title "This is an event with event organisers"
    And I click "Edit content"
    Then I should see "Save"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event organisers in group"
    And I click "Edit content"
    Then I should see "Save"
    And I should not see "Authoring information"

    # Regression test for topic
    Given "topic" content:
      | title                   | body          |
      | Topic regression test   | Description   |
    And I open the "topic" node with title "Topic regression test"
    Then I should not see "Organisers"


  Scenario: Ensure, that if we have several translations of the event, the enrollees and organisers are not duplicated
   # Add Dutch language.
    Given I am logged in as an "administrator"
    And I turn off translations import
    And I am on "/admin/config/regional/language"
    And I click the xth "0" element with the css ".local-actions .button--action"
    And I select "Dutch" from "Language name"
    And I press "Add language"
    And I wait for AJAX to finish

    Given I am viewing my event:
      | title                    | My awesome event |
      | body                     | Body text        |
      | field_event_date         | +7 days          |
      | field_event_date_end     | +7 days          |
      | status                   | 1                |
      | field_content_visibility | public           |

    And users:
      | name         | pass            | mail                        | status | roles        |
      | Ryan Gosling | event_organiser | event_organiser@example.com | 1      | verified     |

    When I am editing the event "My awesome event"
    And I expand the "Additional information" section
    And I fill in "Ryan Gosling" for "field_event_managers[0][target_id]"
    And I press "Save"

    # Enroll for event
    When I press the "Enroll" button
    And I wait for AJAX to finish
    Then I should see the text "Meetup: My awesome event" in the "Modal"
    And I press the "Close" button

    # Add translation for this event.
    And I should see "Translate"
    When I click "Translate"
    Then I should see "Dutch"
    And I should see "Not translated"
    And I should see "Add"
    And I click "Add"
    And I press "Create event (this translation)"

    # Check if enrollees aren't duplicated.
    And I should see "1 people have enrolled"
    When I click "Manage enrollments"
    Then I should see "1 Enrollees"
    # Check if organisers aren't duplicated.
    When I click "Organisers"
    Then I should see "Ryan Gosling" exactly "1" times

