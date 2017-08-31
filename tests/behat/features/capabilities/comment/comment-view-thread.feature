@api @comment @stability @DS-477 @stability-2
Feature: See Comment
  Benefit: In order to interact with people on the platform
  Role: As a LU
  Goal/desire: I want to see comments

  Scenario: Successfully see comment thread
    Given I am logged in as an "authenticated user"
    And I am viewing a "topic" with the title "Comment view thread"
    When I fill in the following:
         | Add a comment | This is a first comment |
    And I press "Comment"
    Then I should see "This is a first comment" in the "Main content"
    Then I should see the link "Reply"
    When I click "Reply"
    And I fill in the following:
      | Add a reply | This is a reply comment |
    And I press "Reply"
    And I should see "This is a reply comment"
    When I fill in the following:
      | Add a comment | This is a second comment |
    And I press "Comment"
    Then I should see "This is a second comment"
    And "This is a first comment" should precede "This is a second comment" for the query ".js-comment"
