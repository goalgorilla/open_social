@api @book @perfect @critical @DS-1766 @book-create
Feature: Create Book page
  Goal: I want to create a book so I can share information with my community

  Background:
    Given I enable the optional module social_book

  Scenario: Successfully create Book page
    Given I am logged in as an "administrator"

    When I create a book using its creation page:
      | Title        | This is my first novel                                     |
      | Description  | This is a book all about me. An autobiography so to speak! |

    Then I should see the book I just created
    And it should not show author information
