@api @javascript
Feature: Create topic like
  Benefit: In order to like a topic
  Role: As a Verified
  Goal/desire: I want to be able to like a topic

  Scenario: Successfully like a topic
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | roles    |
      | user_1   | mail_1@example.com | 1      | Marie                    | Curie                   | verified |
      | user_2   | mail_2@example.com | 1      | Charles                  | Darwin                  | verified |
    And I am logged in as "user_1"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"

    When I fill in the following:
      | Title | Topic for likes |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I check the box "News"
    And I press "Create topic"
    And I should see "Topic for likes has been created."

    And I am logged in as "user_2"
    And I am at "all-topics"
    And I should see "Topic for likes"
    And I should see "Marie Curie"
    And I click "Topic for likes"
    And I should see "Topic for likes"
    And I click the xth "0" element with the css ".vote-like a"
    And I wait for AJAX to finish

    And I am logged in as "user_1"
    And I wait for the queue to be empty
    And I click the xth "0" element with the css ".notification-bell a"

    Then I should see "Notification center"
    And I should see "Charles Darwin likes your topic"
