@api @javascript
Feature: Validate accessibility of flexible group pages for different users

  Background:
    Given I enable the module "social_group_flexible_group"
    And I am logged in as an "sitemanager"
    And users:
      | name        | mail                    | status | roles       |
      | Behat Owner | behat_owner@example.com | 1      | sitemanager |
      | Non Member  | non_member@example.com  | 1      |             |
      | Member      | member@example.com      | 1      |             |

    And groups no validation:
      | label           | type           | author      | field_flexible_group_visibility |
      | Test Flexible   | flexible_group | Behat Owner | public                          |
    And group members with values:
      | group          | user   | group_roles           |
      | Test Flexible  | Member | flexible_group-member |

  Scenario: Anonymous user should be redirected to the About page from "Members" and "Stream" pages.
    Given I am an anonymous user

    When I go to "/group/1/stream"

    Then the URL should match "^\/group\/[^\/]+\/about$"

    And I go to "/group/1/members"
    And the URL should match "^\/group\/[^\/]+\/about$"

  Scenario: Verified user should be redirected to the About page from "Members" and "Stream" pages if he is non-member.
    Given I am logged in as "Non Member"

    When I go to "/group/1/stream"

    Then the URL should match "^\/group\/[^\/]+\/about$"

    And I go to "/group/1/members"
    And the URL should match "^\/group\/[^\/]+\/about$"

  Scenario: Group member shouldn't be redirected to the About page from "Members" and "Stream" pages.
    Given I am logged in as "Member"

    When I go to "/group/1/stream"

    Then the URL should match "^\/group\/[^\/]+\/stream"

    And I go to "/group/1/members"
    And the URL should match "^\/group\/[^\/]+\/members$"
