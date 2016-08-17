@api @post @stability @perfect @critical @DS-244 @DS-245 @DS-247 @DS-248 @DS-674 @DS-676 @database
Feature: Create Post
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post
  Given users:
      | name      | status | pass |
      | PostCreateUser1 |      1 | PostCreateUser1 |
      | PostCreateUser2 |      1 | PostCreateUser2 |
    And I am logged in as "PostCreateUser1"
    And I am on the homepage
  And I should not see "PostCreateUser1" in the "Main content front"
  When I fill in "What's on your mind?" with "This is a public post."
    And I select post visibility "Public"
    And I press "Post"
   Then I should see the success message "Your post has been posted."
    And I should see "This is a public post."
    And I should see "PostCreateUser1" in the "Main content front"
    And I should be on "/stream"

        # Scenario: Succesfully create a private post
   When I fill in "What's on your mind?" with "This is a community post."
    And I select post visibility "Community"
    And I press "Post"
   Then I should see the success message "Your post has been posted."
    And I should see "This is a community post."
    And I should see "PostCreateUser1" in the "Main content front"
    And I should be on "/stream"

        # Scenario: edit the post
   When I click the xth "5" element with the css ".dropdown-toggle"
    And I click "Edit"
    And I fill in "What's on your mind?" with "This is a community post edited."
    And I press "Post"
   Then I should see the success message "Your post has been saved."

        # Scenario: See post on profile stream
   When I am on "/user"
    And I should see "This is a community post edited."
    And I should see "This is a public post."

        # Scenario: Post on someones profile stream
  Given I am on the profile of "PostCreateUser2"
   When I fill in "What's on your mind?" with "This is a post by PostCreateUser1 for PostCreateUser2."
    And I press "Post"
   Then I should see the success message "Your post has been posted."
    And I should see "This is a post by PostCreateUser1 for PostCreateUser2."
   When I go to the homepage
   Then I should not see "This is a post by PostCreateUser1 for PostCreateUser2."

        # TODO: Scenario: Succesfully delete a post

  Given I am an anonymous user
    And I am on the homepage
   Then I should see "This is a public post."
   Then I should not see "This is a community post."
