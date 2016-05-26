@wip @api @post @stability @perfect @critical @DS-244 @DS-245 @DS-247 @DS-248 @DS-674 @DS-676 @database
Feature: Create Post
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post
  Given users:
      | name      | status | pass |
      | PostUser1 |      1 | PostUser1 |
      | PostUser2 |      1 | PostUser2 |
    And I am logged in as "PostUser1"
    And I am on the homepage
  And I should not see "PostUser1" in the "Main content"
  When I fill in "Post" with "This is a public post."
    And I select post visibility "Public"
    And I press "Save"
   Then I should see the success message "Created the Post."
    And I should see "This is a public post."
    And I should see "PostUser1" in the "Main content"
    And I should be on "/stream"

        # Scenario: Succesfully create a private post
   When I fill in "Post" with "This is a community post."
    And I select post visibility "Community"
    And I press "Save"
   Then I should see the success message "Created the Post."
    And I should see "This is a community post."
    And I should see "PostUser1" in the "Main content"
    And I should be on "/stream"

        # Scenario: edit the post
   When I click the xth "2" element with the css ".dropdown-toggle"
    And I click "Edit"
    And I fill in "Post" with "This is a community post edited."
    And I press "Save"
   Then I should see the success message "Saved the Post."

        # Scenario: See post on profile stream
   When I am on "/user"
    And I should see "This is a community post edited."
    And I should see "This is a public post."

        # Scenario: Post on someones profile stream
  Given I am on the profile of "PostUser2"
   When I fill in "Post" with "This is a post by PostUser1 for PostUser2."
    And I press "Save"
   Then I should see the success message "Created the Post."
    And I should see "This is a post by PostUser1 for PostUser2."
   When I go to the homepage
   Then I should not see "This is a post by PostUser1 for PostUser2."

        # Scenario: Succesfully delete a post
   When I click the xth "2" element with the css ".dropdown-toggle"
    And I fill in "Post" with "This is a post to be deleted."
    And I select post visibility "Community"
    And I press "Save"
   Then I should see the success message "Created the Post."
    And I should be on "/stream"

   Given I am an anonymous user
    And I am on the homepage
   Then I should see "This is a public post." in the ".stream-card" element
   Then I should not see "This is a community post."
