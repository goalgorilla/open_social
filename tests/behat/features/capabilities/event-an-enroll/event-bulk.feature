@api @javascript @event @eventenrollment @stability @perfect @TB-5917 @profile @stability-3 @event-bulk
Feature: Event bulk actions
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to be able to use bulk actions for events enrollees

  @email-spool @event-bulk-emails
  Scenario: Successfully send bulk emails to enrollees
    Given I enable the module "social_event_an_enroll"

    Given users:
      | name             | mail                         | status |
      | event_author     | event_author@example.com     | 1      |
      | event_attendee_1 | event_attendee_1@example.com | 1      |
      | event_attendee_2 | event_attendee_2@example.com | 1      |
      | event_attendee_3 | event_attendee_3@example.com | 1      |

    Given event content:
      | title            | field_event_date | status | field_content_visibility | field_event_an_enroll | author       |
      | Bulk email Event | +2 days          | 1      | public                   | 1                     | event_author |

    # Add enrollees to the event directly by sitemanager.
    Given I am logged in as an "sitemanager"
    When I open the "event" node with title "Bulk email Event"
      And I should see "Bulk email Event" in the "Hero block"
      And I should see "Manage enrollments"
      And I click "Manage enrollments"
    Then I should see "Add enrollees"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    Then I should see "Add directly"
    When I click "Add directly"
    Then I should see "Find people by name or email address"
      And I fill in select2 input ".form-type-select" with "event_attendee_1@example.com" and select "event_attendee_1@example.com"
      And I press "Save"
    Then I should see "Add enrollees"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    Then I should see "Add directly"
    When I click "Add directly"
    Then I should see "Find people by name or email address"
      And I fill in select2 input ".form-type-select" with "event_attendee_2@example.com" and select "event_attendee_2@example.com"
      And I press "Save"
    Then I should see "Add enrollees"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    Then I should see "Add directly"
    When I click "Add directly"
    Then I should see "Find people by name or email address"
      And I fill in select2 input ".form-type-select" with "event_attendee_3@example.com" and select "event_attendee_3@example.com"
      And I press "Save"

    # Send bulk emails.
    Given I am logged in as "event_author"
    When I open the "event" node with title "Bulk email Event"
    Then I should see "Bulk email Event" in the "Hero block"
      And I should see "Manage enrollments"
    When I click "Manage enrollments"
      And I check the box "edit-select-all"
    Then I should see the button "Actions"
    When I click the xth "0" element with the css "#vbo-action-form-wrapper .dropdown .dropdown-toggle"
    Then I should see the link "Email selected enrollees"
    When I click "Email selected enrollees"
    Then I should see "Configure the email you want to send to the 3 enrollees you have selected."
    When I fill in the following:
      | Subject | Test subject |
      And I fill in the "edit-message-value" WYSIWYG editor with "Test message"
      And I press "Send email"
    Then I should see "Are you sure you want to send your email to to the following 3 enrollees?"
      And I press "Execute action"
      And I wait for AJAX to finish
      # And I wait for the queue to be empty
      And I run cron
      # Check if emails have been sent.
      And I should have an email with subject "Test subject" and in the content:
        | content             |
        | Hi event_attendee_1 |
        | Test message        |
      And I should have an email with subject "Test subject" and in the content:
        | content             |
        | Hi event_attendee_2 |
        | Test message        |
      And I should have an email with subject "Test subject" and in the content:
        | content             |
        | Hi event_attendee_3 |
        | Test message        |
