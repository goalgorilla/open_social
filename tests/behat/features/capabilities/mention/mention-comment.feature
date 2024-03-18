@api @mentions @stability @DS-2649 @stability-3 @mention-comment @no-update
Feature: Create Mention in a Comment
  Benefit: In order to make sure that mentioned person will read the comment
  Role: As a Verified
  Goal/desire: I want to be able to mention a Verified in a comment

  Scenario: Successfully create mention in a comment
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | roles    |
      | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                | verified |
      | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  | verified |
      | user_3   | mail_3@example.com | 1      | Stephen                  | Hawking                 | verified |
    And I am logged in as "user_1"
    And I am viewing my topic:
      | title                    | Mention in a comment test topic 2 |
      | status                   | 1                                 |
      | field_content_visibility | public                            |
    When I fill in the following:
      | Add a comment | [~user_2], [~user_3], see my comment. |
    And I press "Comment"
    And I should see "user_2, user_3, see my comment." in the "Main content"
    And I should see the link "user_2"
    When I click "user_3"
    Then I should see "Stephen Hawking"
    Then I logout
    And I am on "/all-topics"
    And I click "Mention in a comment test topic 2"
    And I should not see the link "user_2"
    And I should not see the link "user_3"
    And I should see "user_2, user_3, see my comment." in the "Main content"
#    Then I should see "Albert Einstein mentioned Stephen Hawking in a comment"
#    And I should see "user_2, user_3, see my comment."
