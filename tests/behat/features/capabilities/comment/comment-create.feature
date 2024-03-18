@api @comment @stability @DS-459 @topic @stability-2 @comment-create
Feature: Create Comments
  Benefit: Participate in discussions on the platform
  Role: As a Verified
  Goal/desire: I want to create and see a comment

  @verified @perfect
  Scenario: Successfully create and see a comment
    Given posts with non-anonymous owner:
      | field_post  | type | field_visibility | status | langcode |
      | Hello World | post | 0                | 1      | en       |
    And I run cron
    And I am logged in as a user with the verified role

    Then I should see an ".comment-post-comment-form" element

    Given I am viewing a "topic" with the title "Comment test topic"

    When I fill in the following:
         | Add a comment | This is a test comment |
    And I press "Comment"
    And I should see the success message "Your comment has been posted."
    And I should see the heading "Comments (1)" in the "Main content"
    And I should see "This is a test comment" in the "Main content"
    And I should see "second"
    And I should see "ago"

    # Now try visit a topic as authenticated user.
    Given I disable that the registered users to be verified immediately

    When I am logged in as an "authenticated user"

    Then I should not see an ".comment-post-comment-form" element
      And I enable that the registered users to be verified immediately
