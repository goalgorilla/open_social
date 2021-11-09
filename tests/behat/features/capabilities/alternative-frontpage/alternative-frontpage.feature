@api @alternative-frontpage @stability @perfect @critical @DS-4638 @stability-4
Feature: Set alternative frontpage
  Benefit: In order to improve the Look and Feel for LU
  Role: AN
  Goal/desire: I want to set a different frontpage for Anonymous and Logged-in users

  Scenario: Successfully set alternative frontpage

    Given I enable the module "alternative_frontpage"
    Given page content:
      | title          | status | field_content_visibility | alias         |
      | Frontpage AN   | 1      | public                   | /frontpage-an |
      | Frontpage LU   | 1      | community                | /frontpage-lu |
    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
    # Configure the settings
    Given I am logged in as "behatsitemanager"
    When I am on "/admin/config/alternative_frontpage"
    And I click "Add Frontpage Setting"
    When I fill in "Label" with "Anonymous users"
    # We have to wait a bit, as the javascript does not have time to generate the machine name of the field.
    And I wait for "2" seconds
    And I fill in the following:
      | Frontpage path        | /user/logout    |
    And I click radio button "Anonymous user"
    And I press "Save"
    Then I should see "The path for the frontpage is not allowed."
    When I fill in "Frontpage path" with "stream"
    And I press "Save"
    Then I should see "The path for the frontpage should start with a forward slash."
    When I fill in "Frontpage path" with "Invalid page"
    And I press "Save"
    And I should see "The path for the frontpage is not valid."
    # Fill in the correct settings.
    When I fill in "Frontpage path" with "/frontpage-an"
    And I press "Save"
    And I click "Add Frontpage Setting"
    When I fill in "Label" with "Logged users"
    # We have to wait a bit, as the javascript does not have time to generate the machine name of the field.
    And I wait for "2" seconds
    And I fill in the following:
      | Frontpage path        | /frontpage-lu |
    And I click radio button "Authenticated user"
    And I press "Save"
    And the cache has been cleared
    And I click "Home"
    Then I should see "Frontpage LU"
    # See as AN
    Given I logout
    When I click "Home"
    Then I should see "Frontpage AN"

    # Restore the settings
    When I am logged in as "behatsitemanager"
    And I am on "admin/config/alternative_frontpage/manage/anonymous_users/delete"
    And I click the xth "0" element with the css "#edit-submit"
    Then I should see "Entity Anonymous users has been deleted."
    When I am on "admin/config/alternative_frontpage/manage/logged_users/delete"
    And I click the xth "0" element with the css "#edit-submit"
    Then I should see "Logged users has been deleted."
    When I click "Home"
    And the cache has been cleared
    Then I am on "stream"
