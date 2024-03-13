@api
Feature: Embed
  Benefit: Provides user feature allow/disallow embedded content
  Role: As a SM
  Goal/desire: Change the consent settings as Site mananger and check consent button on Embedded objects in posts, comments and nodes with or without WYSIWYG fields

  Background:
    Given I enable the module "social_embed"
    And topics with non-anonymous author:
      | title          | field_topic_type | body                                               | field_content_visibility |
      | Embed consent  | News             | <p>https://www.youtube.com/watch?v=fv2nWEXKSf4</p> | public                   |

  Scenario: As AU I want to configure my consent to see all embedded content immediately
    Given I set the configuration item "social_embed.settings" with key "embed_consent_settings_lu" to 1
    And I am logged in as an "authenticated user"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I uncheck the box "Enforce consent for all embedded content"
    And I press "Save"

    When I open the "topic" node with title "Embed consent"

    Then The iframe in the body description should have the src "https://www.youtube.com/embed/fv2nWEXKSf4?feature=oembed"

  Scenario: As AU I want to be able to give my consent to individual video's
    Given I set the configuration item "social_embed.settings" with key "embed_consent_settings_lu" to 1

    When I am logged in as an "authenticated user"
    And I open the "topic" node with title "Embed consent"
    And I click "Show content"
    And I wait for AJAX to finish
    And I wait for "3" seconds

    Then The embedded content in the body description should have the src "https://www.youtube.com/embed/fv2nWEXKSf4?feature=oembed"

  Scenario: As AU I want to see embedded content
    Given I am logged in as an "authenticated user"

    When I open the "topic" node with title "Embed consent"

    Then The iframe in the body description should have the src "https://www.youtube.com/embed/fv2nWEXKSf4?feature=oembed"

  Scenario: As AN I want to be able to give my consent to individual video's when that is configured
    Given I set the configuration item "social_embed.settings" with key "embed_consent_settings_an" to 1

    When I am an anonymous user
    And I open the "topic" node with title "Embed consent"
    And I click "Show content"
    And I wait for AJAX to finish
    And I wait for "3" seconds

    Then The embedded content in the body description should have the src "https://www.youtube.com/embed/fv2nWEXKSf4?feature=oembed"

  Scenario: As AN I want to see embedded content
    Given I am an anonymous user

    When I open the "topic" node with title "Embed consent"

    Then The iframe in the body description should have the src "https://www.youtube.com/embed/fv2nWEXKSf4?feature=oembed"
