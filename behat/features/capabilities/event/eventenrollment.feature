@api @event @eventenrollment @stability @DS-479
Feature: Enroll for an event
  Benefit: In order to attend an Event
  Role: LU
  Goal/desire: I want to enroll for an Event

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
