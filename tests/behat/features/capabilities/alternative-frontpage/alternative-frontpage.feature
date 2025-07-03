@api
Feature: Set alternative frontpage
  Benefit: In order to improve the Look and Feel for LU
  Role: AN
  Goal/desire: I want to set a different frontpage for Anonymous and Logged-in users

  Scenario: Successfully set alternative frontpage

    Given I enable the module "alternative_frontpage"
    And page content:
      | title          | status | field_content_visibility | alias         |
      | Frontpage AN   | 1      | public                   | /frontpage-an |
      | Frontpage LU   | 1      | community                | /frontpage-lu |
    And users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
    # Configure the settings
    And I am logged in as "behatsitemanager"
    And I am on "/admin/config/alternative_frontpage"
    And I click "Add Frontpage Setting"
    And I fill in "Label" with "Anonymous users"
    # We have to wait a bit, as the javascript does not have time to generate the machine name of the field.
    And I wait for "2" seconds
    And I fill in the following:
      | Frontpage path        | /user/logout    |
    And I click radio button "Anonymous user"
    And I press "Save"
    And I should see "The path for the frontpage is not allowed."
    And I fill in "Frontpage path" with "stream"
    And I press "Save"
    And I should see "The path for the frontpage should start with a forward slash."
    And I fill in "Frontpage path" with "Invalid page"
    And I press "Save"
    And I should see "The path for the frontpage is not valid."
    # Fill in the correct settings.
    And I fill in "Frontpage path" with "/frontpage-an"
    And I press "Save"
    And I click "Add Frontpage Setting"
    And I fill in "Label" with "Logged users"
    # We have to wait a bit, as the javascript does not have time to generate the machine name of the field.
    And I wait for "2" seconds
    And I fill in the following:
      | Frontpage path        | /frontpage-lu |
    And I click radio button "Authenticated user"
    And I press "Save"
    And the cache has been cleared
    And I click "Back to site"
    And I should see "Frontpage LU"
    # See as AN
    And I logout
    And I click "Home"
    And I should see "Frontpage AN"

    # Restore the settings
    And I am logged in as "behatsitemanager"
    And I am on "admin/config/alternative_frontpage/manage/anonymous_users/delete"
    And I click the xth "0" element with the css "#edit-submit"
    And I should see "Entity Anonymous users has been deleted."
    And I am on "admin/config/alternative_frontpage/manage/logged_users/delete"
    And I click the xth "0" element with the css "#edit-submit"
    And I should see "Logged users has been deleted."
    And I click "Back to site"
    And the cache has been cleared
    And I am on "stream"
