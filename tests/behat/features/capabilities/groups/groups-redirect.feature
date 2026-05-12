@api
Feature: The naked group path redirects to the landing page

  Scenario: As verified user I get redirected with the group ID
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | direct                          |
    And I am logged in as a user with the verified role

    When I visit "/group/1"

    Then I should be on "/group/1/about"
    And I should see "Test group"

  Scenario: As verified user I get redirected with the group alias
    Given groups with non-anonymous owner:
      | label      | path        | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | /group/test | Public visibility       | flexible_group | en       | public                          | direct                          |
    And I am logged in as a user with the verified role

    When I visit "/group/test"

    Then I should be on "/group/1/about"
    And I should see "Test group"

  Scenario: As verified user I an invalid alias under /group causes a 404
    Given groups with non-anonymous owner:
      | label      | path        | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | /group/test | Public visibility       | flexible_group | en       | public                          | direct                          |
    And I am logged in as a user with the verified role

    When I visit "/group/nonexistant" expecting a 404 status code

    And I should see "Page not found"
