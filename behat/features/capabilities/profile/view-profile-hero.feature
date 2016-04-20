@account @profile @stability @AN @perfect @api @DS-739
Feature: Profile information
  Benefit: In order to know which user I am looking at
  Role: LU
  Goal/desire: See profile header

  Scenario: Successfully see profile header
    Given users:
      | name     | mail               | status | field_profile_first_name | field_profile_last_name |
      | user_1   | user_1@example.com | 1      | Albert                   | Einstein                |
      | user_2   | user_2@example.com | 1      | Isaac                    | Newton                  |
    And I am logged in as "user_1"
    And I am on "/user"
    And I should see "Albert Einstein" in the "Hero block"
    # @TODO: Uncomment this when title will be in correct region
    # And I should not see "user_1" in the "Hero block"
    And I should see an "i.material-icons" element
    # @TODO: Add scenario about view profile information of other user when Search Users will be ready