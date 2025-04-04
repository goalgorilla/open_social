@api @javascript
Feature: Edit flood settings
  Benefit: Have an interface to edit flood settings
  Role: As an ADMIN
  Goal/desire: I want to edit flood settings

  @perfect
  Scenario: Successfully modify and see flood settings
    Given I am logged in as an "administrator"
    And I am on "/admin/config/people/accounts/flood"
    And I should see "IP limit"
    And I should see "IP window"
    And I should see "User limit"
    And I should see "User window"

    And I fill in the following:
      | IP limit | 4000 |
      | IP window | 600 |
      | User limit | 2 |
      | User window | 600 |
    And I press "Save configuration"
    And I should see the success message "The configuration options have been saved."

