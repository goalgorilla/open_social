@api
Feature: Visibility
  Benefit: In order to control the distribution of information and to secure my privacy
  Role: As a Verified
  Goal/desire: I want to set the visibility of content I create

  Background:
    Given topics with non-anonymous author:
      | title                         | field_topic_type | status | field_content_visibility | body                         |
      | This is a topic for public    | Blog             | 1      | public                   | Testing public visibility    |
      | This is a topic for community | Blog             | 1      | community                | Testing community visibility |

  Scenario: As authenticated user view public topic with zero user permission settings
    Given I disable that the registered users to be verified immediately
    And I am logged in as an "authenticated user"

    When I open the "topic" node with title "This is a topic for public"

    Then I should see "This is a topic for public"

  Scenario: As authenticated user view community topic with zero user permission settings
    Given I disable that the registered users to be verified immediately
    And I am logged in as an "authenticated user"

    When I open the "topic" node with title "This is a topic for community"

    Then I should not see "This is a topic for community"
    And I should see "Access denied"
    And I should see "You are not authorized to access this page."

  Scenario: As anonymous user I can view a public topic
    Given I am an anonymous user

    When I open the "topic" node with title "This is a topic for public"

    Then I should see "This is a topic for public"

  Scenario: As anonymous user I cant view a topic with community visibility
    Given I am an anonymous user

    When I open the "topic" node with title "This is a topic for community"

    Then I should not see "This is a topic for community"
    And I should see "Access denied."

  Scenario: Content visibility community as AN on topic overview
    Given I am an anonymous user

    When I am on the topic overview

    Then I should see "This is a topic for public"
    And I should not see "This is a topic for community"

  Scenario: As verified user I can view public and community topics on the topic overview
    Given I am logged in as an "verified"

    When I am on the topic overview

    Then I should see "This is a topic for public"
    And I should see "This is a topic for community"
