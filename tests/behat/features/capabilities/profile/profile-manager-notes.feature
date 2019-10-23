@account @profile @stability @AN @perfect @api @DS-4400 @stability-3 @profile-manager-notes
Feature: Profile manager notes
  Benefit: In order to create remarks about Users
  Role: SM
  Goal/desire: SM can share remarks about Users

  Scenario: Create a manager note for a profile
    Given I enable the module "social_profile_manager_notes"
    And I am logged in as an "administrator"
    And I am on "/user/12/information"
    And I should see "Site manager remarks"
    And I fill in "edit-comment-body-0-value" with "New remark created"
    And I press "edit-submit--2"
    And I should see "New remark created"

    Given I am logged in as an "authenticated user"
    And I am on "/user/12/information"
    And I should not see "Site manager remarks"
