@account @profile @stability @AN @perfect @api @DS-6941 @stability-3 @profile-manager-notes
Feature: Profile manager notes
  Benefit: In order to create remarks about Users
  Role: SM
  Goal/desire: SM can share remarks about Users

  Background:
    Given I enable the module "social_profile_manager_notes"
    And users:
      | name      |
      | test_user |

  Scenario: Create a manager note for a profile as a sitemanager
    Given I am logged in as a user with the sitemanager role

    When I am on the profile of "test_user"
    And I click "Information"

    Then I should see "Site manager remarks"
    And I fill in "edit-field-comment-body-0-value" with "New remark created"
    And I press "Leave remark"
    And I should see "New remark created"

  Scenario: Can not see manager notes as a non-sitemanager user
    Given I am logged in as an "verified"

    When I am on the profile of "test_user"
    And I click "Information"

    Then I should not see "Site manager remarks"
