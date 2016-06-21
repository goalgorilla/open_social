@api @administration @javascript @DS-361 @ADMIN
Feature: Edit flood settings
  Benefit: Have an interface to edit flood settings
  Role: As an ADMIN
  Goal/desire: I want to edit flood settings

  @perfect
  Scenario: Successfully modify and see flood settings
    Given I am logged in as an "administrator"
      And I click "Configuration"
      And I click admin link "Account settings"
      And I click "Flood settings"
     Then I should see "IP limit"
      And I should see "IP window"
      And I should see "User limit"
      And I should see "User window"

    Given I fill in the following:
          | IP limit | 4000 |
          | IP window | 600 |
          | User limit | 2 |
          | User window | 600 |
      And I press "Save configuration"
     Then I should see the success message "The configuration options have been saved."

