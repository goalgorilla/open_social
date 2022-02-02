@api @topic @stability @perfect @community @newest @overview @block @verified @critical @DS-1057 @stability-3 @newest-topics
Feature: See newest topics in the community
  Benefit: In order to discover content
  Role: As a Verified
  Goal/desire: I want to see newest topics block and overview

  Scenario: Successfully show my upcoming events as a Verified
#    TODO: Test visibility settings (Public, Community)

    Given "topic_types" terms:
      | name          |
      | Blog          |
      | News          |
      | Article       |

    Given I am on "/stream"
    Then I should not see "Behat Topic 1"
    And I should not see "Behat Topic 2"

    Given "topic" content:
      | title         | field_topic_type | status | field_content_visibility |
      | Behat Topic 1 | Blog             | 1      | public                   |
      | Behat Topic 2 | News             | 1      | public                   |

    Given I am on "/stream"

    Then I should see "All topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

    When I am on "all-topics"
    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"
    And I should see "All topics"

    Given I am logged in as an "verified"
    And I am on "/stream"
    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

    When I am on "all-topics"
    Then I should see "All topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"
