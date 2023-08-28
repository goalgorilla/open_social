@api @javascript
Feature: Filtering search with content tags
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |
      | Shoes     | Clothing |

  Scenario: Find a group by a parent category of a tag
    Given I enable the module social_group_flexible_group
    And groups with non-anonymous owner:
      | label                  | type           | field_flexible_group_visibility | field_social_tagging |
      | Group with target tag  | flexible_group | public                          | Pants                |
      | Group with another tag | flexible_group | public                          | Shoes                |
      | Group without a tag    | flexible_group | public                          |                      |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search groups for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Group with target tag"
    And I should see "Group with another tag"
    And I should not see "Group without a tag"

  Scenario: Find a group by its specific tag
    Given I enable the module social_group_flexible_group
    And groups with non-anonymous owner:
      | label                  | type           | field_flexible_group_visibility | field_social_tagging |
      | Group with target tag  | flexible_group | public                          | Pants                |
      | Group with another tag | flexible_group | public                          | Shoes                |
      | Group without a tag    | flexible_group | public                          |                      |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search groups for ""
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Group with target tag"
    And I should not see "Group with another tag"
    And I should not see "Group without a tag"

  Scenario: Find a topic by a parent category of a tag
    Given I enable the module social_topic
    And topics with non-anonymous author:
      | title                  | body      | field_content_visibility | field_topic_type | field_social_tagging |
      | Topic with target tag  | Some body | public                   | news             | Pants                |
      | Topic with another tag | Some body | public                   | news             | Shoes                |
      | Topic without a tag    | Some body | public                   | news             |                      |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Topic with target tag"
    And I should see "Topic with another tag"
    And I should not see "Topic without a tag"

  Scenario: Find a topic by its specific tag
    Given I enable the module social_topic
    And topics with non-anonymous author:
      | title                  | body      | field_content_visibility | field_topic_type | field_social_tagging |
      | Topic with target tag  | Some body | public                   | news             | Pants                |
      | Topic with another tag | Some body | public                   | news             | Shoes                |
      | Topic without a tag    | Some body | public                   | news             |                      |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Topic with target tag"
    And I should not see "Topic with another tag"
    And I should not see "Topic without a tag"

  Scenario: Find an event by a parent category of a tag
    Given I enable the module social_event
    And events with non-anonymous author:
      | title                  | body      | field_content_visibility | field_event_date | field_social_tagging |
      | Event with target tag  | Some body | public                   | +1 day           | Pants                |
      | Event with another tag | Some body | public                   | +1 day           | Shoes                |
      | Event without a tag    | Some body | public                   | +1 day           |                      |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "Event with target tag"
    And I should see "Event with another tag"
    And I should not see "Event without a tag"

  Scenario: Find an event by its specific tag
    Given I enable the module social_topic
    And events with non-anonymous author:
      | title                  | body      | field_content_visibility | field_event_date | field_social_tagging |
      | Event with target tag  | Some body | public                   | +1 day           | Pants                |
      | Event with another tag | Some body | public                   | +1 day           | Shoes                |
      | Event without a tag    | Some body | public                   | +1 day           |                      |

    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "Event with target tag"
    And I should not see "Event with another tag"
    And I should not see "Event without a tag"


  Scenario: Find a user by a parent category of a tag
    Given I enable the module social_group_flexible_group
    And users:
      | username         | status | roles    |
      | with_target_tag  | 1      | verified |
      | with_another_tag | 1      | verified |
      | without_tag      | 1      | verified |
    And user with_target_tag has a profile filled with:
      | field_social_tagging     | Pants |
    And user with_another_tag has a profile filled with:
      | field_social_tagging | Shoes |
    And user without_tag has a profile filled with:
      | field_social_tagging | Shoes |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search users for ""
    And I check the box Clothing
    And I press "Filter"

    Then I should see "with_target_tag"
    And I should see "with_another_tag"
    And I should not see "without_tag"

  Scenario: Find a user by its specific tag
    Given I enable the module social_group_flexible_group
    And users:
      | username         | status | roles    |
      | with_target_tag  | 1      | verified |
      | with_another_tag | 1      | verified |
      | without_tag      | 1      | verified |
    And user with_target_tag has a profile filled with:
      | field_social_tagging     | Pants |
    And user with_another_tag has a profile filled with:
      | field_social_tagging | Shoes |
    And user without_tag has a profile filled with:
      | field_social_tagging | Shoes |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search content for ""
    And I check the box Clothing
    And I press "Filter"
    And I check the box Pants
    And I press "Filter"

    Then I should see "with_target_tag"
    And I should not see "without_target_tag"
    And I should not see "without_tag"
