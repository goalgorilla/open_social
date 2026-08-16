@api
Feature: Enroll for an event
  Benefit: In order to attend an Event
  Role: As a Verified
  Goal/desire: I want to enroll for an Event

  @verified @critical
  Scenario: Successfully enroll for an event
    Given I am logged in as an "verified"
    And events authored by current user:
      | title                  | body                  | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event created | Body description text | +8 days          | +9 days              | 1      | community                |
    And I am viewing the event "My Behat Event created"

    And I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Manage enrollments"

    When I press the "Enroll" button
    And I wait for AJAX to finish

    Then I should see the text "Meetup: My Behat Event created" in the "Modal"
    And I should see the link "See who else is going" in the "Modal"
    And I press the "Close" button
    And I should see the button "Enrolled"

    And I reload the page
    And I should see "1 person has enrolled"
    And I should see the link "All enrollments"

    And I click "All enrollments"
    And I should see the button "Enrolled"

  @AN
  Scenario: Successfully redirect an AN from an event enrollment action
    Given users:
      | name            | pass            | mail                        | status | roles    |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      | verified |
    And I am logged in as an "verified"
    And events authored by current user:
      | title                          | body                   | field_event_date | field_event_date_end | status | field_content_visibility |
      | Enrollment redirect test event | Body description text. | +1 day           | +1 day               | 1      | public                   |
    And I am viewing the event "Enrollment redirect test event"

    And I am an anonymous user
    And I open the "event" node with title "Enrollment redirect test event"
    And I should see "Enrollment redirect test event"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I should see "Please log in or create a new account so that you can enroll to the event"
    And I should see "Log in"
    And I fill in "eventenrollment" for "Username or email address"
    And I fill in "eventenrollment" for "Password"
    And I press "Log in"
    And I should see "Enrollment redirect test event"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I should see the text "Meetup: Enrollment redirect test event" in the "Modal"
    And I should see the link "See who else is going" in the "Modal"
    And I press the "Close" button
    And I should see the button "Enrolled"
    And I reload the page
    And I should see "1 person has enrolled"
    And I should see the link "All enrollments"

  @verified
  Scenario: Successfully cancel enrollment for an event
    Given I am logged in as an "verified"

    When events authored by current user:
      | title                  | body                  | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event created | Body description text | +8 days          | +9 days              | 1      | community                |
    And I am viewing the event "My Behat Event created"

    Then I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Manage enrollments"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I should see the text "Meetup: My Behat Event created" in the "Modal"
    And I should see the link "See who else is going" in the "Modal"
    And I press the "Close" button
    And I should see the button "Enrolled"
    And I reload the page
    And I should see "1 person has enrolled"
    And I should see the link "All enrollments"
    And I press the "Enrolled" button
    And I press "Cancel enrollment"
    And I wait for AJAX to finish
    And I reload the page
    And I should see "No one has enrolled for this event"
    And I should see the button "Enroll"
    And I should see the link "Manage enrollments"
    # Enroll again, since this is technically something different.
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    And I reload the page
    And I should see "1 person has enrolled"
    And I should see the link "All enrollments"

  @verified @cache
  Scenario: Successfully changed enrollment and see changes in teaser
    Given users:
      | name            | pass            | mail                        | status | roles    |
      | eventenrollment | eventenrollment | eventenrollment@example.com | 1      | verified |

    When I am logged in as "eventenrollment"
    And events authored by current user:
      | title                 | body                  | field_event_date  | status | field_content_visibility |
      | Enrollment test event | Body description text | 3014-10-17 8:00am | 1      | public                   |
    And I am viewing the event "Enrollment test event"
    And I click "eventenrollment" in the "Main content"
    And I click "Events"

    Then I should not see "Enrolled"
    And I click "Enrollment test event"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    And I should see the button "Enrolled"
    And I click "eventenrollment" in the "Main content"
    And I click "Events"
    And I should see "Enrolled"

  @closed_enrollments
  Scenario: Can no longer enroll to an event when it has finished.
    Given I am logged in as an "verified"

    When events authored by current user:
      | title                  | body                  | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event created | Body description text | -3 days          | -2 days              | 1      | community                |
    And I am viewing the event "My Behat Event created"

    And I should see "No one has enrolled for this event"
    And I should see the button "Event has passed"

  Scenario: Showing the correct total enrollment count.
    Given events with non-anonymous author:
      | title        | body                  | field_content_visibility | field_event_date    | field_event_date_end    | langcode |
      | Test content | Body description text | community                | 2100-01-01T12:00:00 | 2100-01-01T12:00:00 | en       |
    And there are 13 event enrollments for the "Test content" event

    When I am logged in as an "verified"
    And I am viewing the event "Test content"

    Then I should see "13 people have enrolled"

  Scenario: Can enroll to the online meeting
    Given users:
      | name         | pass     | mail                 | status | roles       |
      | Enrollee     | enrollee | enrollee@example.com | 1      | verified    |

    And events with non-anonymous author:
      | title                | body        | field_event_all_day | field_event_date | field_event_date_end | field_content_visibility | meeting_type | meeting_url                   |
      | Future Online event  | lorem ipsum | 1                   | +1 day           | +1 day               | public                   | custom_link  | https://my.meeting.url        |
      | Present Online event | lorem ipsum | 1                   | +5 minutes       | +1 day               | public                   | custom_link  | https://www.getopensocial.com |

    When I am logged in as "Enrollee"

    Then I am viewing the event "Future Online event"
    And I should see the button "Enroll"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    And I should see the button "Join at the start time"
    And I press the "Join at the start time" button
    And I should see the button "Cancel enrollment"
    And the url should match "/node/\d+"

    And I am viewing the event "Present Online event"
    And I should see the button "Enroll"
    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    And I should see the button "Join now"
    And I press the "Join now" button
    And I wait for AJAX to finish
    # We need to check redirection only.
    And I should be on "https://www.getopensocial.com"
