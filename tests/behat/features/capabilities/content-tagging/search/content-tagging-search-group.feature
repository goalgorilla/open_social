@api @javascript
Feature: Filtering search with content tags for groups
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |
      | Shoes     | Clothing |
    And I enable the module social_group_flexible_group
    And groups with non-anonymous owner:
      | label                  | type           | field_flexible_group_visibility | field_social_tagging |
      | Group with target tag  | flexible_group | public                          | Pants                |
      | Group with another tag | flexible_group | public                          | Shoes                |
      | Group without a tag    | flexible_group | public                          |                      |
    And Search indexes are up to date

  Scenario: Find a group by a parent category of a tag
    Given I am logged in as a user with the verified role

    When I search groups for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should see "Shoes" in the Filters
    And I should see "Group with target tag"
    And I should see "Group with another tag"
    And I should not see "Group without a tag"

  Scenario: Find a group by its specific tag
    Given I am logged in as a user with the verified role

    When I search groups for ""
    And I should not see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I should see "Group with target tag"
    And I should not see "Group with another tag"
    And I should not see "Group without a tag"

  Scenario: Find all groups without a tag
    Given I am logged in as a user with the verified role

    When I search groups for ""
    And I check the box "<No Content Tags>"
    And I press "Filter"

    Then I should see "<No Content Tags>" in the Filters
    And I should not see "Clothing" in the Filters
    And I should not see "Pants" in the Filters
    And I should see "Group without a tag"
    And I should not see "Group with target tag"
    And I should not see "Group with another tag"
