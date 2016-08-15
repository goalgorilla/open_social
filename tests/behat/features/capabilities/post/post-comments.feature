@api @post @stability @perfect @critical @DS-250 @DS-251 @DS-675 @database
Feature: Comment on a Post
  Benefit: In order to give my opinion on a post
  Role: As a LU
  Goal/desire: I want to comment on a post

  Scenario: Successfully create, edit and delete a comment on a post
  Given users:
      | name      | status | pass |
      | PostUser1 |      1 | PostUser1 |
      | PostUser2 |      1 | PostUser2 |
    And I am logged in as "PostUser1"
    And I am on the homepage

        # Scenario: Succesfully create a private post
   When I fill in "What's on your mind?" with "This is a community post."
    And I select post visibility "Community"
    And I press "Post"
   Then I should see the success message "Your post has been posted."
    And I should see "This is a community post." in the "Main content front"
    And I should see "PostUser1" in the "Main content front"
    And I should be on "/stream"

        # Scenario: Post a comment on this private post
  Given I am logged in as "PostUser2"
    And I am on the homepage
   When I fill in "Comment #1" for "field_comment_body[0][value]"
    And I press "Comment"
   Then I should see the success message "Your comment has been posted."

        # Scenario: edit comment
  When I click the xth "5" element with the css ".dropdown-toggle"
    And I click "Edit"
    And I fill in "Comment #1 to be deleted" for "field_comment_body[0][value]"
    And I press "Submit"
   Then I should see the success message "Your comment has been posted."

        # Scenario: delete comment
   When I am on the homepage
    And I click the xth "3" element with the css ".dropdown-toggle"
    And I click "Delete"
   Then I should see "This action cannot be undone."
        # Confirm delete
   When I press "Delete"
   Then I should see "The comment and all its replies have been deleted."

  Given I am on the homepage
   When I fill in "Comment #2" for "field_comment_body[0][value]"
    And I press "Comment"
   Then I should see the success message "Your comment has been posted."
   When I fill in "Comment #3" for "field_comment_body[0][value]"
    And I press "Comment"
   Then I should see the success message "Your comment has been posted."
    And I should see "Comment #3"
        #in the ".stream-card" element
    And I should see "Comment #2"
        #in the ".stream-card" element
  #@TODO And I should not see "Comment #1" in the ".stream-card" element
