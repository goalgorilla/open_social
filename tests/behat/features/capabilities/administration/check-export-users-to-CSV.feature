@administration @account @stability @javascript @api @export-users
Feature: Export users
  Benefit: A user with the SM role can export users.
  Role: SM
  Goal/desire: Export users outside of Open Social.

  Background:
    Given users:
      | uid | uuid                                 | name        | status | roles    | created    |
      | 2   | 85e14211-147c-4f01-ae18-b97300671de6 | ExportUser1 | 1      | verified | 1676644930 |
      | 3   | 34fff0f1-8897-4ca8-a682-707c9dd87501 | ExportUser2 | 1      | verified | 1676644931 |
      | 4   | 5d0df1b9-ee3b-4356-9e48-8891fadfff85 | ExportUser3 | 1      | verified | 1676644932 |
    And I enable the module "social_user_export"
    And I enable the module "social_profile_fields"
    # We want to be able to test with all other optional modules so we must
    # disable the modules that add export fields that we test separately.
    And I disable module social_private_message and its dependants
    And I disable module social_profile_organization_tag and its dependants

  # Ensure the CSV file that people are using in their automation without
  # customisation is the same between Open Social versions.
  Scenario: The default export for a new installation
    Given I am logged in as a user with the "sitemanager" role

    When I am on "admin/people"
    # Select 3 users displayed on the user list to be export
    # We skip the site manager and admin which will have been created after our
    # "given users" due to their fixed timestamp.
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I check the box "edit-views-bulk-operations-bulk-form-3"
    And I check the box "edit-views-bulk-operations-bulk-form-4"
    # Execute the Export users to CSV action with the 3 users selected
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    And I should see the text "Items selected:"
    And I press the "Apply" button
    And I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish

    # Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
    And I should see the text "Download file"
    And I should see the text "Action processing results: Export the selected users to CSV (3)."
    And the file downloaded from "Download file" should have contents:
      """
      "User ID",UUID,"First name","Last name",Username,"Display name",Email,"Last login","Last access","Registration date",Status,"Country code","Administrative address","Address locality","Postal code","Address line 1","Address line 2","Phone number",Nationality,Organization,Function,Skills,Interests,"Self introduction","Profile tag",Roles,"Posts created","Comments created","Topics created","Events created","Event enrollments","Groups created","Group memberships","Group memberships (specified)","Number of Likes"
      4,5d0df1b9-ee3b-4356-9e48-8891fadfff85,,,ExportUser3,ExportUser3,exportuser3@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      3,34fff0f1-8897-4ca8-a682-707c9dd87501,,,ExportUser2,ExportUser2,exportuser2@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      2,85e14211-147c-4f01-ae18-b97300671de6,,,ExportUser1,ExportUser1,exportuser1@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      """

  Scenario: The organization tag extension adds its profile field to the export
    Given I enable the module social_profile_organization_tag
    And I am logged in as a user with the "sitemanager" role

    When I am on "admin/people"
    # Select 3 users displayed on the user list to be export
    # We skip the site manager and admin which will have been created after our
    # "given users" due to their fixed timestamp.
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I check the box "edit-views-bulk-operations-bulk-form-3"
    And I check the box "edit-views-bulk-operations-bulk-form-4"
    # Execute the Export users to CSV action with the 3 users selected
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    And I should see the text "Items selected:"
    And I press the "Apply" button
    And I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish

    # Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
    And I should see the text "Download file"
    And I should see the text "Action processing results: Export the selected users to CSV (3)."
    And the file downloaded from "Download file" should have contents:
      """
      "User ID",UUID,"First name","Last name",Username,"Display name",Email,"Last login","Last access","Registration date",Status,"Country code","Administrative address","Address locality","Postal code","Address line 1","Address line 2","Phone number",Nationality,Organization,Function,"Organization Tag",Skills,Interests,"Self introduction","Profile tag",Roles,"Posts created","Comments created","Topics created","Events created","Event enrollments","Groups created","Group memberships","Group memberships (specified)","Number of Likes"
      4,5d0df1b9-ee3b-4356-9e48-8891fadfff85,,,ExportUser3,ExportUser3,exportuser3@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      3,34fff0f1-8897-4ca8-a682-707c9dd87501,,,ExportUser2,ExportUser2,exportuser2@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      2,85e14211-147c-4f01-ae18-b97300671de6,,,ExportUser1,ExportUser1,exportuser1@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
      """

  Scenario: The private message module adds a private message statistic to the export
    Given I enable the module social_private_message
    And I am logged in as a user with the "sitemanager" role

    When I am on "admin/people"
    # Select 3 users displayed on the user list to be export
    # We skip the site manager and admin which will have been created after our
    # "given users" due to their fixed timestamp.
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I check the box "edit-views-bulk-operations-bulk-form-3"
    And I check the box "edit-views-bulk-operations-bulk-form-4"
    # Execute the Export users to CSV action with the 3 users selected
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    And I should see the text "Items selected:"
    And I press the "Apply" button
    And I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish

    # Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
    And I should see the text "Download file"
    And I should see the text "Action processing results: Export the selected users to CSV (3)."
    And the file downloaded from "Download file" should have contents:
      """
      "User ID",UUID,"First name","Last name",Username,"Display name",Email,"Last login","Last access","Registration date",Status,"Country code","Administrative address","Address locality","Postal code","Address line 1","Address line 2","Phone number",Nationality,Organization,Function,Skills,Interests,"Self introduction","Profile tag",Roles,"Posts created","Comments created","Topics created","Events created","Event enrollments","Groups created","Group memberships","Group memberships (specified)","Number of Likes","Number of Private messages"
      4,5d0df1b9-ee3b-4356-9e48-8891fadfff85,,,ExportUser3,ExportUser3,exportuser3@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0,0
      3,34fff0f1-8897-4ca8-a682-707c9dd87501,,,ExportUser2,ExportUser2,exportuser2@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0,0
      2,85e14211-147c-4f01-ae18-b97300671de6,,,ExportUser1,ExportUser1,exportuser1@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0,0
      """

  Scenario: All profile features enabled
    # Needed while Social Profile Fields exists
    Given I set the configuration item "social_profile_fields.settings" with key "profile_profile_field_profile_nick_name" to 1
    # Enable all the profile fields
    Given I set the configuration item "field.field.profile.profile.field_profile_address" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_first_name" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_last_name" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_nick_name" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_phone_number" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_profile_tag" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_nationality" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_organization" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_function" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_expertise" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_interests" with key "status" to 1
    And I set the configuration item "field.field.profile.profile.field_profile_self_introduction" with key "status" to 1
    And I am logged in as a user with the "sitemanager" role

    When I am on "admin/people"
    # Select 3 users displayed on the user list to be export
    # We skip the site manager and admin which will have been created after our
    # "given users" due to their fixed timestamp.
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I check the box "edit-views-bulk-operations-bulk-form-3"
    And I check the box "edit-views-bulk-operations-bulk-form-4"
    # Execute the Export users to CSV action with the 3 users selected
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    And I should see the text "Items selected:"
    And I press the "Apply" button
    And I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish

    # Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
    And I should see the text "Download file"
    And I should see the text "Action processing results: Export the selected users to CSV (3)."
    And the file downloaded from "Download file" should have contents:
        """
        "User ID",UUID,"First name","Last name",Username,"Display name",Nickname,Email,"Last login","Last access","Registration date",Status,"Country code","Administrative address","Address locality","Postal code","Address line 1","Address line 2","Phone number",Nationality,Organization,Function,Skills,Interests,"Self introduction","Profile tag",Roles,"Posts created","Comments created","Topics created","Events created","Event enrollments","Groups created","Group memberships","Group memberships (specified)","Number of Likes"
        4,5d0df1b9-ee3b-4356-9e48-8891fadfff85,,,ExportUser3,ExportUser3,,exportuser3@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
        3,34fff0f1-8897-4ca8-a682-707c9dd87501,,,ExportUser2,ExportUser2,,exportuser2@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
        2,85e14211-147c-4f01-ae18-b97300671de6,,,ExportUser1,ExportUser1,,exportuser1@example.com,never,never,"02/17/2023 - 14:42",Active,,,,,,,,,,,,,,,"authenticated, verified",0,0,0,0,0,0,0,,0
        """

  Scenario: All profile features disabled
    # Disable all the profile fields
    Given I set the configuration item "field.field.profile.profile.field_profile_address" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_first_name" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_last_name" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_nick_name" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_phone_number" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_profile_tag" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_nationality" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_organization" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_function" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_expertise" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_interests" with key "status" to 0
    And I set the configuration item "field.field.profile.profile.field_profile_self_introduction" with key "status" to 0
    And I am logged in as a user with the "sitemanager" role

    When I am on "admin/people"
    # Select 3 users displayed on the user list to be export
    # We skip the site manager and admin which will have been created after our
    # "given users" due to their fixed timestamp.
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I check the box "edit-views-bulk-operations-bulk-form-3"
    And I check the box "edit-views-bulk-operations-bulk-form-4"
    # Execute the Export users to CSV action with the 3 users selected
    And I select "Export the selected users to CSV" from "Action"
    And I press the "Apply to selected items" button
    And I should see the text "Items selected:"
    And I press the "Apply" button
    And I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish

    # Check the success messages and a downloadable file with 3 users should be available
    Then I should see the text "Export is complete."
    And I should see the text "Download file"
    And I should see the text "Action processing results: Export the selected users to CSV (3)."
    And the file downloaded from "Download file" should have contents:
      """
      "User ID",UUID,Username,"Display name",Email,"Last login","Last access","Registration date",Status,Roles,"Posts created","Comments created","Topics created","Events created","Event enrollments","Groups created","Group memberships","Group memberships (specified)","Number of Likes"
      4,5d0df1b9-ee3b-4356-9e48-8891fadfff85,ExportUser3,ExportUser3,exportuser3@example.com,never,never,"02/17/2023 - 14:42",Active,"authenticated, verified",0,0,0,0,0,0,0,,0
      3,34fff0f1-8897-4ca8-a682-707c9dd87501,ExportUser2,ExportUser2,exportuser2@example.com,never,never,"02/17/2023 - 14:42",Active,"authenticated, verified",0,0,0,0,0,0,0,,0
      2,85e14211-147c-4f01-ae18-b97300671de6,ExportUser1,ExportUser1,exportuser1@example.com,never,never,"02/17/2023 - 14:42",Active,"authenticated, verified",0,0,0,0,0,0,0,,0
      """
