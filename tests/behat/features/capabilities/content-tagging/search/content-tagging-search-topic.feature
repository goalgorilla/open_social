@api @javascript
Feature: Filtering search with content tags for topics
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |
      | Shoes     | Clothing |
    And I enable the module social_topic
    And topics with non-anonymous author:
      | title                  | body      | field_content_visibility | field_topic_type | field_social_tagging |
      | Topic with target tag  | Some body | public                   | news             | Pants                |
      | Topic with another tag | Some body | public                   | news             | Shoes                |
      | Topic without a tag    | Some body | public                   | news             |                      |
    And Search indexes are up to date

  Scenario: Find a topic by a parent category of a tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should see "Shoes" in the Filters
    And I should see "Topic with target tag"
    And I should see "Topic with another tag"
    And I should not see "Topic without a tag"

  Scenario: Find a topic by its specific tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I should not see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I check the box Clothing in the Filters
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I should see "Topic with target tag"
    And I should not see "Topic with another tag"
    And I should not see "Topic without a tag"

  Scenario: Find all topics without a tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I check the box "<No Content Tags>"
    And I press "Filter"

    Then I should see "<No Content Tags>" in the Filters
    And I should not see "Clothing" in the Filters
    And I should not see "Pants" in the Filters
    And I should see "Topic without a tag"
    And I should not see "Topic with target tag"
    And I should not see "Topic with another tag"
