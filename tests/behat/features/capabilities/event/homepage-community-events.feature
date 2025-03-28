@api
Feature: See upcoming events in the community
  Benefit: In order to know which events I can join
  Role: As a Verified
  Goal/desire: I want to see upcoming events of the community on the homepages

  Scenario: Successfully show my upcoming events as a Verified
#    TODO: Test visibility settings (Public, Community)

    Given I am on the homepage
    And I should not see "Behat Event 1"
    And I should not see "Behat Event 2"

    And event content:
      | title         | field_event_date | status | field_content_visibility |
      | Behat Event 1 | +10 minutes      | 1      | public                   |
      | Behat Event 2 | +20 minutes      | 1      | public                   |

    And I am on the homepage

    And I should see "Upcoming events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    And I am at "community-events"
    And I should see "All events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"
    And I should see "Ongoing and upcoming events"

    And I am logged in as an "verified"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    And I click "All Upcoming events"
    And I should see "All events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    And I click radio button "Ongoing and upcoming events"
    And I press "Filter"
    And "Behat Event 1" should precede "Behat Event 2" for the query ".teaser__title"

    And event content:
      | title         | field_event_date | status | field_content_visibility |
      | Behat Event 1 | -10 minutes      | 1      | public                   |
      | Behat Event 2 | -20 minutes      | 1      | public                   |

    And I click radio button "Past events"
    And "Behat Event 1" should precede "Behat Event 2" for the query ".teaser__title"
