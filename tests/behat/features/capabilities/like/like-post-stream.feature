@api
Feature: Like post stream
  Benefit: In order to like a post in the stream
  Role: As a Verified
  Goal/desire: I want to be able to like a post in the stream

  @verified
  Scenario: Like a post in the stream
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | roles    |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                | verified |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  | verified |

    And I am logged in as "user_1"
    And I am on the profile of "user_2"
    And I fill in "Leave a message to Isaac Newton" with "This is a post by Albert Einstein for Isaac Newton."
    And I press "Post"
    And I should see the success message "Your post has been posted."

    And I am logged in as "user_2"
    And I am on "/user"
    And I should see "This is a post by Albert Einstein for Isaac Newton."
    And I click the xth "0" element with the css ".vote-like a"
    And I wait for AJAX to finish

    And I am logged in as "user_1"
    And I wait for the queue to be empty
    And I am on "/notifications"
    And I should see "Isaac Newton likes your post"

  @AN @like-post-stream-anonymous
  Scenario: As an anonymous user I want to see the amount of likes of public content
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | roles    |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                | verified |
    And I set the configuration item "system.site" with key "page.front" to "/stream"
    And I am logged in as "user_1"
    And I am on "/stream"
    And I fill in "Say something to the Community" with "This is a public post."
    And I select post visibility "Public"
    And I press "Post"
    And I should see the success message "Your post has been posted."
    And I should see "This is a public post."

    And I am an anonymous user
    And I am on the homepage
    And I should see "This is a public post."
    And I click the xth "0" element with the css ".vote-like a.disable-status"
    And the ".count" element should not contain "1"
    And the ".count" element should contain "0"
