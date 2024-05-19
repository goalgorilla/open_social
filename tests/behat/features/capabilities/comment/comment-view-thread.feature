@api @comment @stability @DS-477 @stability-2 @comment-view-thread
Feature: See Comment
  Benefit: In order to interact with people on the platform
  Role: As a Verified
  Goal/desire: I want to see comments

  Scenario: Successfully see comment thread
    Given I am logged in as an "verified"
    And topics with non-anonymous author:
      | title              | status | body          | field_content_visibility | field_topic_type |
      | Comment view topic | 1      | Description   | public                   | News             |
    And I am viewing the topic "Comment view topic"
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
