@api @event @stability @perfect @my @upcoming @profile @block @verified @critical @DS-1053 @stability-3 @lu-homepage-my-events
Feature: See my upcoming events
  Benefit: In order to know which events I signed up for
  Role: As a Verified
  Goal/desire: I want to see an overview of upcoming events on my profile

  Scenario: Successfully show my upcoming events as a Verified
    # @todo This test relies on the old layout.
    Given the theme is set to old
    Given I am on the homepage
    Then I should not see "My upcoming events"

    Given I am logged in as an "verified"
    Then I should see "My upcoming events"
    And I should see "No upcoming events"

    Given events authored by current user:
      | title                  | body | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event created | foo  | +8 days          | +9 days              | 1      | public                   |

    Given events with non-anonymous author:
      | title                   | body | field_event_date | field_event_date_end | status | field_content_visibility |
      | My Behat Event enrolled | foo  | +8 days          | +9 days              | 1      | public                   |
    And I am viewing the event "My Behat Event enrolled"

    When I press the "Enroll" button
    And I wait for AJAX to finish
    And I press the "Close" button
    Then I should see "Enrolled"

    When I go to the homepage
    Then I should not see "My Behat Event created" in the ".view-display-id-block_my_upcoming_events" element
    And I should see "My Behat Event enrolled" in the ".view-display-id-block_my_upcoming_events" element
    And I should see "Enrolled" in the ".view-display-id-block_my_upcoming_events" element

    When I am at "user"
    And I click "Events"
    Then I should see "Events for this user"
    And I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"

    When I am at "user"
    Then I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"
    And I should see "enrolled"
