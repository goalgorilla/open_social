@api @post @javascript @stability @perfect @critical @database @post-emoji
Feature: Post and Comment with Emoji
  Benefit: Add an emoji in a post and/or comment
  Role: As a Verified
  Goal/desire: I should be able to add an emoji

  Scenario: Successfully create and see a post/comment with emoji
    Given I enable the module "social_emoji"

    Given users:
        | name      | status | pass      | roles    |
        | PostUser1 |      1 | PostUser1 | verified |
      And I am logged in as "PostUser1"
      And I am on the homepage
      And I should see the button "Pick an emoji"
