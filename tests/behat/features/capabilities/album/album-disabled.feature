@api
Feature: Disable Album
  Benefit: Correct behaviour of disabled Album feature
  Role: As a SM
  Goal/desire: I want to Disable Albums and related pages(add/edit/view/list) will be disabled as well.

  Scenario: Successfully disable album feature
    Given I enable the module "social_album"
    And users:
      | name        | uid | status | pass            | roles       |
      | SiteManager | 444 | 1      | SiteManager     | sitemanager |

    And I am logged in as "SiteManager"
    And I create an album using its creation page:
      | Title        | My album |
    And I should see the album I just created

    And I am editing the album "My album"
    And I should see "My album"

    And I am on "/admin/config/opensocial/album"
    And I should see checked the box "Active"

    # Now lets disable Albums and verify that related pages are not available anymore.
    And I uncheck the box "Active"
    And I press "Save configuration"

    And I am on "node/add/album"
    And I should see "Access denied"

    And I am editing the album "My album"
    And I should see "Access denied"

    And I am viewing the album "My album"
    And I should see "Access denied"

    And I am viewing my profile
    And I should not see "Album"

    And I am on "user/444/albums"
    And I should see "You are not authorized to access this page."

    And I am on the homepage
    And I click "Create New Content"
    And I should not see the text "New Album"

    And I click "Profile of SiteManager"
    And I should not see the text "My albums"
