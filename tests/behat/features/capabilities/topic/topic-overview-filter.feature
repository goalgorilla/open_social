@api @topic @stability @overview @DS-357 @DS-358 @stability-2 @topic-overview-filter
Feature: Topic Overview Filter
  Benefit: In order to find a Topic
  Role: As a Verified
  Goal/desire: I want to filter the Topic overview

  @perfect @critical
  Scenario: Successfully filter the topic overview
    Given "topic_types" terms:
      | name                  |
      | News                  |
    And I am logged in as an "verified"

    When I am on "/all-topics"

    Then I should see "All topics"

    When I select "News" from "is the type of"
    And I press the "Filter" button

    Then I should see "Topics of type News"
