@account @profile @api @gdpr @DS-4895 @stability @stability-1 @hide-profile-fields
Feature: I want to be able to hide profile fields
  Benefit: In order to have better privacy for my users
  Role: SM
  Goal/desire: So I can determine if I collect data for profile fields

  Scenario: Successfully hide profile fields
    Given I enable the module "social_profile_fields"
    And users:
      | name          | mail                  | status |
      | john_doe      | john@doe.com          | 1      |

    Given I am logged in as "john_doe"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"
    Then I should see "First name"
    And I should see "Last name"
    And I should not see "Nickname"

    When I fill in the following:
      | First name   | John         |
      | Last name    | Doe          |
      | Phone number | +31612345678 |
    And I press "Save"
    Then I should see "John Doe"
    And I should see "+31612345678"

    # Check the profile field configuration.
    Given I am logged in as an "administrator"
    And I am on "admin/config/opensocial/profile-fields"
    And I uncheck the box "First name"
    And I uncheck the box "Last name"
    And I uncheck the box "Phone number"
    And I check the box "Nickname"
    Then I press "Save configuration"

    # Check my profile.
    Given I am logged in as "john_doe"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I click "Information"
    Then I should not see "John Doe"
    And I should not see "+31612345678"
    And I should see "john_doe"

    # Edit profile.
    Given I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Edit profile"
    Then I should not see "First name"
    And I should not see "Last name"
    And I should see "Nickname"

    When I fill in "Nickname" with "Shrouded Person"
    And I press "Save"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    And I click "Information"
    Then I should not see "John Doe"
    And I should not see "+31612345678"
    And I should see "Shrouded Person"

    # Check if not flushing data will restore the information.
    Given I am logged in as an "administrator"
    And I am on "admin/config/opensocial/profile-fields"
    And I check the box "First name"
    And I check the box "Last name"
    And I check the box "Phone number"
    And I uncheck the box "Nickname"
    And I press "Save configuration"
    When I am on the profile of "john_doe"
    And I click "Information"
    Then I should see "John Doe"
    And I should see "+31612345678"
    And I should not see "john_doe"
    And I should not see "Shrouded Person"
