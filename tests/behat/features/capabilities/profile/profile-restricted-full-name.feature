@account @profile @api @issue-3039084 @javascript @restricted-full-name
Feature: I want to restrict full name visibility when nickname is used
  Benefit: In order to have better privacy
  Role: As a Verified
  Goal/desire: So I can hide my full name on the platform

  Background:
    Given the profile fields are enabled:
      | Field name |
      | First name |
      | Last name  |
      | Nickname   |
    And users:
      | name   | mail                     | status | field_profile_first_name | field_profile_last_name | field_profile_nick_name | roles       |
      | user_1 | user_1@example.localhost | 1      | Open                     | User                    |                         | verified    |
      | user_2 | user_2@example.localhost | 1      | Secretive                | Person                  | Hide my name            | verified    |
      | user_3 | user_3@example.localhost | 1      |                          |                         | Completely Anonymous    | verified    |
      | sm     | site_manager@example.com | 1      |                          |                         |                         | sitemanager |

  Scenario: Extra protection for real names
    Given I hide real name behind nickname
    And Search indexes are up to date
    And I am logged in as an "verified"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    But I should not see "Secretive Person"

    # Search only allows searching for real names when the nickname is not
    # filled in.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should not see "Hide my name"
    And I should not see "Secretive Person"

    When I search users for "Hide my name"
    Then I should see "Hide my name"

    # Searching for an exact full name should not expose it. This tests for a
    # reported bug that allowed users to guess hidden full names.
    When I search users for "Secretive Person"
    Then I should not see "Hide my name"

    # TODO: Add test for mentioning using Javascript?

  Scenario: View and search for real names when a user has the permission
    Given I hide real name behind nickname
    And Search indexes are up to date
    And I am logged in as a user with the "social profile always show full name" permission

    # Profile displays the real name and nickname (if available).
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name (Secretive Person)"

    When I go to the profile of "user_3"
    Then I should see "Completely Anonymous"

    # Search always allows searching for real names.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name (Secretive Person)"

    When I search users for "Hide my name"
    Then I should see "Hide my name (Secretive Person)"

    When I search users for "Completely"
    Then I should see "Completely Anonymous"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  # This test ensures that searching by username works. It's included so that
  # when the next scenario (searching for username when names are restricted)
  # fails, we can be sure the cause is in the name restricting.
  # If this scenario fails then the next one will fail as well but something
  # else is broken.
  Scenario: Searching by username works when name is unrestricted
    Given I unhide real name behind nickname
    And Search indexes are up to date
    And I am logged in as an "verified"

    When I search users for "user"
    Then I should see "Open User"
    And I should see "Hide my name"
    And I should see "Completely Anonymous"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Searching by username still works when name is restricted
    Given I hide real name behind nickname
    And Search indexes are up to date
    And I am logged in as an "verified"

    When I search users for "user"
    Then I should see "Open User"
    And I should see "Hide my name"
    And I should see "Completely Anonymous"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  # This scenarios intentionally comes last since it's the Open Social default
  # and least likely to break. This reduces test times.
  Scenario: Nickname replaces full name when filled in
    Given I unhide real name behind nickname
    And Search indexes are up to date
    And I am logged in as an "verified"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    And I should not see "Secretive Person"

    # Search shows Nickname but allows searching for real name
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name"

  # TODO: Add test for mentioning using Javascript?
