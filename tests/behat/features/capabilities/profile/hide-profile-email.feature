@account @profile @AN @perfect @api @DS-2082 @stability @stability-2
Feature: I want to be able to hide my email address
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So I can determine if I want to show my email address to everyone

  Scenario: Successfully hide my email address
    Given users:
      | name          | mail                  | status |
      | user_1        | user_1@example.com    | 1      |
      | user_2        | user_2@example.com    | 1      |

    # Disable the privacy setting.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I uncheck the box "social_profile_show_email"
    And I press "Save configuration"

    # Check the profile of someone else, and now I should NOT see the email address.
    Given I am logged in as "user_1"
    And I am on the profile of "user_2"
    And I click "Information"
    And I should not see "user_2@example.com"

    # Check the profile of myself.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I click "Information"
    And I should not see "user_1@example.com"

    # Enable the setting on my profile.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see "Show my email on my profile"
    And I show hidden checkboxes
    And I check the box "Show my email on my profile"
    And I press "Save"
    And I click "Information"
    And I should see "user_1@example.com"

    # Enable the privacy setting.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I check the box "social_profile_show_email"
    And I press "Save configuration"

    # Check the profile of someone else, and now I should see the email address.
    Given I am logged in as "user_1"
    And I am on the profile of "user_2"
    And I click "Information"
    And I should see "user_2@example.com"
