@api
Feature: Delete Post
  Benefit: In order to be able to delete content created
  Role: As a Verified
  Goal/desire: I want to delete Posts

  Scenario: Successfully delete post
    Given users:
      | name            | status | pass            | roles    |
      | PostCreateUser1 |      1 | PostCreateUser1 | verified |
      | PostCreateUser2 |      1 | PostCreateUser2 | verified |
    And I am logged in as "PostCreateUser1"
    And I am on the homepage

    When I fill in "Say something to the Community" with "This is a post made on the homepage."
    And I select post visibility "Public"
    And I press "Post"
    And I should see the success message "Your post has been posted."
    And I should see "This is a post made on the homepage."
    And I should see "PostCreateUser1" in the "Main content front"
    And I am on "/user"
    And I should see "This is a post made on the homepage."

    # Delete post
    Then I click the element with css selector ".btn.btn-icon-toggle.dropdown-toggle.waves-effect.waves-circle"
    And I click "Delete"
    And I should see "Are you sure you want to delete the post"
    And I should see "This action cannot be undone."
    And I press "Delete"
    And I wait for AJAX to finish
    And I should see "has been deleted."
    And I should not see "This is a post made on the homepage."
    And I am on the homepage
    And I should not see "This is a post made on the homepage."
    And I am logged in as "PostCreateUser2"
    And I am on the homepage
    And I should not see "This is a post made on the homepage."


