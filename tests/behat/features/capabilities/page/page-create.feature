@api @page @stability @perfect @critical @DS-1139
Feature: Create Page
  Benefit: In order to share useful information with users
  Role: As an administrator
  Goal/desire: I want to create Static content on the site

  Scenario: Successfully create Page
    Given I am logged in as an "administrator"
    And I am on "node/add/page"
    When I fill in the following:
      | Title | This is a static page |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I press "Save"
    And I should see "Page This is a static page has been created."
    And I should see "This is a static page" in the "Hero block"
    And I should see "Body description text" in the "Main content"
    # Authored by should not be visible
    And I should not see "By" in the "Hero block"
    # Authored date should not be visible
    And I should not see "on" in the "Hero block"
