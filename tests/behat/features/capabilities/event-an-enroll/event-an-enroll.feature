@api @javascript @event @eventenrollment @stability @perfect @PAC-423 @profile @stability-3 @event-an-enroll
Feature: Enroll for an event without an account
  Benefit: In order to attend an Event
  Role: AN
  Goal/desire: I want to be able to enroll to an event without having to create an account

  @AN
  Scenario: Successfully enroll to an event as AN user
    Given I enable the module "social_event_an_enroll"
    Given event content:
      | title         | field_event_date | status | field_content_visibility | field_event_an_enroll |
      | AN Event 1    | +2 days          | 1      | public                   | 1                     |
    Given I open the "event" node with title "AN Event 1"
    Then I should see "AN Event 1" in the "Hero block"
    And I should see the link "Enroll" in the "Hero block"
    When I click "Enroll"
    And I wait for AJAX to finish
    And I should see the link "Log in"
    And I should see the link "Create an account"
    And I should see the link "Enroll as guest"
    When I click "Enroll as guest"
    Then I should see "AN Event 1" in the "Hero block"
    And I should not see the link "Enroll" in the "Hero block"
    And I fill in the following:
      | First name    | John  |
      | Last name     | Doe   |
      | Email address | john@doe.com |
    And I press "Enroll in event"
    Then I should see the success message "You have successfully enrolled to this event. You have also received a notification via email."
    # Cancel enrollment.
    And I should see "AN Event 1" in the "Hero block"
    Then I should see the button "Enrolled"
    When I press the "Enrolled" button
    And I click "Cancel enrollment"
    Then I should see the success message "You are no longer enrolled in this event. Your personal data used for the enrollment is also deleted."
    And I should see "AN Event 1" in the "Hero block"
    And I should see the link "Enroll" in the "Hero block"
    # Duplicate Enrollment.
    When I click "Enroll"
    And I wait for AJAX to finish
    When I click "Enroll as guest"
    And I fill in the following:
      | First name    | John  |
      | Last name     | Doe   |
      | Email address | john@doe.com |
    And I press "Enroll in event"
    Then I should see the success message "You have successfully enrolled to this event. You have also received a notification via email."
    Given I open the "event" node with title "AN Event 1"
    When I click "Enroll"
    And I wait for AJAX to finish
    When I click "Enroll as guest"
    And I fill in the following:
      | First name    | John  |
      | Last name     | Doe   |
      | Email address | john@doe.com |
    And I press "Enroll in event"
    Then I should see the success message "You have been already enrolled to this event. You have also received a notification via email"
    # AS CM+ I should see Guest enrollments.
    Given I am logged in as a user with the "contentmanager" role
    And I open the "event" node with title "AN Event 1"
    And I click "Manage enrollments"
    Then I should see "John Doe"
    # AS LU I should not see Guest enrollments emails
    Given I am logged in as an "authenticated user"
    And I open the "event" node with title "AN Event 1"
    Then I should not see "Manage enrollments"

  @AN
  Scenario: Control the site-wide default of AN enrollment
    Given I enable the module "social_event_an_enroll"
    And I am an anonymous user
    And I am viewing an event:
      | title                    | No guest enrollment |
      | field_event_date         | +3 days                 |
      | field_event_date_end     | +4 days                 |
      | field_content_visibility | public                  |
    When I press "Enroll"
    Then I should not see "Enroll as guest"

    ##
    ## In this test the vent must be created using the form because we are
    ## testing the effect of a hook_form_alter
    ##
    Given I set the configuration item "social_event_an_enroll.settings" with key "event_an_enroll_default_value" to 1
    And I am logged in as a user with the "authenticated user" role
    And I am on "node/add/event"
    When I fill in the custom fields for this "event"
    And I fill in the following:
      | Title                    | Anonymous event enrollment |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-date | 2025-01-02 |
      | Time          | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click radio button "Public"
    And I press "Save"

    Given I am an anonymous user
    And I open the "event" node with title "Anonymous event enrollment"
    When I click "Enroll"
    Then I should see "Enroll in Anonymous event Enrollment"
    And I should see "Enroll as guest"
