@api @share @perfect @critical @DS-1829
Feature: Social Sharing
  Benefit: In order to share my page with external people
  Role: As a Verified
  Goal/desire: I want to share my public content

  Background:
    Given I enable the optional module social_sharing

  Scenario: Can share public content as authenticated user
    Given topics with non-anonymous author:
      | title        | field_topic_type | body                      | field_content_visibility | path          |
      | Public Topic | News             | Testing public visibility | public                   | /public-topic |

    When I am logged in as a user with the "verified" role
    And I am on "/public-topic"

    Then I should see "Share this page"

  Scenario: Can share public content as anonymous user
    Given topics with non-anonymous author:
      | title        | field_topic_type | body                      | field_content_visibility | path          |
      | Public Topic | News             | Testing public visibility | public                   | /public-topic |

    When I am on "/public-topic"

    Then I should see "Share this page"

  Scenario: Can not share community content
    Given topics with non-anonymous author:
      | title           | field_topic_type | body                         | field_content_visibility | path             |
      | Community Topic | News             | Testing community visibility | community                | /community-topic |

    When I am logged in as a user with the "verified" role
    And I am on "/community-topic"

    Then I should not see "Share this page"
