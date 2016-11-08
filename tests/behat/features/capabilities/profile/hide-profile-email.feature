@account @profile @AN @perfect @api @DS-2082 @stability
Feature: I want to be able to hide my email address
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So i can determine if I want to show my email address to everyone

  Scenario: Successfully hide my email address
    Given users:
      | name          | mail                  | status |
      | user_1        | user_1@example.com    | 1      |
      | user_2        | user_2@example.com    | 1      |

    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I uncheck the box "social_profile_show_email"
    And I press "Save configuration"

    Given I am logged in as "user_1"
    And I am on "newest-members"
    Then I click "user_2"
    And I click "Information"
    And I should not see "user_2@example.com"

    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I click "Information"
    And I should not see "user_1@example.com"

    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit account"
    Then I should see "Show my email on my profile"
    And I show hidden checkboxes
    And I check the box "Show my email on my profile"
    And I press "Save"
    And I click "Information"
    And I should see "user_1@example.com"

    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I check the box "social_profile_show_email"
    And I press "Save configuration"

    Given I am logged in as "user_1"
    And I am on "newest-members"
    Then I click "user_2"
    And I click "Information"
    And I should see "user_2@example.com"
