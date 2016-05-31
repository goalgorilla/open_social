@api @topic @stability @perfect @community @newest @overview @block @LU @critical @DS-1057
Feature: See newest topics in the community
  Benefit: In order to discover content
  Role: LU
  Goal/desire: I want to see newest topics block and overview

  Scenario: Successfully show my upcoming events as a LU
#    TODO: Test visibility settings (Public, Community)

    Given I am on the homepage
    Then I should not see "Behat Topic 1"
    And I should not see "Behat Topic 2"

    Given topic content:
      | title         | field_topic_type | status |
      | Behat Topic 1 | Blog             | 1      |
      | Behat Topic 2 | News             | 1      |

    Given I am on the homepage

    Then I should see "Newest topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

    When I click the xth "2" link with the text "View all"
    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"
    And I should see "Newest topics"


    Given I am logged in as an "authenticated user"
    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

    When I click the xth "3" link with the text "View all"
    Then I should see "Newest topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

