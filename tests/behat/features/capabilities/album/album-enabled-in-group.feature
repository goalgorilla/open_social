@api
Feature: Enabled Album in group
  Benefit: Correct behaviour of enabled Album in group
  Role: As a SM
  Goal/desire: I want to activate Albums and related pages in group will be available.

  Scenario: Successfully enabled album feature
    Given I enable the module "social_album"
    And users:
      | name        | uid | status | pass            | roles       |
      | SiteManager | 444 | 1      | SiteManager     | sitemanager |

    And groups:
      | label       | author   | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group  | SiteManager | Group description       | flexible_group | en       | public                          |

    And I am logged in as "SiteManager"

    And I am on "/admin/config/opensocial/album"
    And I should see checked the box "Active"

    And I create an album using its creation page:
      | Title        | My album   |
      | Group        | Test group |
    And I should see the album I just created

    And I am viewing the "albums" page of group "Test group"
    And I should see "My Album"
    And I should see "Create new album"
