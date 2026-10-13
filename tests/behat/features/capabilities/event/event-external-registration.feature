@api
Feature: External registration for events
  Benefit: Attendees register on the organiser's own registration page
  Role: As a Verified
  Goal/desire: I want an event to send attendees to an external registration link

  @verified @javascript
  Scenario: Event organiser must provide a valid registration link
    Given I am logged in as an "verified"
    And events authored by current user:
      | title                   | body                | field_event_date | field_event_date_end | status | field_content_visibility | field_event_enroll |
      | Externally hosted event | this is description | +8 days          | +9 days              | 1      | public                   | 1                  |

    When I am editing the event "Externally hosted event"
    And I select the first option from "field_event_type" when that field is required
    And I show hidden checkboxes
    And I check the box "Use external registration"
    And I press "Save"

    Then I should see "Enter a registration URL to use external registration."

    And I fill in "field_event_registration_url[0][uri]" with "ftp://lu.ma/govtech-event"
    And I press "Save"

    And I should see "Enter a valid registration URL starting with http:// or https://."

    And I fill in "field_event_registration_url[0][uri]" with "https://lu.ma/govtech-event"
    And I press "Save"
    And I am viewing the event "Externally hosted event"

    And I should see the link "Register for event"
    And the link "Register for event" should contain URL "https://lu.ma/govtech-event"
    And I should not see the button "Enroll"

  @verified @security
  Scenario: Externally registered events replace native enrolment
    Given users:
      | name            | pass            | mail                        | status | roles    |
      | event_organiser | event_organiser | event_organiser@example.com | 1      | verified |
      | event_visitor   | event_visitor   | event_visitor@example.com   | 1      | verified |
    # This site can ship with native enrolment switched off. Turn it on so the
    # native event below shows the enrolment action.
    And I set the configuration item "social_event.settings" with key "disable_event_enroll" to 0
    And events:
      | title                   | body                | author          | field_event_date | field_event_date_end | status | field_content_visibility | field_event_enroll | field_external_registration | field_event_registration_url |
      | Externally hosted event | this is description | event_organiser | +8 days          | +9 days              | 1      | public                   | 1                  | 1                           | https://lu.ma/govtech-event  |
      | Natively hosted event   | this is description | event_organiser | +8 days          | +9 days              | 1      | public                   | 1                  | 0                           |                              |

    When I am logged in as "event_visitor"
    And I am viewing the event "Externally hosted event"

    Then I should see the link "Register for event"
    And the link "Register for event" should contain URL "https://lu.ma/govtech-event"
    And I should not see the button "Enroll"

    And I am viewing the event "Natively hosted event"

    And I should see the button "Enroll"
    And I should not see the link "Register for event"

    And I am logged in as "event_organiser"
    And I am viewing the event "Externally hosted event"

    And I should not see the link "Manage enrollments"

    And I am viewing the event manager page for "Externally hosted event"

    And I should see "Access denied"

  @AN
  Scenario: Anonymous visitors see the external registration link
    Given events with non-anonymous author:
      | title                   | body                | field_event_date | field_event_date_end | status | field_content_visibility | field_external_registration | field_event_registration_url |
      | Externally hosted event | this is description | +8 days          | +9 days              | 1      | public                   | 1                           | https://lu.ma/govtech-event  |
    And I am an anonymous user

    When I am viewing the event "Externally hosted event"

    Then I should see the link "Register for event"
    And the link "Register for event" should contain URL "https://lu.ma/govtech-event"
