@account @profile @AN @perfect @api @DS-3440 @stability @stability-4
Feature: I want to be able to hide certain profile information
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So I can determine if I want to show my contact information

  Scenario: Successfully hide certain profile information
    Given I enable the module "social_profile_privacy"
    Given users:
      | name          | mail                  | status |
      | user_1        | user_1@example.com    | 1      |
      | user_2        | user_2@example.com    | 1      |

    # Set your profile information and privacy settings.
    Given I am logged in as "user_1"
    And I am on "/user"
    And I click "Edit profile information"
    When I fill in the following:
      | Phone number | 911 |

    And I select "UA" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
      | City | Lviv |
      | Street address | Fedkovycha 60a |
      | Postal code | 79000 |
      | Oblast | Lviv oblast |
    And I press "Save"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see "Show \"Phone number and location\" on my profile"
    And I show hidden checkboxes
    # This means other users can view this information.
    And I check the box "edit-profile-privacy-group-profile-contact-info-visible"
    And I press "Save"

    Given I am logged in as "user_2"
    And I am on the profile of "user_1"
    When I click "Information"
    Then I should see "911"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"

    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see "Show \"Phone number and location\" on my profile"
    And I show hidden checkboxes
    # This means other users can not view this information.
    And I uncheck the box "edit-profile-privacy-group-profile-contact-info-visible"
    And I press "Save"

    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see "Show \"Phone number and location\" on my profile"

    Given I am logged in as "user_2"
    And I am on the profile of "user_1"
    When I click "Information"
    Then I should not see "911"
    And I should not see "Fedkovycha 60a"
    And I should not see "79000"
    And I should not see "Lviv"
    And I should not see "Lviv oblast"

    # Enable the privacy setting.
    Given I am logged in as an "administrator"
    And I am on the profile of "user_1"
    When I click "Information"
    Then I should see "911"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"
