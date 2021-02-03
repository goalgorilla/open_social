@api @event @stability @javascript @stability-1 @event-all-day-timezone
Feature: All day event for different timezones
  Benefit: Correct handling of non GMT timezones
  Role: As a LU
  Goal/desire: I want to create all day events

  @LU @perfect @critical
  Scenario: Successfully create an all day event in GMT - 8
    Given I set the configuration item "system.date" with key "date_default_timezone" to "America/Los_Angeles"
    And I am logged in as an "authenticated user"
    And I am on "/node/add/event"
    When I fill in the custom fields for this "event"
    And I fill in the following:
      | Title | This is a timezone test for all day events |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | Technopark |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I select "UA" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
      | City | Lviv |
      | Street address | Fedkovycha 60a |
      | Postal code | 79000 |
      | Oblast | Lviv oblast |
    And I press "Create event"
    Then I should see "This is a timezone test for all day events has been created."
    And I should see "1 Jan '25"
    And I should not see "31 Dec '24"

    Given I click "Edit content"
    When I fill in the following:
      | Title | This is a test event - edit |
    And I show hidden checkboxes
    And I check the box "edit-event-all-day"
    And I press "Save"
    Then I should see "Event This is a test event - edit has been updated"
    And I should see "1 Jan '25"
    And I should see "THIS IS A TEST EVENT - EDIT"
    And I should not see "11:00"
    And I should not see "31 Dec '24"