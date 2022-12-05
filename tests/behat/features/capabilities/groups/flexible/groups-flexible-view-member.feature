@api @javascript @flexible-groups
Feature: Flexible groups view access for members
  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario Outline: As a member of a group I can view a group of any visibility
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And Search indexes are up to date
    And I am logged in as a user with the <role> role
    And I am a member of "Test group"

    When I am viewing the group "Test group"

    Then I should see "Test group"

    Examples:
      | visibility | role           |
      | public     | authenticated  |
      | public     | verified       |
      | public     | contentmanager |
      | public     | sitemanager    |
      | community  | authenticated  |
      | community  | verified       |
      | community  | contentmanager |
      | community  | sitemanager    |
      | members    | authenticated  |
      | members    | verified       |
      | members    | contentmanager |
      | members    | sitemanager    |

  Scenario Outline: As a member of a group I can view a group of any visibility on the groups search
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And Search indexes are up to date
    And I am logged in as a user with the <role> role
    And I am a member of "Test group"

    When I search groups for "Test group"

    Then I should see "Test group"

    Examples:
      | visibility | role           |
      | public     | authenticated  |
      | public     | verified       |
      | public     | contentmanager |
      | public     | sitemanager    |
      | community  | authenticated  |
      | community  | verified       |
      | community  | contentmanager |
      | community  | sitemanager    |
      | members    | authenticated  |
      | members    | verified       |
      | members    | contentmanager |
      | members    | sitemanager    |

  Scenario Outline: As a member of a group I can view a group of any visibility on the groups overview
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And I am logged in as a user with the <role> role
    And I am a member of "Test group"

    When I am viewing the groups overview

    Then I should see "Test group"

    Examples:
      | visibility | role           |
      | public     | authenticated  |
      | public     | verified       |
      | public     | contentmanager |
      | public     | sitemanager    |
      # @todo https://www.drupal.org/project/social/issues/3324969
      # | community  | authenticated  |
      | community  | verified       |
      | community  | contentmanager |
      | community  | sitemanager    |
      | members    | authenticated  |
      | members    | verified       |
      | members    | contentmanager |
      | members    | sitemanager    |
