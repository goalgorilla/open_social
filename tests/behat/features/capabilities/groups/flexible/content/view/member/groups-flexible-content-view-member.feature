@api @javascript @flexible-groups-content
Feature: Flexible groups content view access for group members

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario Outline: As group member view a topic in a group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | <group_visibility>              |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | <content_visibility>     | en       |
    And I am logged in as a user with the <role> role
    And I am a member of "Test group"

    When I am viewing the topic "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Examples:
    | role           | group_visibility | content_visibility |
    | authenticated  | public           | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | public           | community          |
    | authenticated  | public           | group              |
    | authenticated  | community        | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | community        | community          |
    | authenticated  | community        | group              |
    | authenticated  | members          | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | members          | community          |
    | authenticated  | members          | group              |
    | verified       | public           | public             |
    | verified       | public           | community          |
    | verified       | public           | group              |
    | verified       | community        | public             |
    | verified       | community        | community          |
    | verified       | community        | group              |
    | verified       | members          | public             |
    | verified       | members          | community          |
    | verified       | members          | group              |
    | contentmanager | public           | public             |
    | contentmanager | public           | community          |
    | contentmanager | public           | group              |
    | contentmanager | community        | public             |
    | contentmanager | community        | community          |
    | contentmanager | community        | group              |
    | contentmanager | members          | public             |
    | contentmanager | members          | community          |
    | contentmanager | members          | group              |
    | sitemanager    | public           | public             |
    | sitemanager    | public           | community          |
    | sitemanager    | public           | group              |
    | sitemanager    | community        | public             |
    | sitemanager    | community        | community          |
    | sitemanager    | community        | group              |
    | sitemanager    | members          | public             |
    | sitemanager    | members          | community          |
    | sitemanager    | members          | group              |

  Scenario Outline: As group member view an event in a group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | <group_visibility>              |
    And events with non-anonymous author:
      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
      | Test content | Test group | Body description text | <content_visibility>     | 2100-01-01T12:00:00 | en       |
    And I am logged in as a user with the <role> role
    And I am a member of "Test group"

    When I am viewing the event "Test content"

    Then I should see "Test content"
    And I should see "Test group"

  Examples:
    | role           | group_visibility | content_visibility |
    | authenticated  | public           | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | public           | community          |
    | authenticated  | public           | group              |
    | authenticated  | community        | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | community        | community          |
    | authenticated  | community        | group              |
    | authenticated  | members          | public             |
    # @todo https://www.drupal.org/project/social/issues/3324967
    # | authenticated  | members          | community          |
    | authenticated  | members          | group              |
    | verified       | public           | public             |
    | verified       | public           | community          |
    | verified       | public           | group              |
    | verified       | community        | public             |
    | verified       | community        | community          |
    | verified       | community        | group              |
    | verified       | members          | public             |
    | verified       | members          | community          |
    | verified       | members          | group              |
    | contentmanager | public           | public             |
    | contentmanager | public           | community          |
    | contentmanager | public           | group              |
    | contentmanager | community        | public             |
    | contentmanager | community        | community          |
    | contentmanager | community        | group              |
    | contentmanager | members          | public             |
    | contentmanager | members          | community          |
    | contentmanager | members          | group              |
    | sitemanager    | public           | public             |
    | sitemanager    | public           | community          |
    | sitemanager    | public           | group              |
    | sitemanager    | community        | public             |
    | sitemanager    | community        | community          |
    | sitemanager    | community        | group              |
    | sitemanager    | members          | public             |
    | sitemanager    | members          | community          |
    | sitemanager    | members          | group              |
