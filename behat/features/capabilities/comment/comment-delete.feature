@api @comment @stability
Feature: Delete Comment
  Benefit: In order to manage my content
  Role: As a LU
  Goal/desire: I want to delete my comments on the platform

  Scenario: Successfully delete comment
    Given I am logged in as an "authenticated user"
    And I am viewing a "topic" with the title "Comment delete topic"
    When I fill in the following:
         | Add a comment | This is my comment |
    And I press "Comment"
    Then I should see "This is my comment" in the "Main content"
    And I should see the link "Delete"
    When I click "Delete"
    And I should see "Any replies to this comment will be lost."
    And I click "Delete"
    And I should not see "This is my comment"
