@api
Feature: Disable Album for SM
  Benefit: Correct behaviour of disabled Album feature for SM
  Role: As a SM
  Goal/desire: I want to Disable Albums and related pages(add/edit/view/list) should be still available for SM.

  Scenario: Successfully see disabled album feature as SM
    Given I enable the module "social_album"
    And users:
      | name         | status | pass             | roles       |
      | SiteManager1  | 1      | SiteManager      | sitemanager |

    And I am logged in as "SiteManager1"

    # Verify that Album feature is enabled.
    And I am on "/admin/config/opensocial/album"
    And I check the box "Active"
    And I press "Save configuration"
    And I should see checked the box "Active"

    And I create an album using its creation page:
      | Title        | My album |
    And I should see the album I just created

    # Now lets disable Albums and verify that related pages are still available for SM
    # but except "Create Album" links.
    And I am on "/admin/config/opensocial/album"
    And I uncheck the box "Active"
    And I press "Save configuration"

    And I am on "node/add/album"
    And I should see "Access denied"

    And I am editing the album "My album"
    And I should see "Edit Album My album"

    And I am viewing the album "My album"
    And I should see "0 image"

    And I am viewing my profile
    And I should not see "Album"

    And I am on "user/444/albums"
    And I should see "You are not authorized to access this page."

    And I am on the homepage
    And I click "Create New Content"
    And I should not see the text "New Album"

    And I click "Profile of SiteManager"
    And I should not see the text "My albums"
