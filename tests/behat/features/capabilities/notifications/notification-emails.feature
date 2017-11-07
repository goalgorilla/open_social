@api @notifications @stability @DS-4323
Feature: Receive email notifications and choose frequency
  Benefit: Email notifications attract users to the platform
  Role: As a LU
  Goal/desire: I want to be able to receive email notifications and configure their frequency

  @email-spool
  Scenario: Send direct email notification for an activity
    Given users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as "user1"
    And I am on the homepage
    And I fill in "Say something to the Community" with "Hello [~user2]!"
    And I press "Post"
    And I wait for the queue to be empty
    Then I should have an email with subject "Notification from Open Social" and in the content:
      | content                                           |
      | Hi Cathy Willis                                   |
      | Christopher Conway mentioned you in a post        |
      | the notification above is sent to you Immediately |

  @email-spool
  Scenario: User is able to get no emails for activities if he so desires
    Given users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I select "none" from "A person mentioned me in a post"
    And I press "Save"

    Given I am logged in as "user2"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"
    And I wait for the queue to be empty
    Then I should not have an email with subject "Notification from Open Social" and "Cathy Willis mentioned you" in the body

  @email-spool
  Scenario: User is able to set a daily mail for activities if he so desires
    Given users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
      | user3   | mail_user3@example.com | 1      | Thomas                   | Miller                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I select "daily" from "A person mentioned me in a post"
    And I press "Save"

    Given I am logged in as "user2"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"

    Given I am logged in as "user3"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"

    Given I wait for the queue to be empty
    And I run the "daily" digest cron
    And I wait for the queue to be empty
    Then I should have an email with subject "Notification from Open Social" and in the content:
      | content                                                    |
      | Hi Christopher Conway                                      |
      | Cathy Willis mentioned you in a post                       |
      | Thomas Miller mentioned you in a post                      |
      | the notifications above are sent to you as a Daily mail    |

  @email-spool
  Scenario: User is able to set a weekly mail for activities if he so desires
    Given users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
      | user3   | mail_user3@example.com | 1      | Thomas                   | Miller                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I select "weekly" from "A person mentioned me in a post"
    And I press "Save"

    Given I am logged in as "user2"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"

    Given I am logged in as "user3"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"

    Given I wait for the queue to be empty
    And I run the "weekly" digest cron
    And I wait for the queue to be empty
    Then I should have an email with subject "Notification from Open Social" and in the content:
      | content                                                    |
      | Hi Christopher Conway                                      |
      | Cathy Willis mentioned you in a post                       |
      | Thomas Miller mentioned you in a post                      |
      | the notifications above are sent to you as a Weekly mail   |
