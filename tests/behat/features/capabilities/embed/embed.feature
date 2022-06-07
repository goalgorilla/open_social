@api @embed @stability @perfect @critical @DS-3666 @DS-5350 @stability-4
Feature: Embed
  Benefit: More dynamic look and feel in the platform
  Role: As a Verified
  Goal/desire: Embed objects in posts, comments and nodes with or without WYSIWYG fields

  Scenario: Create the files
    Given I enable the module "social_embed"
    And users:
      | name                  | mail                            | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function | roles    |
      | embed_1               | embed_1@example.com             | 1      | Em                        | Bed                     | Youtube                    | Anything               | verified |
    And I am logged in as "embed_1"

    # Create a topic with one attachment.
    Given I am on "node/add/topic"
    And I check the box "News"
    When I fill in the following:
      | Title | Embed WYSIWYG |
    And I click on the embed icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I fill in "URL" with "https://www.youtube.com/watch?v=ojafuCcUZzU"
    And I click the xth "0" element with the css ".url-select-dialog .form-actions .ui-button"
    # Temporary comment next step since it will fails because of the Embed
    # module new release https://www.drupal.org/project/embed/releases/8.x-1.5
    # @see https://git.drupalcode.org/project/embed/-/commit/89b249e4da8f5b39fdfa3e97960107c850427469
    # @todo Uncomment it out when a solution will found
    # And I wait for AJAX to finish
    And I wait for "3" seconds
    And I press "Create topic"
    Then I should see "Topic Embed WYSIWYG has been created."
    And The iframe in the body description should have the src "https://www.youtube.com/embed/ojafuCcUZzU"
