@wip @api @DS-1255 @activity_stream @topic @create @group
Feature: See and get notified when content is created
  Benefit: So I can discover new content on the platform
  Role: As a LU
  Goal/desire: I want see and get notified when content is created

  Scenario: See public topic and event
    Given users:
      | name       | status | pass        |
      | CreateUser | 1      | CreateUser  |
      | SeeUser    | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    And I am on the homepage
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | field_event_date         | +8 days                |
      | status                   | 1                      |
      | field_content_visibility | public                 |

    Given I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | public                 |

    When I wait for the queue to be empty
    And I go to "user"
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"
    When I am on the homepage
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"

    Given I am an anonymous user
    When I am on the homepage
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"

  Scenario: See community topic
    Given users:
      | name        | status | pass        |
      | CreateUser  | 1      | CreateUser  |
      | SeeUser     | 1      | SeeUser     |
    And I am logged in as "CreateUser"
    And I am on the homepage
    Given I am viewing my event:
      | title                    | My Behat Event created |
      | field_event_date         | +8 days                |
      | status                   | 1                      |
      | field_content_visibility | community              |

    And I am viewing my topic:
      | title                    | My Behat Topic created |
      | status                   | 1                      |
      | field_content_visibility | community              |

    When I wait for the queue to be empty
    And I go to "user"
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"
    When I am on the homepage
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"
    When I go to "explore"
    Then I should see "CreateUser created an event in the community"
    And I should see "My Behat Event created"
    And I should see "CreateUser created a topic in the community"
    And I should see "My Behat Topic created"

    Given I am an anonymous user
    When I am on the homepage
    Then I should not see "CreateUser created an event in the community"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic in the community"
    And I should not see "My Behat Topic created"
    When I go to "explore"
    Then I should not see "CreateUser created an event in the community"
    And I should not see "My Behat Event created"
    And I should not see "CreateUser created a topic in the community"
    And I should not see "My Behat Topic created"