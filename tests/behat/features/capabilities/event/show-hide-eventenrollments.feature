@api @event @eventenrollment @stability @perfect @PROD-22857 @stability-2
Feature: Show/hide enrollments on Event
  Benefit: Ability to manage enrollments displaying on event
  Role: As a Verified
  Goal/desire: I want to show/hide enrollments on Event

  @verified @security
  Scenario: Event author can successfully see hidden enrollments
    Given I am logged in as an "verified"
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | body                     | this is description    |
      | field_event_date         | +8 days                |
      | field_event_date_end     | +9 days                |
      | status                   | 1                      |
      | field_content_visibility | public                 |
      | field_hide_enrollments   | 1                      |

    And I should see an "#block-socialblue-views-block-event-enrollments-event-enrollments-socialbase" element
    And I should see the link "Manage enrollments"
    When I click "Manage enrollments"
    Then I should see the text "0 Enrollees"

  @verified @security
  Scenario: Verified can not see hidden enrollments
    And users:
      | name            | pass            | mail                          | status | roles        |
      | event_creator   | event_organiser | event_organiser@example.com   | 1      | verified     |
      | event_visitor  | event_visitor  | event_visitor@example.com       | 1      | verified     |

    Given I am logged in as "event_creator"
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | body                     | this is description    |
      | field_event_date         | +8 days                |
      | field_event_date_end     | +9 days                |
      | status                   | 1                      |
      | field_content_visibility | public                 |
      | field_hide_enrollments   | 1                      |

    Given I am logged in as "event_visitor"
    Given I open the "event" node with title "My Behat Event created"
    Then I should see "My Behat Event created"
    And I should see the button "Enroll"
    And I should not see an "#block-socialblue-views-block-event-enrollments-event-enrollments-socialbase" element
    And I should not see the link "Enrollments"

    # Make enrollments visible for verified again.
    Given I am logged in as "event_creator"
    Then I open the "event" node with title "My Behat Event created"
    Then I click "Edit content"
    Then I uncheck the box "Hide enrollments"
    And I press "Save"
    Then I should see "Event My Behat Event created has been updated."

    Given I am logged in as "event_visitor"
    Given I open the "event" node with title "My Behat Event created"
    Then I should see "My Behat Event created"
    And I should see the button "Enroll"
    And I should see an "#block-socialblue-views-block-event-enrollments-event-enrollments-socialbase" element
    And I should see the link "Enrollments"
    When I click "Enrollments"
    Then I should see "No one has enrolled for this event"

  @AN @security
  Scenario: Anonymous can not see hidden enrollments
    Given event content:
      | title                  | field_event_date  | status | field_content_visibility | field_event_an_enroll | field_hide_enrollments |
      | My Behat Event created | +8 days           | 1      | public                   | 1                     | 1                      |
    Given I am an anonymous user
    When I open the "event" node with title "My Behat Event created"
    Then I should see "My Behat Event created"
    And I should not see an "#block-socialblue-views-block-event-enrollments-event-enrollments-socialbase" element
    And I should not see the link "Enrollments"
