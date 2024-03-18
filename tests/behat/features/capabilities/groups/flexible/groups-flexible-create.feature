@api @javascript @flexible-groups @TB-6072 @DS-4211 @ECI-632
Feature: Create flexible groups
  # @todo Rewrite in Jobs to be done format
  Benefit: So I can work together with others in a relative small circle
  Role: As a Verified
  Goal/desire: I want to create flexible Groups

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario Outline: Create a flexible group using the form
    Given I am logged in as a user with the <role> role

    # @todo We're not testing image upload yet.
    When I create a flexible group using its creation page:
      | Title                            | Test flexible group |
      | Description                      | Description text    |
      | Group visibility                 | community           |
      | Join method                      | direct              |
      | Location name                    | Technopark          |
      | Country                          | Ukraine             |
      | City                             | Lviv                |
      | Street address                   | Fedkovycha 60a      |
      | Postal code                      | 79000               |
    # @todo https://www.drupal.org/project/social/issues/3314737
    # | Oblast                           | Lviv Oblast         |

    Then I should see the group I just created

    Examples:
      | role           |
      | verified       |
      | contentmanager |
      | sitemanager    |

  Scenario: Unverified can't access flexible group creation form
    Given I am logged in as a user with the authenticated role

    When I visit the flexible group create form

    Then I should be denied access

  Scenario: Anonymous can't access flexible group creation form
    Given I am an anonymous user

    When I visit the flexible group create form

    Then I should be asked to login
