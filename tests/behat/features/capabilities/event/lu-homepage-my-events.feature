@api @event @stability @perfect @my @upcoming @profile @block @LU @critical @DS-1053
Feature: See my upcoming events
  Benefit: In order to know which events I signed up for
  Role: LU
  Goal/desire: I want to see an overview of upcoming events on my profile

  Scenario: Successfully show my upcoming events as a LU
    Given I am on the homepage
    Then I should not see "My upcoming events"

    Given I am logged in as an "authenticated user"
    Then I should see "My upcoming events"
    And I should see "No upcoming events"

    Given I am viewing my event:
      | title            | My Behat Event created |
      | field_event_date | +8 days                |
      | status           | 1                      |

    And I am viewing an event:
      | title            | My Behat Event enrolled |
      | field_event_date | +8 days                 |
      | status           | 1                       |

    When I press the "Enroll" button
    Then I should see "Enrolled"

    When I go to the homepage
    Then I should not see "My Behat Event created" in the "Sidebar second"
    And I should see "My Behat Event enrolled" in the "Sidebar second"
    And I should see "Enrolled" in the "Sidebar second"

    When I am at "my-events"
    Then I should see "Events for this user"
    And I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"

    When I am at "user"
    Then I should see "My Behat Event created"
    And I should see "My Behat Event enrolled"
    And I should see "enrolled"