@api @javascript
Feature: Flexible groups view access for unpublished groups
  Background:
    Given I enable the module "social_group_flexible_group"
    And groups with non-anonymous owner:
        | label      | field_group_description | type           | langcode | field_flexible_group_visibility | status |
        | Test group | Community visibility    | flexible_group | en       | community                       | 0      |

  Scenario: As verified user I can't view a community group that is unpublished
    Given I am logged in as a user with the verified role

    When I am viewing the "about" page of group "Test group"

    Then I should be denied access

  Scenario: As sitemanager user I can view a community group that is unpublished
    Given I am logged in as a user with the sitemanager role

    When I am viewing the "about" page of group "Test group"

    Then I should see "Test group"

  Scenario: As Group Manager user I can view a community group that is unpublished
    Given I am logged in as a user with the verified role
    And groups owned by current user:
      | label         | field_group_description | type           | langcode | field_flexible_group_visibility | status |
      | Manager group | Community visibility    | flexible_group | en       | community                       | 0      |

    When I am viewing the "about" page of group "Manager group"

    Then I should see "Manager group"
