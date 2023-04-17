@api @javascript
Feature: Content Tagging
  Users are able to add tags to different data types (groups, content, profile)
  in a community so that different sub-sections of a community can be created.
  The tags that can be selected are managed by sitemanagers but any user that
  can create a data type can add the tags.

  Background:
    Given I enable the module social_tagging
    And social_tagging terms:
      | name      | parent   |
      | Clothing  |          |
      | Pants     | Clothing |

  Scenario: View a tag on a group
    Given groups with non-anonymous owner:
      | label            | type           | field_flexible_group_visibility | field_social_tagging |
      | Group with a tag | flexible_group | public                          | Pants                |
    And I am logged in as a user with the verified role

    When I am viewing the about page of group "Group with a tag"

    Then I should see "Tags"
    And I should see "Pants"

  Scenario: View a tag on a topic
    Given I enable the module social_topic
    And topics with non-anonymous author:
      | title               | body                     | field_content_visibility | field_topic_type | field_social_tagging |
      | My piece of content | Wear pants to the office | public                   | news             | Pants                |
    And I am logged in as a user with the verified role

    When I am viewing the topic "My piece of content"

    Then I should see "Tags"
    And I should see "Pants"

  Scenario: View a tag on an event
    Given I enable the module social_event
    And events with non-anonymous author:
      | title               | body                     | field_content_visibility | field_event_date | field_social_tagging |
      | My piece of content | Wear pants to the office | public                   | +1 days          | Pants                |
    And I am logged in as a user with the verified role

    When I am viewing the event "My piece of content"

    Then I should see "Tags"
    And I should see "Pants"

  # @todo Add coverage for profiles.
