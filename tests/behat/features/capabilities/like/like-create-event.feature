@api @like @stability @DS-2968 @stability-4
Feature: Create event like
  Benefit: In order to like an event
  Role: As a LU
  Goal/desire: I want to be able to like an event

  Scenario: Successfully like an event
   Given users:
     | name     | mail               | status | field_profile_first_name | field_profile_last_name |
     | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
     | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  |
   Given events:
     | title           | description            | author | startdate           | enddate             | language |
     | Event for likes | Body description text. | user_1 | 2025-01-01 11:00:00 | 2025-01-01 12:00:00 | en       |
   Given I am logged in as "user_2"
     And I open the "event" node with title "Event for likes"
    Then I should see "Event for likes"
     And I should see "Albert Einstein"
     And I click the xth "0" element with the css ".vote-like a"
     And I wait for AJAX to finish

    Given I am logged in as "user_1"
      And I click the xth "0" element with the css ".notification-bell a"
     Then I should see "Notification centre"
      And I should see "Isaac Newton likes your event"
