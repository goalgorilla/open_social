@api @event @stability @overview @DS-421
Feature: Overview
  Benefit: In order to learn details over an Event
  Role: As a User
  Goal/desire: I want to see an Event overview

  @LU @perfect @critical @wip
  Scenario: Successfully see the event overview
    Given I am logged in as an "authenticated user"
    And I am on "user"
    When I click "Events"
    Then I should see the heading "Events"
    And I should see the heading "Events filter" in the "Sidebar second"
    And I should see "What events do you want to see?"
    And I should see "Upcoming events"
    And I should see "Events that have started or are finished"
    And I should see text matching "Publish status"

    # Scenario: Successfully see the topic overview of another user
    Given I am on "user/1"
    When I click "Events"
    Then I should see the heading "Events"
    And I should see the heading "Events filter" in the "Sidebar second"
    And I should not see text matching "Publish status"

    #@TODO make a scenario for filters to work.
