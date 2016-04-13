@api @search @stability @DS-700
  Feature: Search people
  Benefit: In order to find user on the platform (to find information about someone, find content of someone, or contact the user).
  Role: As a LU
  Goal/desire: I want to find people

  Scenario: Successfully search users
    Given users:
      | name     | mail               | status | field_profile_first_name |
      | user_1   | user_1@example.com | 1      | User first               |
      | user_2   | user_2@example.com | 1      | User second              |
    And I am logged in as an "authenticated user"
    And I am on "search/users"
    When I fill in the following:
      | Search the entire website | first |
    And I press "Search"
    And I should see "Search users" in the "Page title block"
    And I should see "User first" in the "Main content"
    And I should not see "User second"
