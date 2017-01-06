@api @mentions @stability @DS-2649
Feature: Create Mention in a Comment
  Benefit: In order to make sure that mentioned person will read the comment
  Role: As a LU
  Goal/desire: I want to be able to mention a LU in a comment

  Scenario: Successfully create mention in a comment
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  |
      | user_3   | mail_3@example.com | 1      | Stephen                  | Hawking                 |
    And I am logged in as "user_1"
    And I am viewing a "topic" with the title "Mention in a comment test topic 2"
    When I fill in the following:
      | Add a comment | [~user_2], [~user_3], see my comment. |
    And I press "Comment"
    And I should see "user_2, user_3, see my comment." in the "Main content"
    And I should see the link "user_2"
    When I click "user_3"
    Then I should see "Albert Einstein mentioned you in a comment"
    And I should see "user_2, user_3, see my comment."
