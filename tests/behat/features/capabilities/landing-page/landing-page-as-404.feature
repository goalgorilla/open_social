@api @landing-page @stability @perfect @critical @stability-4
Feature: Use a landing page as 404 page
  Benefit: Provide a customisable visually pleasing 404 page
  Role: LU
  Goal/desire: I want to use a landing page as not found page

  Scenario: Create a landing page and configure it as 404 page

    Given I enable the module "social_landing_page"
    # Create Landing Page Hero
    Given I am logged in as an "sitemanager"
    When I am on "node/add/landing_page"
    And I fill in the following:
      | Title | Page not found |
    And I click radio button "Public - visible to everyone including people who are not a member" with the id "edit-field-content-visibility-public"
    And I press "Add Section"
    And I wait for AJAX to finish
    And I press "Add Hero"
    And I wait for AJAX to finish
    And I fill in the following:
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_title][0][value]                                     | Hero title    |
      | field_landing_page_section[0][subform][field_section_paragraph][0][subform][field_hero_subtitle][0][value]                                  | Hero subtitle |
    # Set URL Alias
    And I click "URL path settings"
    And I fill in "URL alias" with "page-not-found"
    And I press "Save"
    And I wait for "3" seconds
    # See as LU
    Then I should see "Landing page Page not found has been created."
    Given I set the configuration item "system.site" with key "page.404" to "/page-not-found"
    And I am on "this-is-a-page-that-should-never-exist-thats-why-its-so-long-if-this-does-exist-then-this-test-breaks"
    Then I should see "Hero title"
    And I should not see an ".block-social-page-title-block" element
