@api @topic @stability @perfect @critical @DS-2311 @stability-3
Feature: Follow Content
  Benefit: In order receive (email) notification  when a new comments or reply has been placed
  Role: As a LU
  Goal/desire: I want to be able to subscribe to content

  Scenario: Follow content
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Marie                    | Curie                   |
      | user_2   | mail_2@example.com | 1      | Charles                  | Darwin                  |
    Given topics:
      | title            | description           | author | type        | language |
      | Topic for follow | Body description text | user_1 | Discussion  | en       |

    When I am logged in as "user_2"
     And I am on "/all-topics"
    Then I should see "Topic for follow"

    When I click "Topic for follow"
    Then I should see "Topic for follow" in the "Hero block"
     And I should see "Body description text" in the "Main content"
     And I should see the link "Follow content" in the "Main content"
     And I should not see the link "Unfollow content" in the "Main content"

    When I click "Follow content"
     And I wait for AJAX to finish
    Then I should see the link "Unfollow content" in the "Main content"
     And I should not see the link "Follow content" in the "Main content"

    When I click "Unfollow content"
     And I wait for AJAX to finish
    Then I should see the link "Follow content" in the "Main content"
     And I should not see the link "Unfollow content" in the "Main content"

