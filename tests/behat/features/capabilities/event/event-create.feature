@api @event @stability @javascript @DS-406
Feature: Create Event
  Benefit: In order to connect with other people offline
  Role: As a LU
  Goal/desire: I want to create Events

  @LU @perfect @critical
  Scenario: Successfully create event
    Given I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Events"
    And I click "Create Event"
    When I fill in the following:
         | Title | This is a test event |
         | Date | 2025-01-01 |
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
    And I press "Save"
    Then I should see "This is a test event has been created."
    And I should see "THIS IS A TEST EVENT"
    And I should see "Technopark" in the "Main content"
    And I should see "Body description text" in the "Main content"
    And I should see "Fedkovycha 60a" in the "Main content"
    And I should see "79000" in the "Main content"
    And I should see "Lviv" in the "Main content"
    And I should see "Lviv oblast" in the "Main content"

    # Quick edit
    Given I click "Edit content"
    Then I should not see "Enrollments" in the "Hero block"
    When I fill in the following:
      | Title | This is a test event - edit |
    And I press "Save and keep published"
    Then I should see "Event This is a test event - edit has been updated"
    And I should see "THIS IS A TEST EVENT - EDIT"
