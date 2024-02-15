@api @event @stability @javascript @stability-1 @event-all-day-timezone
Feature: All day event for different timezones
  Benefit: Correct handling of non GMT timezones
  Role: As a Verified
  Goal/desire: I want to create all day events

  @verified @perfect @critical
  Scenario: Successfully create an all day event in GMT - 8
    # @todo This test relies on the old layout.
    Given the theme is set to old
    Given I set the configuration item "system.date" with key "date_default_timezone" to "America/Los_Angeles"
    And I am logged in as an "verified"
    And I am on "/node/add/event"
    When I fill in the custom fields for this "event"
    And I fill in the following:
      | Title                                  | This is a timezone test for all day events |
      | edit-field-event-date-0-value-date     | 2025-01-01                                 |
      | edit-field-event-date-end-0-value-date | 2025-01-01                                 |
      | edit-field-event-date-0-value-time     | 11:00:00                                   |
      | edit-field-event-date-end-0-value-time | 11:00:00                                   |
      | Location name                          | Technopark                                 |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I select "UA" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
      | City           | Lviv           |
      | Street address | Fedkovycha 60a |
      | Postal code    | 79000          |
    And I select "Lviv oblast" from "Region"
    And I press "Create event"
    Then I should see "This is a timezone test for all day events has been created."
    And I should see "1 Jan '25"
    And I should not see "31 Dec '24"

    Given I click "Edit content"
    When I fill in the following:
      | Title | This is a test event - edit |
    And I show hidden checkboxes
    And I check the box "edit-field-event-all-day-value"
    And I press "Save"
    Then I should see "Event This is a test event - edit has been updated"
    And I should see "1 Jan '25"
    And I should see "THIS IS A TEST EVENT - EDIT"
    And I should not see "11:00"
    And I should not see "31 Dec '24"

  Scenario: Successfully create an all day event with two event days in GMT - 8
    Given events with non-anonymous author:
      | title                   | body                   | field_event_all_day | field_event_date | field_event_date_end | field_content_visibility |
      | Test for all day events | Body description text. | 1                   | 2035-01-01       | 2035-01-02           | public                   |

    And I am logged in as a user with the verified role
    When I am viewing the event "Test for all day events"
    Then I should see "1 January 2035 - 2 January 2035"

    # Check all events
    When I am on "community-events"
    Then I should see "Test for all day events"
    And I should see "1 Jan '35 - 2 Jan '35"

