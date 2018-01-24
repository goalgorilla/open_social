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
    # Configure the settings
    Given I am logged in as an "sitemanager"
    When I am on "admin/config/alternative_frontpage"
    # Error validation
    And I fill in the following:
      | Frontpage for anonymous users     |              |
      | Frontpage for authenticated users | /user/logout |
    And I press "Save configuration"
    Then I should see "The path for the anonymous frontpage cannot be empty."
    And I should see "The path for the authenticated frontpage is not allowed."
    When I fill in the following:
      | Frontpage for anonymous users     | stream       |
      | Frontpage for authenticated users | invalid page |
    And I press "Save configuration"
    Then I should see "The path for the anonymous frontpage should start with a forward slash."
    And I should see "The path for the authenticated frontpage is not valid."
    # Fill in the correct settings.
    When I fill in the following:
      | Frontpage for anonymous users     | /frontpage-an |
      | Frontpage for authenticated users | /frontpage-lu |
    And I press "Save configuration"
    And I am on "frontpage-an"
    Then I should see "Frontpage LU"
    # See as AN
    Given I logout
    When I click "Home"
    Then I should see "Frontpage AN"

    # Restore the settings
    Given I am on "user/login"
    When I fill in the following:
      | Username or email address | admin |
      | Password                  | admin |
    And I press "Log in"
    Given I am on "admin/config/alternative_frontpage"
    # Error validation
    When I fill in the following:
      | Frontpage for anonymous users     | /stream |
      | Frontpage for authenticated users |         |
    And I press "Save configuration"
    Then I should see "The configuration options have been saved."
