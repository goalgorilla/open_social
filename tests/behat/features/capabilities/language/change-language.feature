@api @account @language @stability @LU @DS-1382 @stability-2
Feature: Multilingual Open Social
  Benefit: Deliver site in users own language
  Role: LU
  Goal/desire: Be able to view the site in multiple languages

  Scenario: Successfully change language in the user settings form

    Given I enable the module "social_language"

    # Language field on user form should be hidden when site has one language.
    Given I am logged in as an "authenticated user"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should not see the text "Interface language"
    And I should not see the text "Select the language you want to use this site in."

    # Add Dutch language.
    Given I am logged in as an "administrator"
    When I am on "/admin/config/regional/language"
    And I click "Add language"
    And I select "Dutch" from "Language name"
    And I press "Add language"
    And I wait for the batch job to finish

    # Check language field not visible when User negotation is not turned on.
    Given I go to "/admin/config/regional/language/detection"
    And I uncheck the box "Enable user language detection method"
    And I press "Save settings"
    And I am logged in as an "authenticated user"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should not see the text "Interface language"
    And I should not see the text "Select the language you want to use this site in."

    # Language field on user form should be visible when site has more than one
    # language and the User language detection is enabled.
    Given I am logged in as an "administrator"
    When I go to "/admin/config/regional/language/detection"
    And I uncheck the box "Enable url language detection method"
    And I check the box "Enable user language detection method"
    And I press "Save settings"
    And I am logged in as an "authenticated user"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see the text "Interface language"
    And I should see the text "Select the language you want to use this site in."

    # Switch to Dutch.
    When I select "Dutch" from "Interface language"
    And I press "Save"
    Then I should see the text "Lokalisatie-instellingen"

    # Check stream for Dutch translations.
    When I am on the homepage
    Then I should see the text "Alle onderwerpen"

    When I click the xth "0" element with the css ".navbar-nav .dropdown"
    Then I should see the text "Nieuw evenement"
    And I should see the text "Nieuwe groep"

    # Switch back to English.
    Given I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Instellingen"
    And I select "Engels" from "Taal van de editor"
    And I press "Opslaan"
    Then I should see the text "Locale settings"

    # Check stream for English texts.
    When I am on the homepage
    Then I should see the text "Enrolled"
    And I should see the text "All topics"

    When I click the xth "0" element with the css ".navbar-nav .dropdown"
    Then I should see the text "New event"
    And I should see the text "New group"