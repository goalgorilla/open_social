@api
Feature: Redirect comment page
  Benefit: See the full scope of the comment thread
  Role: As a Verified
  Goal/desire: I want to be redirected from comment page to full entity display

  @verified @perfect
  Scenario: Successfully redirect comment page
    Given users:
      | name              | mail                     | status | roles    |
      | Comment view user | comment-view@example.com | 1      | verified |
    And I am logged in as "Comment view user"
    And I am viewing a "topic" with the title "Comment redirect topic"

    When I fill in the following:
      | Add a comment | This is a test comment |
    And I press "Comment"
    And I should see the success message "Your comment has been posted."
    And I click the xth "1" link with the text "ago"

    Then I should see "Comment redirect topic" in the "Hero block"
