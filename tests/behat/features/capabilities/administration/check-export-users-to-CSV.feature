@administration @account @stability @javascript @api @export-users
Feature: Export users
  Benefit: A user with the SM role can export users.
  Role: SM
  Goal/desire: Export users outside of Open Social.

  Scenario: As a SM I should be able to export users of my platform
    Given users:
      | name        | status | roles    |
      | ExportUser1 |      1 | verified |
      | ExportUser2 |      1 | verified |
      | ExportUser3 |      1 | verified |
    And I enable the module "social_user_export"

    When I am logged in as a user with the "sitemanager" role
      And I am on "admin/people"
      #Select the first 3 users displayed on the user list to be export
      And I check the box "edit-views-bulk-operations-bulk-form-0"
      And I check the box "edit-views-bulk-operations-bulk-form-1"
      And I check the box "edit-views-bulk-operations-bulk-form-2"
      #Execute the Export users to CSV action with the 3 users selected
      And I select "Export the selected users to CSV" from "Action"
      And I press the "Apply to selected items" button
      And I should see the text "Items selected:"
      And I press the "Apply" button
      And I should see the text "Are you sure you wish to perform"
      And I press the "Execute action" button
      And I wait for the batch job to finish

    #Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
      And I should see the text "Download file"
      And I should see the text "Action processing results: Export the selected users to CSV (3)."
