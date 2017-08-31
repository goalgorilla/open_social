@api @event @stability @overview @DS-421 @stability-2
Feature: Overview
  Benefit: In order to learn details over an Event
  Role: As a User
  Goal/desire: I want to see an Event overview

  @LU @perfect @critical
  Scenario: Successfully see the event overview
    Given I am logged in as an "authenticated user"
    And I am on "user"
    When I click "Events"
    Then I should see "Events" in the "Page title block"
    And I should see "FILTER" in the "Sidebar second"
    And I should see "Upcoming events"
    And I should see "Events that have started or are finished"
    And I should see text matching "Publish status"

    # Scenario: Successfully see the topic overview of another user
    Given I am on "user/1"
    When I click "Events"
    Then I should see "Events" in the "Page title block"
    And I should see "FILTER" in the "Sidebar second"
    And I should not see text matching "Publish status"

    #@TODO make a scenario for filters to work.
