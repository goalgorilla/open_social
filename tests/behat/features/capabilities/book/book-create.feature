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


  Scenario: User can create "book" node as book by default
    Given I am logged in as an "administrator"

    When I am on "node/add/book"
    Then I should see "- Create a new book -" in the "#edit-book-bid" element

  Scenario: User can create a topic as a book
    Given I am logged in as an "administrator"
    Given Book structure is enabled for topics

    When I am on "node/add/topic"
    Then I should see "- None -" in the "#edit-book-bid" element
