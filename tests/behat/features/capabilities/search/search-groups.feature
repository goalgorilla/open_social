@api
Feature: Search
  Benefit: In order to find specific content
  Role: As a LU
  Goal/desire: I want to search the site for groups

  Scenario: Successfully search groups
    Given users:
      | name             | mail                     | status |
      | Group search One | group_user_1@example.com | 1      |
    And groups:
      | label                    | field_group_description | author           | type           | field_flexible_group_visibility | field_group_allowed_visibility | field_group_allowed_join_method | langcode |
      | Behat test group title 1 | My Behat description    | Group search One | flexible_group | public                          | public                         | direct                          | en       |
      | Behat test group title 2 | My Behat description 2  | Group search One | flexible_group | public                          | public                         | direct                          | en       |
      | Behat test group title 3 | No Behat descr          | Group search One | flexible_group | public                          | public                         | direct                          | en       |
    And Search indexes are up to date
    And I am logged in as an "authenticated user"
    #@TODO: Change "search/content" to the homepage when search block will be in the header
    And I am on "search/groups"

    When I fill in "search_input" with "My Behat description"
    And I press "Search" in the "Hero block" region
    And I should see the heading "Search" in the "Hero block" region
    And I should see "Behat test group title 1" in the "Main content"
    And I should see "Behat test group title 2" in the "Main content"
    And I should not see "Behat test group title 3" in the "Main content"
