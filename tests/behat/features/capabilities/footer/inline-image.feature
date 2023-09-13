@api @footer @stability @PROD-25874
Feature: Inline image
  Benefit: Have a social footer with an inline image
  Role: As a Site Manager
  Goal/desire: Embed images in social footer block with WYSIWYG

  Scenario: Embed an inline image
    Given I enable the module "social_footer"
    And users:
      | name                  | mail                            | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function | roles    |
      | social_footer_1               | social_footer_1@example.com             | 1      | Social                        | Footer                     | Youtube                    | Anything               | sitemanager |
    And I am logged in as "social_footer_1"
    Given I am on "admin/config/opensocial/footer-block"
    And I click on the image icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I attach the file "/files/opensocial.jpg" to "Image"
    And I wait for AJAX to finish
    And I fill in "Alternative text" with "Just a social footer image test"
    And I click the xth "0" element with the css ".editor-image-dialog .form-actions .ui-button"
    And I press "Save configuration"
    Then I should see "Your footer settings have been updated"
    When I am on "/user"
    Then I should see "#MadeToShare"

