@api @topic @stability @overview @DS-357 @DS-358 @stability-2 @topic-overview-filter
Feature: Topic Overview Filter
  Benefit: In order to find a Topic
  Role: As a User
  Goal/desire: I want to filter the Topic overview

  @perfect @critical
  Scenario: Successfully filter the topic overview
    Given "topic_types" terms:
      | name                  |
      | Official company news |
    And I am on "/all-topics"
    Then I should see "All topics"
    And I select "Official company news" from "is the type of"
    And I press the "Apply" button
    And I should see "Topics of type Official company news"
