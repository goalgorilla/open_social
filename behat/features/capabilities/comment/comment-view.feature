@api @comment @stability
Feature: See Comment
  Benefit: In order to interact with people on the platform
  Role: As a LU
  Goal/desire: I want to see comments

  Scenario: Successfully see comment
    Given users:
      | name              | mail                     | status |
      | Comment view user | comment-view@example.com | 1      |
    And I am logged in as "Comment view user"
    And I am viewing a "topic" with the title "Comment view test topic"
    When I fill in the following:
         | Add a comment | This is a test comment |
    And I press "Comment"
    Then I should see "This is a test comment" in the "Main content"
    And I should see "Comment view user"
    And I should see "ago"
    And I should see the link "Permalink"
