@api @book @perfect @critical @DS-1766
Feature: Create Book page
  Benefit: In order to share useful information with users
  Role: As an administrator
  Goal/desire: I want to create a book page on the site

  Scenario: Successfully create Book page
    Given I am logged in as an "administrator"
    And I am on "node/add/book"
    When I fill in the following:
      | Title | This is my first novel |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a book all about me. An autobiography so to speak!"
    And I press "Save"
    Then I should see "Book page This is my first novel has been created."
    And I should see "This is my first novel" in the "Hero block"
    And I should see "This is a book all about me. An autobiography so to speak!" in the "Main content"
    # Authored by should not be visible
    And I should not see "By" in the "Hero block"
    # Authored date should not be visible
    And I should not see " on " in the "Hero block"
