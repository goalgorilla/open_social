@api @comment @stability @DS-478 @stability-3 @comment-delete
Feature: Delete Comment
  Benefit: In order to manage my content
  Role: As a Verified
  Goal/desire: I want to delete my comments on the platform

  Scenario: Successfully delete comment
    Given I am logged in as an "verified"
    And topics with non-anonymous author:
      | title                | status | body          | field_content_visibility | field_topic_type |
      | Comment delete topic | 1      | Description   | public                   | News             |
    And I am viewing the topic "Comment delete topic"
    When I fill in the following:
         | Add a comment | This is my comment |
    And I press "Comment"
    Then I should see "This is my comment" in the "Main content"
    When I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I should see the link "Delete"
    When I click "Delete"
    And I should see "Any replies to this comment will be lost."
    And I click "Delete"
    And I should not see "This is my comment"
