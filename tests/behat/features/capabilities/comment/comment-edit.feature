@api @comment @stability @edit @comment-edit @stability-4
Feature: Edit Comment
  Benefit: Correct mistakes I made when adding comments
  Role: As a Verified
  Goal/desire: I want to edit my comments on the platform

  Scenario: Successfully edit comment
    Given I am logged in as an "verified"
    And topics with non-anonymous author:
      | title              | status | body          | field_content_visibility | field_topic_type |
      | Comment edit topic | 1      | Description   | public                   | News             |
    And I am viewing the topic "Comment edit topic"
    When I fill in the following:
      | Add a comment | This is my comment |
    And I press "Comment"
    Then I should see "This is my comment" in the "Main content"
    When I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I should see the link "Edit"
    When I click "Edit"
    When I fill in the following:
      | Add a comment | This is my edited comment |
    And I press "Submit"
    And I should see "This is my edited comment"
