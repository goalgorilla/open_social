@api
Feature: Delete Comment
  Benefit: In order to manage my content
  Role: As a Verified
  Goal/desire: I want to delete my comments on the platform

  Scenario: Successfully delete comment on a topic
    Given I am logged in as an "verified"
    And I am viewing a "topic" with the title "Comment delete topic"

    When I fill in the following:
         | Add a comment | This is my comment |
    And I press "Comment"
    And I should see "This is my comment" in the "Main content"
    And I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I should see the link "Delete"
    And I click "Delete"
    And I should see "Any replies to this comment will be lost."
    And I click "Delete"

    Then I should not see "This is my comment"

  Scenario: Successfully delete comment on a homepage
    Given I am logged in as an "verified"
    And I am viewing a "topic" with the title "Comment delete topic"

    When I fill in the following:
         | Add a comment | This is my comment |
    And I press "Comment"
    And I should see "This is my comment" in the "Main content"
    And I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I should see the link "Delete"
    And I click "Delete"
    And I should see "Any replies to this comment will be lost."
    And I click "Delete"

    Then I should not see "This is my comment"

  Scenario: Successfully delete comment on a group
    Given users:
      | name           | mail                     | status | roles    |
      | Group User One | group_user_1@example.com | 1      | verified |
    And groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | direct                          |
    And group members:
      | group      | user            |
      | Test group | Group User One  |
    And I am logged in as "Group User One"

    When I am viewing the group "Test group"
    And I click "Stream"
    And I fill in "Say something to the group" with "This is a post in a group."
    And I press "Post"
    And I should see "This is a post in a group."
    And I should see "Group User One" in the ".media-heading" element
    And I fill in "Write a comment..." with "This is a comment in post in a group."
    And I press "Comment"
    And I should see "Your comment has been posted."
    And I should see "This is a comment in post in a group."
    And I am on the homepage
    And I should see "This is a comment in post in a group."

    # Delete post
    Then I am viewing the group "Test group"
    And I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I click the element with css selector ".comment-delete"
    And I should see "Any replies to this comment will be lost. This action cannot be undone."
    And I press "Delete"
    And I wait for AJAX to finish
    And I should see "The comment and all its replies have been deleted."
    And I should not see "This is a comment in post in a group."
    And I am on the homepage
    And I should not see "This is a comment in post in a group."