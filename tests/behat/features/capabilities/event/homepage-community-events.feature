@api @event @stability @perfect @community @upcoming @overview @block @verified @critical @DS-1056 @stability-2 @homepage-community-events
Feature: See upcoming events in the community
  Benefit: In order to know which events I can join
  Role: As a Verified
  Goal/desire: I want to see upcoming events of the community on the homepages

  Scenario: Successfully show my upcoming events as a Verified
#    TODO: Test visibility settings (Public, Community)

    Given I am on the homepage
    Then I should not see "Behat Event 1"
    And I should not see "Behat Event 2"

    Given events:
      | title         | field_event_date | field_event_date_end | status | field_content_visibility | body |
      | Behat Event 1 | +10 minutes      | +15 minutes          | 1      | public                   | foo  |
      | Behat Event 2 | +20 minutes      | +25 minutes          | 1      | public                   | foo  |

    Given I am on the homepage

    Then I should see "Upcoming events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    When I am at "community-events"
    Then I should see "All events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"
    And I should see "Ongoing and upcoming events"

    Given I am logged in as an "verified"
    Then I should see "Behat Event 1"
    And I should see "Behat Event 2"

    When I click "All Upcoming events"
    Then I should see "All events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    When I click radio button "Ongoing and upcoming events"
    And I press "Filter"
    And "Behat Event 1" should precede "Behat Event 2" for the query ".teaser__title"

    Given events:
      | title         | field_event_date | field_event_date_end | status | field_content_visibility | body |
      | Behat Event 1 | -10 minutes      | -5 minutes           | 1      | public                   | foo  |
      | Behat Event 2 | -20 minutes      | -15 minutes          | 1      | public                   | foo  |

    When I click radio button "Past events"
    And "Behat Event 1" should precede "Behat Event 2" for the query ".teaser__title"
