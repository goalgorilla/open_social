@api @event @stability @overview @DS-421 @stability-2
Feature: Overview
  Benefit: In order to learn details over an Event
  Role: As a User
  Goal/desire: I want to see an Event overview

  @LU @perfect @critical
  Scenario: Successfully see the event overview
    Given users:
      | name     | mail               | status |
      | User One | user_1@example.com | 1      |
      | User Two | user_2@example.com | 1      |
    When I am logged in as "User One"
    And I am on "/user"
    And I click "Events"
    And I should see "FILTER" in the "Sidebar second"
    And I should see "Upcoming events"
    And I should see "Events that have started or are finished"
    And I should see text matching "Publish status"

    # Scenario: Successfully see the topic overview of another user
    Given I am on the profile of "User Two"
    When I click "Events"
    Then I should see "FILTER" in the "Sidebar second"
    And I should not see text matching "Publish status"

    #@TODO make a scenario for filters to work.
