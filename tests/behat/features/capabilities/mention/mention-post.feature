@api
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

    When I am logged in as "user_1"
    And I am on the homepage
    And I fill in "Say something to the Community" with "Hello [~user_2], [~user_3]!"
    And I select post visibility "Public"
    And I press "Post"
    And I should see "Albert Einstein posted"
    And I should see the link "user_2"
    And I should see the link "user_3"
    And I should see "Hello " in the ".social-post-album--post" element
    And I should see "user_2" in the ".social-post-album--post" element
    And I should see "user_3" in the ".social-post-album--post" element
    And I click "user_2"
    And I should see "Isaac Newton"

    # Test if the user gets a notification.
    And I am logged in as "user_2"
    And I wait for the queue to be empty
    And I am at "notifications"
    And I should see text matching "Albert Einstein mentioned you in a post"

    And I logout
    And I am on the homepage
    And I should not see the link "user_2"
    And I should not see the link "user_3"
    And I should see "Hello " in the ".social-post-album--post" element
    And I should see "user_2" in the ".social-post-album--post" element
    And I should see "user_3" in the ".social-post-album--post" element
