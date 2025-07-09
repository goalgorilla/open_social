@api @javascript
Feature: Comment on a Post
  Benefit: In order to give my opinion on a post
  Role: As a Verified
  Goal/desire: I want to comment on a post

  Background:
    Given users:
      | name      | status | pass      | roles    |
      | PostUser1 |      1 | PostUser1 | verified |
      | PostUser2 |      1 | PostUser2 | verified |

  Scenario: Successfully create a post
    Given I am logged in as "PostUser1"
    And I am on the homepage

    When I fill in "Say something to the Community" with "This is a community post."
    And I select post visibility "Community"
    And I press "Post"

    Then I should see the success message "Your post has been posted."
    And I should see "This is a community post." in the "Main content front"
    And I should see "PostUser1" in the "Main content front"


  Scenario: Successfully comment on a post.
    Given posts:
      | field_post                |  author      | type | field_visibility | status | langcode |
      | This is a community post. |  PostUser1   | post | 1                | 1      | en       |

    When I am logged in as "PostUser2"
    And I am on the homepage
    And I fill in "Comment #1" for "Post comment"
    And I press "Comment"

    Then I should see the success message "Your comment has been posted."

  Scenario: Successfully edit comment on a post.
    Given posts:
      | field_post                |  author      | type | field_visibility | status | langcode |
      | This is a community post. |  PostUser1   | post | 1                | 1      | en       |
    And comments:
      | author    | target_type   | target_label               | status | subject                  | field_comment_body           | comment_type |
      | PostUser2 | post:post     | This is a community post.  | 1      | The comment subject      | This is a really cool topic! | post_comment |

    When I am logged in as "PostUser2"
    And I click the xth "0" element with the css ".comment .comment__actions .dropdown-toggle" in the "Main content"
    And I click "Edit"
    And I fill in "Comment #1 to be deleted" for "Post comment"
    And I press "Submit"

    Then I should see the success message "Your comment has been updated."

  Scenario: Successfully delete comment on a post.
    Given posts:
      | field_post                |  author      | type | field_visibility | status | langcode |
      | This is a community post. |  PostUser1   | post | 1                | 1      | en       |
    And comments:
      | author    | target_type   | target_label               | status | subject                  | field_comment_body           | comment_type |
      | PostUser2 | post:post     | This is a community post.  | 1      | The comment subject      | This is a really cool topic! | post_comment |

    When I am logged in as "PostUser2"
    And I am on the homepage
    And I click the xth "0" element with the css ".comment .comment__actions .dropdown-toggle" in the "Main content"
    And I click "Delete"
    And I should see "This action cannot be undone."
    And I press "Delete"
    And I wait for the batch job to finish

    Then I should see "The comment and all its replies have been deleted."
