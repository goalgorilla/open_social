@account @user @stability @stability-4 @perfect @api @export-users
Feature: Export users
  Benefit: A user with the SM role can export users.
  Role: SM
  Goal/desire: Export users outside of Open Social.

  Scenario: As a SM I should be able to export users of my platform
    Given I enable the module "social_user_export"
    When I am logged in as a user with the "sitemanager" role
    And I am on "admin/people"
    When I check the box "edit-views-bulk-operations-bulk-form-0"
    And I check the box "edit-views-bulk-operations-bulk-form-1"
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the text "Selected 3 entities"

    When I press the "Apply" button
    Then I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish
    Then I should see the text "Export is complete."
    Then I should see the text "Download file"
