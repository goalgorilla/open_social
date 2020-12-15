@api @embed @stability @perfect @critical @DS-3666 @DS-5350 @stability-4
Feature: Embed
  Benefit: More dynamic look and feel in the platform
  Role: As a LU
  Goal/desire: Embed objects in posts, comments and nodes with or without WYSIWYG fields

  Scenario: Create the files
    Given I enable the module "social_embed"
    And users:
      | name                  | mail                            | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function |
      | embed_1               | embed_1@example.com             | 1      | Em                        | Bed                     | Youtube                    | Anything               |
    And I am logged in as "embed_1"

    # Create a topic with one attachment.
    Given I am on "node/add/topic"
    And I click radio button "Discussion"
    When I fill in the following:
      | Title | Embed WYSIWYG |
    And I click on the embed icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I fill in "URL" with "https://www.youtube.com/watch?v=kgE9QNX8f3c"
    And I click the xth "0" element with the css ".url-select-dialog .form-actions .ui-button"
    And I wait for AJAX to finish
    And I wait for "3" seconds
    And I press "Save"
    Then I should see "Topic Embed WYSIWYG has been created."
    And The iframe in the body description should have the src "https://www.youtube.com/embed/kgE9QNX8f3c"
