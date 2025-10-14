@api @javascript
Feature: Create/update online events
  Benefit: In order to host virtual events
  Role: As a Verified
  Goal/desire: I want to create and manage online events

  Scenario: Online checkbox is visible when creating an event
    Given I am logged in as an "verified"

    When I am on "node/add/event"

    And I fill in the following:
      | Title                                  | Online Event             |
      | edit-field-event-date-0-value-date     | 2025-01-01               |
      | edit-field-event-date-end-0-value-date | 2025-01-01               |
      | edit-field-event-date-0-value-time     | 11:00:00                 |
      | edit-field-event-date-end-0-value-time | 12:00:00                 |
      | Location name                          | Online Meeting           |

    And I select "NL" from "Country"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is an online event."

    # Make sure the "Online" checkbox is visible.
    Then I should see "Online"
    And I should not see "Join link"

    # Check the "BigBlueButton" and "Join link" options visibility.
    And I check the box "Online"
    And I wait for AJAX to finish
    And I should see "Join link"
    And I uncheck the box "Online"
    And I wait for AJAX to finish
    And I should not see "Join link"

    # Create event with Join link meeting.
    And I check the box "Online"
    And I wait for AJAX to finish
    And I click the element with css selector ".meeting-type-wrapper .meeting-type-label"
    And I wait for AJAX to finish
    And I fill in "Meeting URL" with "https://example.com/meeting/12345"
    And I press "Create event"
    And I should see "Online Event has been created."

    # Make sure the default meeting value is correctly loaded.
    And I click "Edit content"
    And I should see "Meeting URL"

    # Unchecking "Online" checkbox clears URL when saving.
    And I uncheck the box "Online"
    And I wait for AJAX to finish
    And I press "Save"
    And I should see "Online Event has been updated"
    And I click "Edit content"
    And I should not see "Join link"
