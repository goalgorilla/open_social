@api
Feature: Overview
  Benefit: In order to learn details over an Event
  Role: As a Verified
  Goal/desire: I want to see an Event overview

  @verified @perfect @critical
  Scenario: Successfully see the event overview
    Given users:
      | name     | mail               | status | roles    |
      | User One | user_1@example.com | 1      | verified |
      | User Two | user_2@example.com | 1      | verified |

    And I am logged in as "User One"
    And I am on "/user"
    And I click "Events"
    And I should see "Filter" in the "Sidebar second"
    And I should see "Ongoing and upcoming events"
    And I should see "Past events"
    And I should see text matching "Publish status"

    # Scenario: Successfully see the topic overview of another user
    And I am on the profile of "User Two"

    When I click "Events"

    Then I should see "Filter" in the "Sidebar second"
    And I should not see text matching "Publish status"

    #@TODO make a scenario for filters to work.
