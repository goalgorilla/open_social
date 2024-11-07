@api
Feature: Enabled Album for SM
  Benefit: Correct behaviour of enabled Album feature for SM
  Role: As a SM
  Goal/desire: I want to activate Albums and related pages(add/edit/view/list) will be available for SM.

  Scenario: Successfully see enabled Album feature as SM
    Given I enable the module "social_album"
    And users:
      | name        | uid | status | pass            | roles       |
      | SiteManager | 444 | 1      | SiteManager     | sitemanager |

    And I am logged in as "SiteManager"
    And I am on "/admin/config/opensocial/album"
    And I should see checked the box "Active"

    And I create an album using its creation page:
      | Title        | My album |
    And I should see the album I just created

    And I am on "node/add/album"
    And I should see "Create a album"

    And I am editing the album "My album"
    And I should see "My album"

    And I am viewing the album "My album"
    And I should see "Add images"

    And I am viewing my profile
    And I should see "Album"

    And I am on "user/444/albums"
    And I should see "1 album"

    And I am on the homepage
    And I click "Create New Content"
    And I should see the text "New Album"

    And I click "Profile of SiteManager"
    And I should see the text "My albums"
