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
    And I should see the button "Enroll for this event"
    And I should see the link "Enrollments"

    When I press the "Enroll for this event" button
    Then I should see the button "Cancel enrollment"
    And I should see "You have enrolled for this event"
    And I should see "1 people have enrolled"
    And I should see the link "See all enrollments"

    When I click "See all enrollments"
    Then I should see the button "Cancel enrollment"
    And I should see "You have enrolled for this event"
    And I should see the link "Enrollments"
    And I should see "View profile"
    And I should see "Contact me"

  @AN
  Scenario: Successfully redirect an AN from an event enrollment action
    Given users:
      | name            | pass            | mail                        | status |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      |
    And I am viewing a "event" with the title "Enrollment redirect test event"
    Then I should see "Enrollment redirect test event"

    When I press the "Enroll for this event" button
    Then I should see "Please log in or create a new account so that you can enroll to the event"
    And I should see "Log in"

    When I fill in "eventenrollment" for "Username or email address"
    And I fill in "eventenrollment" for "Password"
    And I press "Log in"
    Then I should see "Enrollment redirect test event"

    When I press the "Enroll for this event" button
    Then I should see the button "Cancel enrollment"
    And I should see "You have enrolled for this event"
    And I should see "1 people have enrolled"
    And I should see the link "See all enrollments"

  @LU
  Scenario: Successfully cancel enrollment for an event
    Given I am logged in as an "authenticated user"
    When I am viewing a "event" with the title "Enrollment test event"
    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll for this event"
    And I should see the link "Enrollments"

    When I press the "Enroll for this event" button
    Then I should see the button "Cancel enrollment"
    And I should see "You have enrolled for this event"
    And I should see "1 people have enrolled"
    And I should see the link "See all enrollments"

    When I press "Cancel enrollment"
    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll for this event"
    And I should see the link "Enrollments"

    # Enroll again, since this is technically something different.
    When I press the "Enroll for this event" button
    Then I should see the button "Cancel enrollment"
    And I should see "You have enrolled for this event"
    And I should see "1 people have enrolled"
    And I should see the link "See all enrollments"

  @LU @cache
  Scenario: Successfully changed enrollment and see changes in teaser
    Given users:
      | name            | pass            | mail                        | status |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      |
    And I am logged in as "eventenrollment"
    And I am viewing my "event" with the title "Enrollment test event"
    And I click "eventenrollment"
    And I click "Events"
    Then I should not see "Enrolled"

    When I click "Enrollment test event"
    And I press the "Enroll for this event" button
    Then I should see the button "Cancel enrollment"
    And I click "eventenrollment"
    And I click "Events"
    Then I should see "Enrolled"
