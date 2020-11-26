@account @profile @AN @perfect @api @DS-3440 @YANG-4105 @stability @stability-4 @hide-profile-field-groups
Feature: I want to be able to hide certain profile information
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So I can determine if I want to show my contact information

  Scenario: Successfully hide certain profile information
    Given I enable the module "social_profile_privacy"
    And users:
      | name          | mail                  | status | pass   |
      | user_1        | user_1@example.com    | 1      | user_1 |
      | user_2        | user_2@example.com    | 1      | user_2 |

    # Set your profile information and privacy settings.
    When I am logged in as "user_1"
    And I am on "/user"
    And I click "Edit profile information"
    And I fill in "Phone number" with "+1-202-555-0150"

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
    Then I should see "Address" in the "#edit-profile-privacy" element
    And I should see "Phone number" in the "#edit-profile-privacy" element

    Given I am logged in as "user_2"
    And I am on the profile of "user_1"
    When I click "Information"
    Then I should see "+1-202-555-0150"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"

    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    Then I should see "Address" in the "#edit-profile-privacy" element
    And I should see "Phone number" in the "#edit-profile-privacy" element
    And I show hidden checkboxes
    # This means other users can not view this information.
    And I uncheck the box "edit-profile-privacy-fields-field-profile-address"
    And I uncheck the box "edit-profile-privacy-fields-field-profile-phone-number"
    And I press "Save"

    Given I am logged in as "user_2"
    And the cache has been cleared
    And I am on the profile of "user_1"
    Then I should see "user_1"
    When I click "Information"
    Then I should not see "+1-202-555-0150"
    And I should not see "Fedkovycha 60a"
    And I should not see "79000"
    And I should not see "Lviv"
    And I should not see "Lviv oblast"

    # Enable the privacy setting.
    Given I am logged in as an "administrator"
    And I am on the profile of "user_1"
    When I click "Information"
    Then I should see "+1-202-555-0150"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"
