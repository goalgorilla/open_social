@account @profile @stability @AN @perfect @api @DS-739
Feature: Profile information
  Benefit: In order to know which user I am looking at
  Role: LU
  Goal/desire: See profile header

  Scenario: Successfully see profile header
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name | field_profile_organization | field_profile_function |
      | user_1   | user_1@example.com | 1      | Albert                   | Einstein                | Science                    | Professor              |
      | user_2   | user_2@example.com | 1      | Isaac                    | Newton                  | Cambridge                  | Professor              |
    And I am logged in as "user_1"
    And I am on "/user"
    And I should see the heading "Albert Einstein"
    And I should see "Science"
    # @TODO: Uncomment this when title will be in correct region
    # And I should not see "user_1" in the "Hero block"
    And I should see the link "Edit profile information"
    # @TODO: Add scenario about view profile information of other user when Search Users will be ready
