@api @event @stability @overview @DS-421
Feature: Overview
  Benefit: In order to learn details over an Event
  Role: As a User
  Goal/desire: I want to see an Event overview

  @LU @perfect @critical
  Scenario: Successfully see the event overview
    Given users:
      | name       | pass       | mail                   | status |
      | eoverview1 | eoverview1 | eoverview1@example.com | 1      |
      | eoverview2 | eoverview2 | eoverview2@example.com | 1      |
    And I am logged in as "eoverview1"
    And I am on "user"
    When I click "Events"
    Then I should see "Events" in the "Page title block"
    And I should see "FILTER" in the "Sidebar second"
    And I should see "Upcoming events"
    And I should see "Events that have started or are finished"
    And I should see text matching "Publish status"

    # Scenario: Successfully see the topic overview of another user
    Given I am logged in as "eoverview2"
      And I am on "all-members"
     Then I should see "eoverview1"
     When I click "eoverview1"
      And I click "Events"
     Then I should see "Events" in the "Page title block"
      And I should see "FILTER" in the "Sidebar second"
      And I should not see text matching "Publish status"

    #@TODO make a scenario for filters to work.
