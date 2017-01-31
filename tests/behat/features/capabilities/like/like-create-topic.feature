@api @like @stability @DS-2969
Feature: Create event like
  Benefit: In order to like an event
  Role: As a LU
  Goal/desire: I want to be able to like an event

  Scenario: Successfully create mention in a post
   Given users:
     | name     | mail               | status | field_profile_first_name | field_profile_last_name |
     | user_1   | mail_1@example.com | 1      | Marie                    | Curie                   |
     | user_2   | mail_2@example.com | 1      | Charles                  | Darwin                  |
     And I am logged in as "user_1"
     And I am on "user"
     And I click "Topics"
     And I click "Create Topic"

    When I fill in the following:
      | Title | Topic for likes |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
     And I click radio button "Discussion"
     And I press "Save"
    Then I should see "Topic for likes has been created."

   Given I am logged in as "user_2"
    Then I should see "Topic for likes"
     And I should see "Marie Curie"
    When I click "Topic for likes"
    Then I should see "Topic for likes"
     And I click the xth "0" element with the css ".vote-like a"
     And I wait for AJAX to finish

    Given I am logged in as "user_1"
      And I click the xth "0" element with the css ".notification-bell a"
     Then I should see "Notification centre"
      And I should see "Charles Darwin likes your content"
