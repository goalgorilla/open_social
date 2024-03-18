@api @topic @stability @overview @DS-357 @DS-358 @stability-2 @topic-overview
Feature: Topic Overview
  Benefit: In order to find a Topic from a author
  Role: As a Verified
  Goal/desire: I want to see an Topic overview

  @perfect @critical
  Scenario: Successfully see the topic overview
    Given users:
      | name       | pass       | mail                   | status | roles    |
      | toverview1 | toverview1 | toverview1@example.com | 1      | verified |
      | toverview2 | toverview2 | toverview2@example.com | 1      | verified |
    And I am logged in as "toverview1"
    And I am on "user"
    When I click "Topics"
    Then I should see "Filter" in the "Sidebar second"
    And I should see text matching "is the type of"
    And I should see text matching "has the publish status of"

    # Scenario: Successfully see the topic overview of another user
    Given I am logged in as "toverview2"
    And I am on "all-members"
    Then I should see "toverview1"
    When I click "toverview1"
    And I click "Topics"
    Then I should see "Filter" in the "Sidebar second"
    And I should not see text matching "has the publish status of"
