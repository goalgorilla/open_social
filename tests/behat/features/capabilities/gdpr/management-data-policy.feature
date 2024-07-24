@api
Feature: Create data policy and add revisions using administration page
  Benefit: In order to create and add new revision to data policy
  Role: As a SM
  Goal/desire: I want to create and add new revision to data policy

  Background:
    # Create users and enable Social GDPR module.
    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
    And I enable the module "social_gdpr"

  Scenario: Create data policy using administration page.

    Given I am logged in as "behatsitemanager"
    And I am on "admin/config/people/data-policy"
    And I should see the link "Add new entity"

    When I click "Add new entity"
    And I should be on "/admin/config/people/data-policy/add"
    And I wait for "1" seconds
    And I should see the text "Name"
    And I should see the text "Description"

    Then I fill in "Name" with "Data policy"
    And I fill in the "edit-field-description-0-value" WYSIWYG editor with "Description for the data policy"
    And I wait for "1" seconds
    And I press "Save"

  Scenario: Add and active a new revision to data policy

    Given  data_policies:
      | name                             | field_description                                     |
      | First version of the data policy | Description for the first version of the data policy  |
    And I am logged in as "behatsitemanager"
    And I am on "/admin/config/people/data-policy"
    And I should see the link "First version of the data policy"
    And I should see "Revisions" in the "table" element
    And I click "Revisions" on the row containing "First version of the data policy"
    And I should see the link "Add new revision"

    # Add new revision to Data Policy created before.
    When I click "Add new revision"
    And I wait for "1" seconds
    And I should see the text "Name"
    And I should see the text "Description"
    And I should see the text "Revision log message"
    And I fill in "Name" with "Second version of the data policy"
    And I fill in the "edit-field-description-0-value" WYSIWYG editor with "Description for the second version of the data policy"
    And I wait for "1" seconds
    And I press "Save"

    # Active this new revision.
    Then I click the xth "0" element with the css ".dropbutton__toggle"
    And I should see "Edit"
    And I click the xth "0" element with the css ".edit.dropbutton-action a"
    And I should see the text "Active"
    And I should see the text "When this field is checked, after submitting the form, a new revision will be created which will be marked active."
    And I should see the text "Revision log message"
    And I should see the text "Briefly describe the changes you have made."
    And I check the box "Active"
    And I press "Save"
