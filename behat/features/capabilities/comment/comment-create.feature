@api @comment @stability
Feature: Create Comments
  Benefit: Participate in discussions on the platform
  Role: As a LU
  Goal/desire: I want to create comment

  Scenario: Successfully create comment
    Given I am logged in as an "authenticated user"
    And I am viewing an "topic" content with the title "Comment test topic"
    When I fill in the following:
         | Add a comment | This is a test comment |
    And I press "Comment"
    Then I should see "Your comment has been posted."
    And I should see the heading "Comments" in the "Main content"
    And I should see "This is a test comment" in the "Main content"
