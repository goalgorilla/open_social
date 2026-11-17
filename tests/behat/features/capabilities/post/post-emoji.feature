@api @javascript
Feature: Post and Comment with Emoji
  Benefit: Add an emoji in a post and/or comment
  Role: As a Verified
  Goal/desire: I should be able to add an emoji

  Scenario: The emoji picker loads its emoji data from the site itself
    Given I enable the module "social_emoji"
    And users:
      | name      | status | pass      | roles    |
      | PostUser1 |      1 | PostUser1 | verified |
    And I am logged in as "PostUser1"
    And I am on the homepage

    When I open the emoji picker

    Then the emoji picker loads its emoji data from the site itself

  Scenario: Successfully create and see a post with emoji
    Given I enable the module "social_emoji"
    And users:
      | name      | status | pass      | roles    |
      | PostUser1 |      1 | PostUser1 | verified |
    And I am logged in as "PostUser1"
    And I am on the homepage

    When I open the emoji picker
    And I pick the emoji "😀" in the emoji picker
    And I select post visibility "Community"
    And I press "Post"

    Then I should see the success message "Your post has been posted."
    And I should see "😀" in the "Main content front"
