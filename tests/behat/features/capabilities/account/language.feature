@api @account @language @stability @LU @DS-1382 @stability-2 @test
Feature: View language selector form element in the user settings form
  Benefit: In order to change language per user
  Role: LU
  Goal/desire: I want to view language selector form element in the user settings form

  Scenario: Successfully view language selector form element in the user settings form

    Given I enable the module "social_language"

    # Language field on user form should be hidden when site has one language.
    Given I am logged in as an "authenticated user"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should not see the text "Interface language"
    And I should not see the text "Select the language you want to use this site in."

    # Language field on user form should be visible when site has more one language.
    Given I am logged in as an "administrator"
    When I am on "/admin/config/regional/language"
    And I click "Add language"
    And I select "Dutch" from "Language name"
    And I press "Add language"
    And I wait for AJAX to finish
    And I go to "/admin/config/regional/language/detection"
    And I uncheck the box "Enable url language detection method"
    And I check the box "Enable user language detection method"
    And I press "Save settings"

    And I am logged in as an "authenticated user"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see the text "Interface language"
    And I should see the text "Select the language you want to use this site in."
