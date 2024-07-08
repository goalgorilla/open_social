@api
Feature: A group can be configured to allow joining directly

  Scenario: As anonymous user view a public group
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | direct                          |
    And I am an anonymous user

    When I am viewing the "stream" page of group "Test group"

    Then I should see "Test group"
    And I should see the link Join
