@api
Feature: Create Post
  Benefit: In order to share knowledge with people
  Role: As a Verified
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post
  Given users:
    | name            | status | pass            | roles    |
    | PostCreateUser1 |      1 | PostCreateUser1 | verified |
    | PostCreateUser2 |      1 | PostCreateUser2 | verified |
  And I am logged in as "PostCreateUser1"
  And I am on the homepage
  And I should not see "PostCreateUser1" in the "Main content front"

  When I fill in "Say something to the Community" with "This is a public post."
  And I select post visibility "Public"
  And I press "Post"

  Then I should see the success message "Your post has been posted."
  And I should see "This is a public post."
  And I should see "PostCreateUser1" in the "Main content front"

  # Scenario: Succesfully create a private post
  And I fill in "Say something to the Community" with "This is a community post."
  And I select post visibility "Community"
  And I press "Post"
  And I should see the success message "Your post has been posted."
  And I should see "This is a community post."
  And I should see "PostCreateUser1" in the "Main content front"

  # Scenario: edit the post
  And I click the xth "1" element with the css ".dropdown-toggle" in the "Main content"
  And I click "Edit"
  And I fill in "Say something to the Community" with "This is a community post edited."
  And I press "Post"
  And I should see the success message "Your post has been saved."

  # Scenario: See post on profile stream
  And I am on "/user"
  And I should see "This is a community post edited."
  And I should see "This is a public post."

  # Scenario: Post on someones profile stream
  And I am on the profile of "PostCreateUser2"
  And I fill in "Leave a message to PostCreateUser2" with "This is a post by PostCreateUser1 for PostCreateUser2."
  And I press "Post"
  And I should see the success message "Your post has been posted."
  And I should see "This is a post by PostCreateUser1 for PostCreateUser2."
  And I go to the homepage
  And I should not see "This is a post by PostCreateUser1 for PostCreateUser2."

  # TODO: Scenario: Succesfully delete a post

  And I am an anonymous user
  And I am on the homepage
  And I should see "This is a public post."
  And I should not see "This is a community post."

  # LU should not be able to create posts.
  And I disable that the registered users to be verified immediately
  And I am logged in as an "authenticated user"
  And I am on the homepage
  And I should not see an ".form--post-create" element
  And I enable that the registered users to be verified immediately
