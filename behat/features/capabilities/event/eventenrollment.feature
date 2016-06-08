@api @event @eventenrollment @stability @perfect @DS-479 @profile
Feature: Enroll for an event
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to enroll for an Event

  @LU @critical
  Scenario: Successfully enroll for an event
    Given I am logged in as an "authenticated user"
    When I am viewing a "event" with the title "Enrollment test event"
    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Enrollments"

    When I press the "Enroll" button
    Then I should see the button "Enrolled"
    And I should see "1 people have enrolled"
    And I should see the link "View all"

    When I click "View all"
    Then I should see the button "Enrolled"
    And I should see the link "Enrollments"
    And I should see "View profile"
    And I should see "Contact me"

  @AN
  Scenario: Successfully redirect an AN from an event enrollment action
    Given users:
      | name            | pass            | mail                        | status |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      |
    Given I am logged in as an "authenticated user"
    And I am on "node/add/event"
    When I fill in the following:
      | Title         | Enrollment redirect test event |
      | Date          | 2025-01-01 |
      | Time          | 11:00:00 |
      | Location name | GG HQ |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click radio button "Public - visible to everyone including people who are not a member"
    And I press "Save"
    And I logout
    Given I open the "event" node with title "Enrollment redirect test event"
    Then I should see "Enrollment redirect test event"

    When I press the "Enroll" button
    Then I should see "Please log in or create a new account so that you can enroll to the event"
    And I should see "Log in"

    When I fill in "eventenrollment" for "Username or email address"
    And I fill in "eventenrollment" for "Password"
    And I press "Log in"
    Then I should see "Enrollment redirect test event"

    When I press the "Enroll" button
    Then I should see the button "Enrolled"
    And I should see "1 people have enrolled"
    And I should see the link "View all"

  @LU
  Scenario: Successfully cancel enrollment for an event
    Given I am logged in as an "authenticated user"
    When I am viewing a "event" with the title "Enrollment test event"
    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Enrollments"

    When I press the "Enroll" button
    Then I should see the button "Cancel enrollment"
    And I should see "1 people have enrolled"
    And I should see the link "View all"

    When I press the "Enroll" button
    And I press "Cancel enrollment"
    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Enrollments"

    # Enroll again, since this is technically something different.
    When I press the "Enroll" button
    Then I should see "1 people have enrolled"
    And I should see the link "View all"

  @LU @cache
  Scenario: Successfully changed enrollment and see changes in teaser
    Given users:
      | name            | pass            | mail                        | status |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      |
    When I am logged in as "eventenrollment"
    And I am viewing my event:
      | title            | Enrollment test event |
      | field_event_date | 3014-10-17 8:00am     |
      | status           | 1                     |
    And I click "eventenrollment"
    And I click "Events"
    Then I should not see "Enrolled"

    When I click "Enrollment test event"
    And I press the "Enroll" button
    Then I should see the button "Enrolled"
    And I click "eventenrollment"
    And I click "Events"
    Then I should see "Enrolled"
