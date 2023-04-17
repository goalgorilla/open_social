@api @javascript
Feature: Filtering search with content tags
  The content tags provide a way to filter search results.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |

  Scenario: Find a group
    Given groups with non-anonymous owner:
      | label               | type           | field_flexible_group_visibility | field_social_tagging |
      | Group with a tag    | flexible_group | public                          | Pants                |
      | Group without a tag | flexible_group | public                          | Pants                |
    And I am logged in as a user with the verified role

    When I search groups for ""
    And I select the radio button Pants
    And I press "Filter"

    Then I should see "Group with a tag"
    And I should not see "Group without a tag"

#  Scenario: Find a topic
#    Given I enable the module social_topic
#    And topics with non-anonymous author:
#      | title               | body                     | field_content_visibility | field_topic_type | field_social_tagging |
#      | My piece of content | Wear pants to the office | public                   | news             | Pants                |
#    And I am logged in as a user with the verified role
#
#    When I am viewing the topic "My piece of content"
#
#    Then I should see "Tags"
#    And I should see "Pants"
#
#  Scenario: Find an event
#    Given I enable the module social_event
#    And events with non-anonymous author:
#      | title               | body                     | field_content_visibility | field_event_date | field_social_tagging |
#      | My piece of content | Wear pants to the office | public                   | +1 days          | Pants                |
#    And I am logged in as a user with the verified role
#
#    When I am viewing the event "My piece of content"
#
#    Then I should see "Tags"
#    And I should see "Pants"

  # @todo Add coverage for profiles.
