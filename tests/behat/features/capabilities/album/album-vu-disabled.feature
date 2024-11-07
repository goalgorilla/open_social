@api
Feature: Disable Album for VU
  Benefit: Correct behaviour of disabled Album feature for VU
  Role: As a VU
  Goal/desire: I want to Disable Albums and related pages(add/edit/view/list) will be disabled for VU.

  Scenario: Successfully disable Album feature for VU
    Given I enable the module "social_album"
    And users:
      | name         | uid | status | pass             | roles       |
      | SiteManager  | 444 | 1      | SiteManager      | sitemanager |
      | VerifiedUser | 445 | 1      | VerifiedUser     | verified    |

    And I am logged in as "SiteManager"

    # Verify that Album feature is enabled.
    And I am on "/admin/config/opensocial/album"
    And I should see checked the box "Active"

    And I create an album using its creation page:
      | Title        | My album |
    And I should see the album I just created


    # Now lets disable Albums and verify that related pages are not available anymore for VU.
    And I am on "/admin/config/opensocial/album"
    And I uncheck the box "Active"
    And I press "Save configuration"

    And I am logged in as "VerifiedUser"
    And I am on "node/add/album"
    And I should see "Access denied"

    And I am editing the album "My album"
    And I should see "Access denied"

    And I am viewing the album "My album"
    And I should see "Access denied"

    And I am viewing my profile
    And I should not see "Album"

    And I am on "user/445/albums"
    And I should see "You are not authorized to access this page."

    And I am on the homepage
    And I click "Create New Content"
    And I should not see the text "New Album"

    And I click "Profile of VerifiedUser"
    And I should not see the text "My albums"
