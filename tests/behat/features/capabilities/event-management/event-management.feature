@api @event-management @stability @javascript @DS-1258
Feature: Event Management
  Benefit: In order to organise an event
  Role: As a LU
  Goal/desire: I want to assign event managers

  @LU @perfect @critical
  Scenario: Successfully assign event managers
    Given I enable the module "social_event_managers"
    And users:
      | name            | mail             | field_profile_organization | status |
      | event_manager_1 | em_1@example.com | GoalGorilla                | 1      |
      | event_manager_2 | em_2@example.com | Drupal                     | 1      |
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
    And I fill in "event_manager_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_manager_2" for "field_event_managers[1][target_id]"
    And I press "Save"
    Then I should see "This is an event with event managers has been created."
    And I should see "THIS IS AN EVENT WITH EVENT MANAGERS"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Managers"
    And I should see "Event Managers"

    # Create event in group.
    Given I am on "all-groups"
    And I click "Springfield local business collaboration"
    And I click "Join"
    And I press "Join group"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title | This is an event with event managers in group |
      | Date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "event_manager_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_manager_2" for "field_event_managers[1][target_id]"
    And I press "Continue to final step"
    And I press "Create node in group"
    And I should see "This is an event with event managers in group"

    # Now test with event_manager_1
    Given I logout
    And I am logged in as "event_manager_1"
    And I open the "event" node with title "This is an event with event managers"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event managers in group"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    # Now test with event_manager_2
    Given I logout
    And I am logged in as "event_manager_2"
    And I open the "event" node with title "This is an event with event managers"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event managers in group"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"
