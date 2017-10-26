@api @notifications @stability @DS-4323 @stability-3
Feature: Receive email notifications
  Benefit: Email notifications attract users to the platform
  Role: As a LU
  Goal/desire: I want to be able to receive email notifications

  @email-spool
  Scenario: Send email notifications for activities
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | mail_1@example.com | 1      | Christopher              | Conway                  |
      | user_2   | mail_2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as "user_1"
    And I am on the homepage
    And I fill in "Say something to the Community" with "Hello [~user_2], [~user_3]!"
    And I press "Post"
    And I wait for the queue to be empty
