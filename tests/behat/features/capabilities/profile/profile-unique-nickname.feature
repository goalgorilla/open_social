@account @profile @api @gdpr @GPDE-114 @unique-nicknames
Feature: I want to be able to make nick names unique
  Benefit: Increased distinguishability
  Role: SM
  Goal/desire: So I can see who's who even when they have nick names

  Background:
    Given the profile fields are enabled:
      | Field name |
      | Nickname   |

  Scenario: Nickname uniqueness is enforced when enabled
    Given unique nicknames for users is enabled
    And users:
      | name           | mail                    | status | field_nickname |
      | peter_schwartz | peter@example.localhost | 1      | Peter Pirate   |
    And I am logged in as a user with the verified role

    When I am editing my profile
    And I fill in "Nickname" with "Peter Pirate"
    And I press "Save"

    Then I should see the error message "Peter Pirate is already taken."

  Scenario: Nickname is allowed if it's unique
    Given unique nicknames for users is enabled
    And users:
      | name           | mail                    | status | field_nickname |
      | peter_schwartz | peter@example.localhost | 1      | Peter Pirate   |
    And I am logged in as a user with the verified role

    When I am editing my profile
    And I fill in "Nickname" with "Postman Pat"
    And I press "Save"

    Then I should see "Postman Pat"

  Scenario: Nickname uniqueness is not enforced when disabled
    Given unique nicknames for users is disabled
    And users:
      | name           | mail                    | status | field_nickname |
      | peter_schwartz | peter@example.localhost | 1      | Peter Pirate   |
    And I am logged in as a user with the verified role

    When I am editing my profile
    And I fill in "Nickname" with "Peter Pirate"
    And I press "Save"

    Then I should see "Peter Pirate"

  Scenario: Sitemanager can enable the setting
    Given unique nicknames for users is disabled
    And I am logged in as a user with the sitemanager role

    When I am on "admin/config/people/social-profile"
    And I check the box "Unique nicknames"
    And I press "Save configuration"

    Then unique nicknames for users should be enabled

  Scenario: Sitemanager can disable the setting
    Given unique nicknames for users is enabled
    And I am logged in as a user with the sitemanager role

    When I am on "admin/config/people/social-profile"
    And I uncheck the box "Unique nicknames"
    And I press "Save configuration"

    Then unique nicknames for users should be disabled
