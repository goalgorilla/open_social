@api @javascript
Feature: Filtering search with content tags for events
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |
      | Shoes     | Clothing |
    And I enable the module social_event
    And events with non-anonymous author:
      | title                  | body      | field_content_visibility | field_event_date | field_social_tagging |
      | Event with target tag  | Some body | public                   | +1 day           | Pants                |
      | Event with another tag | Some body | public                   | +1 day           | Shoes                |
      | Event without a tag    | Some body | public                   | +1 day           |                      |
    And Search indexes are up to date

  Scenario: Find an event by a parent category of a tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should see "Shoes" in the Filters
    And I should see "Event with target tag"
    And I should see "Event with another tag"
    And I should not see "Event without a tag"

  Scenario: Find an event by its specific tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I should not see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I should see "Event with target tag"
    And I should not see "Event with another tag"
    And I should not see "Event without a tag"

  Scenario: Find all events without a tag
    Given I am logged in as a user with the verified role

    When I search content for ""
    And I check the box "<No Content Tags>"
    And I press "Filter"

    Then I should see "<No Content Tags>" in the Filters
    And I should not see "Clothing" in the Filters
    And I should not see "Pants" in the Filters
    And I should see "Event without a tag"
    And I should not see "Event with target tag"
    And I should not see "Event with another tag"
