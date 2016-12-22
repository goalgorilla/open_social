@api @event-management @stability @javascript @DS-1258
Feature: Event Management
  Benefit: In order to organise an event
  Role: As a LU
  Goal/desire: I want to assign event managers

  @LU @perfect @critical
  Scenario: Successfully assign event managers
    Given I enable the module "social_event_managers"
    And I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title | This is an event with event managers |
      | Date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "susanwilliams" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "chrishall" for "field_event_managers[1][target_id]"
    And I press "Save"
    Then I should see "This is an event with event managers has been created."
    And I should see "THIS IS AN EVENT WITH EVENT MANAGERS"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Managers"
    And I should see "Event Managers"