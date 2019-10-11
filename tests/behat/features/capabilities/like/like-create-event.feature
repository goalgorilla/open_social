@api @like @stability @DS-2968 @stability-4 @like-create-event
Feature: Create event like
  Benefit: In order to like an event
  Role: As a LU
  Goal/desire: I want to be able to like an event

  Scenario: Successfully like an event
   Given users:
     | name     | mail               | status | field_profile_first_name | field_profile_last_name |
     | user_1   | mail_1@example.com | 1      | Albert                   | Einstein                |
     | user_2   | mail_2@example.com | 1      | Isaac                    | Newton                  |
     And I am logged in as "user_1"
     And I am on "user"
     And I click "Events"
     And I click "Create Event"

    When I fill in the following:
      | Title | Event for likes |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-date | 2025-01-01 |
      | Time | 11:00:00 |
      | Location name | GG HQ |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
     And I press "Save"
    Then I should see "Event for likes has been created."

   Given I am logged in as "user_2"
     And I open the "event" node with title "Event for likes"
    Then I should see "Event for likes"
     And I should see "Albert Einstein"
     And I click the xth "0" element with the css ".vote-like a"
     And I wait for AJAX to finish

    Given I am logged in as "user_1"
      And I wait for the queue to be empty
      And I click the xth "0" element with the css ".notification-bell a"
     Then I should see "Notification centre"
      And I should see "Isaac Newton likes your event"
