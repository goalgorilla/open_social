@api
Feature: See newest topics in the community
  Benefit: In order to discover content
  Role: As a Verified
  Goal/desire: I want to see newest topics block and overview

  Background:
    Given "topic_types" terms:
      | name          |
      | Blog          |
      | News          |
      | Article       |

    And "topic" content:
      | title         | field_topic_type | status | field_content_visibility |
      | Behat Topic 1 | Blog             | 1      | public                   |
      | Behat Topic 2 | News             | 1      | public                   |

  Scenario: Successfully show upcoming events as a AN on the stream
    Given I am an anonymous user

    When I am on "/stream"

    Then I should see "All topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

  Scenario: Successfully show upcoming events as a AN on the overview
    Given I am an anonymous user

    When I am on the topic overview

    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"
    And I should see "All topics"

  Scenario: Successfully show my upcoming events as a Verified on the stream
    Given I am logged in as an "verified"

    When I am on "/stream"

    Then I should see "Behat Topic 1"
    And I should see "Behat Topic 2"

  Scenario: Successfully show my upcoming events as a Verified on the overview
    Given I am logged in as an "verified"

    When I am on the topic overview

    Then I should see "All topics"
    And I should see "Behat Topic 1"
    And I should see "Behat Topic 2"
