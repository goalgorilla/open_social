@api @topic @stability @overview @DS-357 @DS-358 @stability-2
Feature: Topic Overview
  Benefit: In order to find a Topic from a author
  Role: As a User
  Goal/desire: I want to see an Topic overview

  @perfect @critical
  Scenario: Successfully see the topic overview
    Given users:
      | name       | pass       | mail                   | status |
      | toverview1 | toverview1 | toverview1@example.com | 1      |
      | toverview2 | toverview2 | toverview2@example.com | 1      |
    And I am logged in as "toverview1"
    And I am on "user"
    When I click "Topics"
    Then I should see "FILTER" in the "Sidebar second"
    And I should see text matching "is the type of"
    And I should see text matching "has the publish status of"

  # Scenario: Successfully see the topic overview of another user
    Given I am logged in as "toverview2"
    And I am on "all-members"
    Then I should see "toverview1"
    When I click "toverview1"
    And I click "Topics"
    Then I should see "FILTER" in the "Sidebar second"
    And I should not see text matching "has the publish status of"

#  Scenario: Successfully filter the topic overview
    Given I am logged in as an "authenticated user"
    And I am on "/all-topics"
    Then I should see "All topics"
    And I click the xth "0" element with the css ".form-select"
    And I click "News"
    Then I press the "Apply" button
    And I should see "Topics of type News"
