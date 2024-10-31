@api
Feature: Disabled Album in group
  Benefit: Correct behaviour of disabled Album feature in group
  Role: As a SM
  Goal/desire: I want to disable Albums and related pages in group will be disabled.

  Scenario: Successfully disabled album feature
    Given I enable the module "social_album"
    And users:
      | name        | uid | status | pass            | roles       |
      | SiteManager | 444 | 1      | SiteManager     | sitemanager |
    And groups with non-anonymous owner:
      | label       | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group  | Group description       | flexible_group | en       | public                          |

    And I am logged in as "SiteManager"

    And I create an album using its creation page:
      | Title        | My album   |
      | Group        | Test group |
    And I should see the album I just created

    And I am on "/admin/config/opensocial/album"
    And I should see checked the box "Active"

    # Now lets disable Albums and verify that related pages are not available anymore.
    And I uncheck the box "Active"
    And I press "Save configuration"

    And I am viewing the group "Test group"
    And I should not see "Albums"

    And I am viewing the "albums" page of group "Test group"
    And I should see "You are not authorized to access this page."

    And I am viewing the "albums/add" page of group "Test group"
    And I should see "You are not authorized to access this page."
