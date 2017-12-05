@api @search @stability @DS-700 @stability-3
  Feature: Search people
  Benefit: In order to find user on the platform (to find information about someone, find content of someone, or contact the user).
  Role: As a LU
  Goal/desire: I want to find people

  Scenario: Successfully search users
    Given users:

      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | user_1@example.com | 1      | User                     | one                     |
      | user_2   | user_2@example.com | 1      | User                     | two                     |
    And I am logged in as an "authenticated user"
    And I am on "search/users"
    When I fill in the following:
      | search_input | one |
    And I press "Search"
    And I should see the heading "Search" in the "Hero block" region
    And I should see "User one" in the "Main content"
    And I should not see "User two" in the "Main content"

  Scenario: search for admin user without result
    Given I am logged in as an "authenticated user"
      And I am on "search/users/admin"
     Then I should see "Member results"
      And I should see "No results found."
