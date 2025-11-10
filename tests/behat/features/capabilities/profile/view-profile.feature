@api
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
    And I should see the heading "Albert Einstein"
    # @TODO: Uncomment this when title will be in correct region
    # And I should not see "user_1" in the "Hero block"
    And I should see the link "Edit profile information"
    # @TODO: Add scenario about view profile information of other user when Search Users will be ready

  Scenario: Successfully see non-platform affiliation in profile page
    Given users:
      | name        | mail              | status | field_profile_first_name | field_profile_last_name |
      | marie_curie | marie@example.com | 1      | Marie                    | Curie                   |
    And The user "marie_curie" has non-platform affiliation "University of Paris" with function "Professor"
    And I am logged in as "marie_curie"
    And I am on "/user"
    # Affiliation information is displaying the statistic bock.
    And I should see "Professor" in the "#block-socialblue-profile-statistic-block" element
    And I should see "University of Paris" in the "#block-socialblue-profile-statistic-block" element
