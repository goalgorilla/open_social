@api 
Feature: Validate access and visibility of topics for Anonymous user and content manager
    
  Background:
    Given I enable the module "social_group_flexible_group"
    And topics with non-anonymous author:
      | title                         | field_topic_type | status | field_content_visibility | body                         |
      | This is a topic for public    | Blog             | 1      | public                   | Testing public visibility    |
      | This is a topic for community | Blog             | 1      | community                | Testing community visibility |
    And groups with non-anonymous owner:
      | label                   | field_group_description          | field_flexible_group_visibility | field_group_allowed_visibility  |type            |
      | Flexible group for topic   | Description of Flexible group    | public                          | public,community,group          |flexible_group  |
    And topics with non-anonymous author:
      | title                    | body             | group                       | field_content_visibility | field_topic_type |
      | This is a public topic in group        | Descriptions     | Flexible group for topic     | public                   | Blog  |
      | This is a community topic in group    | Descriptions     | Flexible group for topic     | community                | Blog  |
      | This is a secret topic in group        | Descriptions     | Flexible group for topic     | group                    | Blog  |

  Scenario: Anonymous user should only see public topics
    Given I am an anonymous user

    When I am on "/all-topics"

    Then I should see "This is a topic for public"
    And I should not see "This is a topic for community"
    And I should see "This is a public topic in group"
    And I should not see "This is a community topic in group"
    And I should not see "This is a secret topic in group"

    And I open the "topic" node with title "This is a topic for community"
    And I should not see "This is a topic for community"
    And I should see "Access denied"

    And I open the "topic" node with title "This is a community topic in group"
    And I should not see "This is a community topic in group"
    And I should see "Access denied"

    And I open the "topic" node with title "This is a secret topic in group"
    And I should not see "This is a secret topic in group"
    And I should see "Access denied"

    And I open the "topic" node with title "This is a topic for public"
    And I should see "This is a topic for public"

    And I open the "topic" node with title "This is a public topic in group"
    And I should see "This is a public topic in group"

  Scenario: Content manager should see all topics
    Given I am logged in as a user with the contentmanager role

    When I am on "/all-topics"

    Then I should see "This is a topic for public"
    And I should see "This is a topic for community"
    And I should see "This is a public topic in group"
    And I should see "This is a community topic in group"
    And I should see "This is a secret topic in group"

    And I open the "topic" node with title "This is a topic for public"
    And I should see "This is a topic for public"

    And I open the "topic" node with title "This is a public topic in group"
    And I should see "This is a public topic in group"

    And I open the "topic" node with title "This is a topic for community"
    And I should see "This is a topic for community"

    And I open the "topic" node with title "This is a community topic in group"
    And I should see "This is a community topic in group"

    And I open the "topic" node with title "This is a secret topic in group"
    And I should see "This is a secret topic in group"
