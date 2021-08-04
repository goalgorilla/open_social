@api @notifications @stability @DS-4323 @notification-emails
Feature: Receive email notifications and choose frequency
  Benefit: Email notifications attract users to the platform
  Role: As a LU
  Goal/desire: I want to be able to receive email notifications and configure their frequency

  @email-spool
  Scenario: Send direct email notification for an activity
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as "user1"
    And I am on the homepage
    And the cache has been cleared
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
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click "Email notifications"
    And I wait for "2" seconds
    And I click "Message to me"
    And I wait for "2" seconds
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
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
      | user3   | mail_user3@example.com | 1      | Thomas                   | Miller                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click "Email notifications"
    And I wait for "2" seconds
    And I click "Message to me"
    And I wait for "2" seconds
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
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name    | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1   | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2   | mail_user2@example.com | 1      | Cathy                    | Willis                  |
      | user3   | mail_user3@example.com | 1      | Thomas                   | Miller                  |
    And I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click "Email notifications"
    And I wait for "2" seconds
    And I click "Message to me"
    And I wait for "2" seconds
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

  @email-spool
  Scenario: See if the queue item is processed or stuck after cron run.
    Given I am logged in as an "authenticated user"
    And I run cron
    And I wait for the queue to be empty
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Create topic"
    And I should see "Topic This is a test topic has been created."
    And I click "Edit content"
    And I click "Delete"
    And I should see "This action cannot be undone."
    And I press "Delete"
    And I run cron
    And I check if queue items processed "activity_logger_message"

  @email-spool
  Scenario: User should not receive notification as default
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name  | mail                   | status | field_profile_first_name | field_profile_last_name |
      | user1 | mail_user1@example.com | 1      | Christopher              | Conway                  |
      | user2 | mail_user2@example.com | 1      | Cathy                    | Willis                  |
    And I am logged in as an "sitemanager"
    And I go to "/admin/config/opensocial/swiftmail"
    And I press "Default email notification settings"
    And I click radio button "Never" with the id "edit-create-mention-post-none"
    And I press "Save configuration"

    Given I am logged in as "user1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click "Email notifications"
    And I wait for "2" seconds
    And I click "Message to me"
    And I should see "Never" in the "select[name='email_notifications[message_to_me][create_mention_post]'] option[selected='selected']" element

    Given I am logged in as "user2"
    And I am on the homepage
    And I fill in "Say something to the Community" with "You're not going to be notified of this [~user1]!"
    And I press "Post"
    And I press "Post"
    And I wait for the queue to be empty
    Then I should not have an email with subject "Notification from Open Social" and "Cathy Willis mentioned you" in the body
