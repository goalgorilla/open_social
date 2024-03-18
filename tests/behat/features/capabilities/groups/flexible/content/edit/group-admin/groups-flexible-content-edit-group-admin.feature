@api @javascript @flexible-groups-content
Feature: Test edit access for content in groups as group admin

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario Outline: Can edit topics I don't own in a group as group admin
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Group description       | flexible_group | en       | <group_visibility>              |
    And topics with non-anonymous author:
      | title        | group      | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | Test group | News             | Body description text | <content_visibility>     | en       |
    And I am logged in as a user with the <role> role
    And I am a member of "Test group" with the "flexible_group-group_admin" role

    When I am editing the topic "Test content"

    Then I should see "Edit Topic Test Content"

  Examples:
    | role           | group_visibility | content_visibility |
    | authenticated  | public           | public             |
    | authenticated  | public           | community          |
    | authenticated  | public           | group              |
    | authenticated  | community        | public             |
    | authenticated  | community        | community          |
    | authenticated  | community        | group              |
    | authenticated  | members          | public             |
    | authenticated  | members          | community          |
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

    # @todo It appears there's currently custom access handling for events which prevents any of this from working.
    # https://www.drupal.org/project/social/issues/3324968
#  Scenario Outline: Can edit events I don't own in a group as group admin
#    Given groups with non-anonymous owner:
#      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
#      | Test group | Group description       | flexible_group | en       | <group_visibility>              |
#    And events with non-anonymous owner:
#      | title        | group      | body                  | field_content_visibility | field_event_date    | langcode |
#      | Test content | Test group | Body description text | <content_visibility>     | 2100-01-01T12:00:00 | en       |
#    And I am logged in as a user with the <role> role
#    And I am a member of "Test group" with the "flexible_group-group_admin" role
#
#    When I am editing the event "Test content"
#
#    Then I should see "Edit Event Test Content"
#
#  Examples:
#    | role           | group_visibility | content_visibility |
#    | authenticated  | public           | public             |
#    | authenticated  | public           | community          |
#    | authenticated  | public           | group              |
#    | authenticated  | community        | public             |
#    | authenticated  | community        | community          |
#    | authenticated  | community        | group              |
#    | authenticated  | members          | public             |
#    | authenticated  | members          | community          |
#    | authenticated  | members          | group              |
#    | verified       | public           | public             |
#    | verified       | public           | community          |
#    | verified       | public           | group              |
#    | verified       | community        | public             |
#    | verified       | community        | community          |
#    | verified       | community        | group              |
#    | verified       | members          | public             |
#    | verified       | members          | community          |
#    | verified       | members          | group              |
