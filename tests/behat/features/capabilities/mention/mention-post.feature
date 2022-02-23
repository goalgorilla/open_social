@api @mentions @stability @DS-2647 @stability-3 @mention-post
Feature: Create Mention in a Post
  Benefit: In order to make sure that mentioned person will read this post
  Role: As a Verified
  Goal/desire: I want to be able to mention a Verified in a post

  Scenario: Successfully create mention in a post
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | roles    |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                | verified |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  | verified |
      | user_3   | mail_3@example.com | 1      | Stephen                  | Hawking                 | verified |
    And I am logged in as "user_1"
    And I am on the homepage
    And I fill in "Say something to the Community" with "Hello [~user_2], [~user_3]!"
    And I select post visibility "Public"
    And I press "Post"
    Then I should see "Albert Einstein posted"
    And I should see "Hello user_2, user_3!"
    And I should see the link "user_3"
    When I click "user_2"
    Then I should see "Isaac Newton"

    # Test if the user gets a notification.
    When I am logged in as "user_2"
    And I wait for the queue to be empty
    And I am at "notifications"
    Then I should see text matching "Albert Einstein mentioned you in a post"

    When I logout
    And I am on the homepage
    And I should not see the link "user_2"
    And I should not see the link "user_3"
    Then I should see "Hello user_2, user_3!"
