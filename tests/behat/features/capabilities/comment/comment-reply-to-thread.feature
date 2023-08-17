@api @comment @stability @stability-2 @comment-reply-thread
Feature: See Comment
  Benefit: In order to interact with people on the platform
  Role: As a Verified
  Goal/desire: I want to reply to a comment thread

  Scenario: Successfully see reply button on comment thread
    Given I am logged in as an "verified"
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
    Then I should see an ".comments .mention-reply" element
