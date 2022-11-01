@api @group @notifications @TB-6072 @DS-4211 @ECI-632 @stability @stability-1 @group-create-flexible @javascript
Feature: Create flexible Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a Verified
  Goal/desire: I want to create flexible Groups

  # @todo Check that we also test error conditions
  # @todo Scenario: Notifications
  # @todo Check that member overview permissions are tested
  # @todo Check that member management is tested
  # @todo Create a ticket to create test coverage for SEO

  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: Unverified can see members from flexible groups with Community visibility as outsider
    Given users:
      | name             | roles    |
      | The group member | verified |
    And groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |
    And group members:
      | group      | user             |
      | Test group | The group member |
    And I am logged in as a user with the authenticated role

    When I am viewing the group "Test group"
    And I click Members

    Then I should see "The group member"

  # @todo This test currently fails because zero-permission users are allowed to join groups but shouldn't.
  Scenario: Unverified can not join flexible groups with Community visibility as authenticated user when Direct is selected as join method
    Given I disable that the registered users to be verified immediately
    And groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |
    And I am logged in as a user with the authenticated role

    When I am viewing the group "Test group"

    Then I should not see the link Join

  Scenario: Verified can join flexible groups with Community visibility as verified user when Direct is selected as join method
    Given groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |
    And I am logged in as a user with the verified role

    When I am viewing the group "Test group"
    And I click Join
    And I press "Join group"

    # @todo The fact that I need to look for a button here rather than a
    #  confirmation message indicates a UX issue.
    Then I should see the button "Joined"

  Scenario: Can create a community post as a flexible group member
    Given users:
      | name             | roles    |
      | The group member | verified |
    And groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |
    And group members:
      | group      | user             |
      | Test group | The group member |
    And I am logged in as "The group member"

    When I am on the stream of group "Test group"
    And I fill in "Say something to the group" with "This is a flexible group post."
    And I select post visibility Community
    And I press "Post"

    Then I should see the success message "Your post has been posted."

  Scenario: It preselects the right group when creating an event
    Given I am logged in as a user with the verified role
    And groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |

    When I am viewing the group "Test group"
    And I click "Events"
    And I click "Create Event"

    Then I should be on the event creation form
    And the group "Test group" should be preselected

  Scenario: It preselects the right group when creating a topic
    Given I am logged in as a user with the verified role
    And groups:
      | label      | field_group_description  | type           | langcode | field_flexible_group_visibility |
      | Test group | Outsider visibility      | flexible_group | en       | community                       |

    When I am viewing the group "Test group"
    And I click "Topics"
    And I click "Create Topic"

    Then I should be on the topic creation form
    And the group "Test group" should be preselected

