@api @javascript
Feature: Join a flexible group without the confirmation step
  Background:
    Given I enable the module "social_group_flexible_group"
    And I enable the module "social_group_quickjoin"
    And I set the configuration item "social_group_quickjoin.settings" with key "social_group_quickjoin_enabled" to 1

  Scenario: As outsider I can't join a flexible group with join method direct if not configured
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Secret visibility       | flexible_group | en       | community                       | direct                          |
    And I am logged in as an "verified"

    When I visit the group quick join link for "Test group"

    Then I should see "You can't join this group directly"

  Scenario: As outsider I can join a flexible group with join method direct without the confirmation step
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Secret visibility       | flexible_group | en       | community                       | direct                          |
    And I am logged in as an "verified"
    And I set the configuration item "social_group_quickjoin.settings" with key "social_group_quickjoin_flexible_group" to 1

    When I visit the group quick join link for "Test group"

    Then I should see "You've been added to this group"

  Scenario Outline: As outsider I can't quick join a flexible group with join method request and invite
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Secret visibility       | flexible_group | en       | community                       | <join_method>                    |
    And I am logged in as an "verified"
    And I set the configuration item "social_group_quickjoin.settings" with key "social_group_quickjoin_flexible_group" to 1

    When I visit the group quick join link for "Test group"

    Then I should see "You can't join this group directly"

    Examples:
      | join_method |
      | request     |
      | added       |
