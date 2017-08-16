@api @DS-2936
  Feature: I want to visit another profile by clicking on their avatar
    Benefit: Better interaction upon viewing other's profiles
    Role: LU
    Goal/desire: I want to visit another profile by clicking on their avatar

  Scenario: Click an avatar from the stream, on an activity item, a node and a comment
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | user_1@example.com | 1      | Albert                   | Einstein                |
      | user_2   | user_2@example.com | 1      | Isaac                    | Newton                  |
    And I am logged in as "user_1"
    And I am on the homepage

  # Create a public post so we can test this for AN as well
    When I fill in "Say something to the Community" with "This is a public post."
    And I select post visibility "Public"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a public post."
    And I should see "Albert Einstein" in the "Main content front"
    And I should be on "/stream"

  # Click the avatar from the post in the stream
    Given I am logged in as "user_2"
    And I am on the homepage
    Then I click the xth "0" element with the css ".media-left"
    And I should see the heading "Albert Einstein"
    And I should see "Albert Einstein posted"
    And I should see "This is a public post."

  # Click the avatar as AN
    Given I am an anonymous user
    Then I click the xth "0" element with the css ".media-left"
    Then I should see "Access denied. You must log in to view this page."

  # Click the avatar of the comment
    Given I am logged in as "user_2"
    And I am on the homepage
    When I fill in "Comment #1" for "Post comment"
    And I press "Comment"
    Then I should see the success message "Your comment has been posted."
    And I should be on "/stream"

  # Check out the users profile that commented on the post by clicking their avatar in the comments
    Given I am logged in as "user_1"
    And I am on the homepage
    Then I click the xth "1" element with the css ".comment__avatar"
    And I should see the heading "Isaac Newton"
    And I should see "Isaac Newton commented on a post"

  # Create a topic so we can click the avatar of the author
    And I am logged in as "user_1"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Save"
    And I should see "Topic This is a test topic has been created."
    And I should see "This is a test topic" in the "Hero block"
    And I should see "Discussion" in the "Main content"
    And I should see "Body description text" in the "Main content"
    And I should not see "Enrollments"

  # Log in as another user to click the avatar of the created topic
    Given I am logged in as "user_2"
    And I am on the homepage
    And I click "This is a test topic"
    Then I click the xth "0" element with the css ".metainfo__avatar"
    And I should see the heading "Albert Einstein"