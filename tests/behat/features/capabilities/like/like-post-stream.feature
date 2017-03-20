@api @like @stability @DS-2971
Feature: Like post stream
  Benefit: In order to like a post in the stream
  Role: As a LU
  Goal/desire: I want to be able to like a post in the stream

  @LU
  Scenario: Like a post in the stream
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  |

    Given I am logged in as "user_1"
    And I am on the profile of "user_2"
    And I fill in "Leave a message to Albert Einstein" with "This is a post by Albert Einstein for Isaac Newton."
    And I press "Post"
    Then I should see the success message "Your post has been posted."

    Given I am logged in as "user_2"
    And I am on "/user"
    Then I should see "This is a post by Albert Einstein for Isaac Newton."
    And I click the xth "0" element with the css ".vote-like a"
    And I wait for AJAX to finish

    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".notification-bell a"
    Then I should see "Notification centre"
    And I should see "Isaac Newton likes your post"
    And I click "Isaac Newton likes your post"

  @AN
  Scenario: As an anonymous user I want to see the amount of likes of public content
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
    Given I am logged in as "user_1"
    And I am on the homepage
    When I fill in "Say something to the community" with "This is a public post."
    And I select post visibility "Public"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a public post."
    And I should be on "/stream"

    Given I am an anonymous user
    And I am on "/stream"
    Then I should see "This is a public post."
    And I click the xth "0" element with the css ".vote-like a.disable-status"
    Then the ".count" element should not contain "1"
    And the ".count" element should contain "0"
