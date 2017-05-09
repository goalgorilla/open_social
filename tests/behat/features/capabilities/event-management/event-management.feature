@api @event-management @stability @javascript @DS-1258
Feature: Event Management
  Benefit: In order to organise an event
  Role: As a LU
  Goal/desire: I want to assign event organiser

  @LU @perfect @critical
  Scenario: Successfully assign event organiser
    Given I enable the module "social_event_managers"
    And users:
      | name            | mail             | field_profile_organization | status |
      | event_organiser_1 | eo_1@example.com | GoalGorilla                | 1      |
      | event_organiser_2 | eo_2@example.com | Drupal                     | 1      |
    And I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
      | Title | This is an event with event organisers |
      | Date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "event_organiser_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_organiser_2" for "field_event_managers[1][target_id]"
    And I press "Save"
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
      | Title | This is an event with event organisers in group |
      | Date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I fill in "event_organiser_1" for "field_event_managers[0][target_id]"
    And I press "field_event_managers_add_more"
    And I wait for AJAX to finish
    And I fill in "event_organiser_2" for "field_event_managers[1][target_id]"
    And I press "Save and publish"
    And I should see "This is an event with event managers in group"

    # Now test with event_organiser_1
    Given I logout
    And I am logged in as "event_organiser_1"
    And I open the "event" node with title "This is an event with event organisers"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event organisers in group"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    # Now test with event_organiser_2
    Given I logout
    And I am logged in as "event_organiser_2"
    And I open the "event" node with title "This is an event with event organisers"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    Given I open the "event" node with title "This is an event with event organisers in group"
    And I click "Edit content"
    Then I should see "Save and keep published"
    And I should not see "Authoring information"

    # Regression test for topic
    Given "topic" content:
      | title                   | body          |
      | Topic regression test   | Description   |
    And I open the "topic" node with title "Topic regression test"
    Then I should not see "Organisers"
