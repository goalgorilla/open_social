@api @mentions @stability @DS-2647
Feature: Create Mention in a Post
  Benefit: In order to make sure that mentioned person will read this post
  Role: As a LU
  Goal/desire: I want to be able to mention a LU in a post

  Scenario: Successfully create mention in a post
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  |
      | user_3   | mail_3@example.com | 1      | Stephen                  | Hawking                 |
    And I am logged in as "user_1"
    And I am on the homepage
    And I fill in "Say something to the community" with "Hello [~user_2], [~user_3]!"
    And I press "Post"
    Then I should see "Albert Einstein posted"
    And I should see "Hello user_2, user_3!"
    And I should see the link "user_3"
    When I click "user_2"
    Then I should see "Albert Einstein mentioned Isaac Newton in a post"
    And I should see "Hello user_2, user_3!"
