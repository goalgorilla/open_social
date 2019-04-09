@account @profile @api @issue-3039084 @stability @stability-2 @restricted-full-name
Feature: I want to restrict full name visibility when nickname is used
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So I can hide my full name on the platform

  Background:
    Given I enable the module "social_profile_fields"
    And I enable the module "social_profile_privacy"
    And I enable the nickname field on profiles
    And users:
      | name   | mail                     | status | field_profile_first_name | field_profile_last_name | field_profile_nick_name |
      | user_1 | user_1@example.localhost | 1      | Open                     | User                    |                         |
      | user_2 | user_2@example.localhost | 1      | Secretive                | User                    | Hide my name            |
      | user_3 | user_3@example.localhost | 1      |                          |                         | Completely Anonymous    |

  Scenario: Nickname replaces full name when filled in
    Given Search indexes are up to date
    And I am logged in as an "authenticated user"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    And I should not see "Secretive User"

    # Search shows Nickname but allows searching for real name
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name"

    # TODO: Add test for mentioning using Javascript?

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: Extra protection for real names
    Given I restrict real name usage
    And Search indexes are up to date
    And I am logged in as an "authenticated user"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    But I should not see "Secretive User"

    # Search only allows searching for real names when the nickname is not
    # filled in.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should not see "Hide my name"
    And I should not see "Secretive user"

    When I search users for "Hide my name"
    Then I should see "Hide my name"

    # TODO: Add test for mentioning using Javascript?

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: View and search for real names when a user has the permission
    Given I restrict real name usage
    And Search indexes are up to date
    And I am logged in as a user with the "social profile privacy always show full name" permission

    # Profile displays the real name and nickname (if available).
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name (Secretive User)"

    When I go to the profile of "user_3"
    Then I should see "Completely Anonymous"

    # Search always allows searching for real names.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name (Secretive User)"

    When I search users for "Hide my name"
    Then I should see "Hide my name (Secretive User)"

    When I search users for "Completely"
    Then I should see "Completely Anonymous"

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"
