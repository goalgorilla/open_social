@api @javascript
Feature: Post and Comment with Emoji
  Benefit: Add an emoji in a post and/or comment
  Role: As a Verified
  Goal/desire: I should be able to add an emoji

  Scenario: Successfully create and see a post/comment with emoji
    Given I enable the module "social_emoji"

    And users:
      | name      | status | pass      | roles    |
      | PostUser1 |      1 | PostUser1 | verified |
    And I am logged in as "PostUser1"
    And I am on the homepage
    And I should see the button "Pick an emoji"

    When I press "Pick an emoji"
    Then the emoji picker loads its emoji data from the site itself

    When I pick the emoji "😀" in the emoji picker
    And I select post visibility "Community"
    And I press "Post"

    Then I should see the success message "Your post has been posted."
    And I should see "😀" in the "Main content front"
