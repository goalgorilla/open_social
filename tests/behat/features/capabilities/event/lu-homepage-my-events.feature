@api
Feature: See my upcoming events
  Benefit: In order to know which events I signed up for
  Role: As a Verified
  Goal/desire: I want to see an overview of upcoming events on my profile

  Scenario: Successfully show my upcoming events as a Verified
    # @todo This test relies on the old layout.
    Given the theme is set to old
    And I am on the homepage
    And I should not see "My upcoming events"

    And I am logged in as an "verified"
    And I should see "My upcoming events"
    And I should see "No upcoming events"

    And I am viewing my event:
      | title            | My Behat Event created |
      | field_event_date | +8 days                |
      | status           | 1                      |

    And I am viewing an event:
      | title            | My Behat Event enrolled |
      | field_event_date | +8 days                 |
      | status           | 1                       |

    And I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    And I should see "Enrolled"

    And I go to the homepage
    And I should not see "My Behat Event created" in the ".view-display-id-block_my_upcoming_events" element
    And I should see "My Behat Event enrolled" in the ".view-display-id-block_my_upcoming_events" element
    And I should see "Enrolled" in the ".view-display-id-block_my_upcoming_events" element

    And I am at "user"
    And I click "Events"
    And I should see "Events for this user"
    And I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"

    And I am at "user"
    And I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"
    And I should see "enrolled"
