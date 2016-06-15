@api @comment @stability @DS-459 @topic
Feature: Create Comments
  Benefit: Participate in discussions on the platform
  Role: As a LU
  Goal/desire: I want to create and see a comment

  @LU @perfect
  Scenario: Successfully create and see a comment
    Given users:
      | name              | mail                     | status |
      | Comment view user | comment-view@example.com | 1      |
    And I am logged in as "Comment view user"
    And I am viewing a "topic" with the title "Comment test topic"
    When I fill in the following:
         | Add a comment | This is a test comment |
    And I press "Comment"
    And I should see the success message "Your comment has been posted."
    And I should see the heading "Comments" in the "Main content"
    And I should see "This is a test comment" in the "Main content"
    And I should see "Comment view user"
    And I should see "second"
    And I should see "ago"
