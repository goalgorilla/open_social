@api @javascript
Feature: Filtering search with content tags for users
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |
      | Shoes     | Clothing |
    And users:
      | name             | status | roles    |
      | with_target_tag  | 1      | verified |
      | with_another_tag | 1      | verified |
      | without_tag      | 1      | verified |
    And user with_target_tag has a profile filled with:
      | field_social_tagging | Pants |
    And user with_another_tag has a profile filled with:
      | field_social_tagging | Shoes |
    And Search indexes are up to date

  Scenario: Find a user by a parent category of a tag
    Given I am logged in as a user with the verified role

    When I search users for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should see "Shoes" in the Filters
    And I should see "with_target_tag"
    And I should see "with_another_tag"
    And I should not see "without_tag"

  Scenario: Find a user by its specific tag
    Given I am logged in as a user with the verified role

    When I search users for ""
    And I should not see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Clothing" in the Filters
    And I should see "Pants" in the Filters
    And I should not see "Shoes" in the Filters
    And I should see "with_target_tag"
    And I should not see "with_another_tag"
    And I should not see "without_tag"

  Scenario: Find all users without a tag
    Given I am logged in as a user with the verified role

    When I search users for ""
    And I check the box "<No Content Tags>"
    And I press "Filter"

    Then I should see "<No Content Tags>" in the Filters
    And I should not see "Clothing" in the Filters
    And I should not see "Pants" in the Filters
    And I should see "without_tag"
    And I should not see "with_target_tag"
    And I should not see "with_another_tag"
