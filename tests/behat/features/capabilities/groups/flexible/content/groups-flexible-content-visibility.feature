@api @javascript @flexible-groups @flexible-groups-content-visibility
Feature: Verify that selected group content visibility applies correctly for content in flexible groups

  Background:
    Given I enable the module "social_group_flexible_group"

    And groups with non-anonymous owner:
      | label           | field_group_description      | field_flexible_group_visibility | field_group_allowed_visibility  |type            |
      | Flexible group  | Description of Flexible group| public                          | public,community,group          |flexible_group  |
    And "topic_type" terms:
      | name    |
      | Blog    |
    And topics with non-anonymous author:
      | title           | body         | group          | field_content_visibility | field_topic_type |
      | Topic public    | Descriptions | Flexible group | public                   | Blog             |
      | Topic community | Descriptions | Flexible group | community                | Blog             |
      | Topic group     | Descriptions | Flexible group | group                    | Blog             |

  Scenario: Update group content visibility - so all content items should be updated accordingly
    # Lets update content visibility and disable "Public" option.
    Given I am logged in as a user with the sitemanager role
    When I am editing the group "Flexible group"
    And I uncheck the box "field_group_allowed_visibility[public]"
    And I press "Save"
    And I wait for the batch job to finish
    And the cache has been cleared

    # Then lets verify all topics visibility.
    When I am editing the topic "Topic public"
    And I should see unchecked the box "Public"
    And I should see checked the box "Community"

    When I am editing the topic "Topic Community"
    And I should see checked the box "Community"

    When I am editing the topic "Topic group"
    And I should see checked the box "Group members"
