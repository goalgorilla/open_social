@api @comment @stability @DS-642 @comment-redirect
Feature: Redirect comment page
  Benefit: See the full scope of the comment thread
  Role: As a LU
  Goal/desire: I want to be redirected from comment page to full entity display

  @LU @perfect
  Scenario: Successfully redirect comment page
    Given users:
      | name              | mail                     | status |
      | Comment view user | comment-view@example.com | 1      |
    And I am logged in as "Comment view user"
    And I am viewing a "topic" with the title "Comment redirect topic"
    When I fill in the following:
         | Add a comment | This is a test comment |
    And I press "Comment"
    And I should see the success message "Your comment has been posted."
    And I click "Permalink"
    Then I should see "Comment redirect topic" in the "Hero block"
