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
    And I should see the link "Sign up"
    And I should see the link "Sign up as guest"
    When I click "Sign up as guest"
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
    When I click "Sign up as guest"
    And I fill in the following:
      | First name    | John  |
      | Last name     | Doe   |
      | Email address | john@doe.com |
    And I press "Enroll in event"
    Then I should see the success message "You have successfully enrolled to this event. You have also received a notification via email."
    Given I open the "event" node with title "AN Event 1"
    When I click "Enroll"
    And I wait for AJAX to finish
    When I click "Sign up as guest"
    And I fill in the following:
      | First name    | John  |
      | Last name     | Doe   |
      | Email address | john@doe.com |
    And I press "Enroll in event"
    Then I should see the success message "You have been already enrolled to this event. You have also received a notification via email"
    # AS CM+ I should see Anonymous enrollments.
    Given I am logged in as a user with the "contentmanager" role
    And I open the "event" node with title "AN Event 1"
    And I click "Anonymous enrollment"
    Then I should see "John Doe"
    And I should see "john@doe.com"
    # AS LU I should not see Anonymous enrollments emails
    Given I am logged in as an "authenticated user"
    And I open the "event" node with title "AN Event 1"
    Then I should not see "Anonymous enrollment"
