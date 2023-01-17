@api @book @perfect @critical @DS-1766 @book-create
Feature: Create Book page
  Goal: I want to create a book so I can share information with my community

  Background:
    Given I enable the optional module social_book

  Scenario: Successfully create Book page
    Given I am logged in as an "administrator"

    When I create a book using its creation page:
      | Title        | This is my first novel                                      |
      | Description  | This is a book all about me. An autobiography so to speak! |

    Then I should see the book I just created
    And it should not show author information

  Scenario: A book type node can not be outside of a book structure
    Given I am logged in as an "contentmanager"

    When I view the book creation page

    And should not see "- None -" in the Book select field

  Scenario: A new book is created by default
    Given I am logged in as an "contentmanager"

    When I view the book creation page

    Then I should see "- Create a new book -" selected in the Book select field

  Scenario: Enabling core book functionality for other node types allows the content to still be placed outside of a book
    Given I am logged in as an "contentmanager"
    And book structure is enabled for topic

    When I am on "node/add/topic"

    Then I should see "- None -" selected in the Book select field
