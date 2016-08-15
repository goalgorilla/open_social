@api @event @stability @perfect @community @upcoming @overview @block @LU @critical @DS-1056
Feature: See upcoming events in the community
  Benefit: In order to know which events I can join
  Role: LU
  Goal/desire: I want to see upcoming events of the community on the homepages

  Scenario: Successfully show my upcoming events as a LU
#    TODO: Test visibility settings (Public, Community)

    Given I am on the homepage
    Then I should not see "Behat Event 1"
    And I should not see "Behat Event 2"

    Given event content:
      | title         | field_event_date | status | field_content_visibility |
      | Behat Event 1 | +10 minutes      | 1      | public                   |
      | Behat Event 2 | +10 minutes      | 1      | public                   |

    Given I am on the homepage

    Then I should see "Upcoming events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"

    When I am at "community-events"
    Then I should see "Community events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"
    And I should see "Upcoming events"

    Given I am logged in as an "authenticated user"
    Then I should see "Behat Event 1"
    And I should see "Behat Event 2"

    When I click the link with the text "All Upcoming events"
    Then I should see "Community events"
    And I should see "Behat Event 1"
    And I should see "Behat Event 2"
