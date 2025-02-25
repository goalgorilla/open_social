@api
Feature: Multilingual Open Social
  Benefit: Deliver site in users own language
  Role: As a Verified
  Goal/desire: Be able to view the site in multiple languages

  Scenario: Successfully change language in the user settings form

    Given I enable the module "social_language"

    # Language field on user form should be hidden when site has one language.
    And I am logged in as an "verified"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I should not see the text "Interface language"
    And I should not see the text "Select the language you want to use this site in."

    # Add Dutch language.
    And I am logged in as an "administrator"
    And I turn off translations import
    And I am on "/admin/config/regional/language"
    And I should see the text "Add language"
    And I click the xth "0" element with the css ".local-actions .button--action"
    And I select "Dutch" from "Language name"
    And I press "Add language"
    And I wait for AJAX to finish
    And I translate "Interface language" to "Taalinstelling" for "nl"
    And I translate "Create New Content" to "Inhoud aanmaken" for "nl"
    And I translate "New Event" to "Nieuw evenement" for "nl"
    And I translate "New Group" to "Nieuwe groep" for "nl"
    And I translate "Settings" to "Instellingen" for "nl"


    # Check language field not visible when User negotation is not turned on.
    And I go to "/admin/config/regional/language/detection"
    And I uncheck the box "Enable user language detection method"
    And I press "Save settings"
    And I am logged in as an "verified"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I should not see the text "Interface language"
    And I should not see the text "Select the language you want to use this site in."

    # Language field on user form should be visible when site has more than one
    # language and the User language detection is enabled.
    And I am logged in as an "administrator"
    And I go to "/admin/config/regional/language/detection"
    And I uncheck the box "Enable url language detection method"
    And I check the box "Enable user language detection method"
    And I press "Save settings"
    And I am logged in as an "verified"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I should see the text "Interface language"
    And I should see the text "Select the language you want to use this site in."

    # Switch to Dutch.
    And I select "Dutch" from "Interface language"
    And I press "Save"
    And I should see the text "Taalinstelling"

    # Check stream for Dutch translations.
    And I am on the homepage
    And I click "Inhoud aanmaken"
    And I should see the text "Nieuw evenement"
    And I should see the text "Nieuwe groep"

    # Switch back to English.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Instellingen"
    And I select "English" from "Taalinstelling"
    And I press "Save"
    And I should see the text "Interface language"

    # Check stream for English texts.
    And I am on the homepage
    And I click "Create New Content"
    And I should see the text "New event"
    And I should see the text "New group"
