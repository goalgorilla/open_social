@api @gdpr @data-policy @data-policy-create @DS-5586 @stability @stability-4
Feature: Create data policy and view new policy
  Benefit: In order to have a clear data policy users need to accept
  Role: As a SM
  Goal/desire: I want to create a data policy

  Scenario: Successfully create and view a data policy

    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
      | behatuser        | behatuser@example.com        | 1      |             |

    Given I enable the module "social_gdpr"

    Given I am logged in as "behatuser"
    Then I should be on the homepage

    Given I am logged in as "behatsitemanager"
    And I am on "admin/config/people/data-policy"
    Then I should be on "data-policy/revisions"
    And I should see the heading "Revisions" in the "Hero block" region
    And I should see the link "Details" in the "Tabs" region
    And I should see the link "Revisions" in the "Tabs" region
    And I should see the text "Revision"
    And I should see the text "Operations"
    And I should see the text "List is empty."
    And I should see the link "Add new revision" in the "Sidebar second" region

    When I click "Details"
    Then I should be on "data-policy"
    And I should see the text "Data policy is not created."

    When I click "Revisions"
    And I click "Add new revision"
    Then I should be on "data-policy/revisions/add"
    And I should see the text "Description"
    And I should see the text "Active"
    And I should see "Active" in the ".form-item-active-revision.form-disabled .control-label" element
    And I should see the text "When this field is checked, after submitting the form, a new revision will be created which will be marked active."
    And I should see "Create new revision" in the ".form-item-new-revision.form-disabled .control-label" element
    And I should see the text "Revision log message"
    And I should see the text "Briefly describe the changes you have made."
    And I should see "Save"

    When I press "Save"
    Then I should see the error message "1 error has been found: Description"

    When I fill in the "Description" WYSIWYG editor with "First version of the data policy."
    And I press "Save"
    Then I should be on "data-policy/revisions"
    And I should see the success message "Created new revision."
    And I should see the link "behatsitemanager"
    And I should see the text "(current revision)"
    And I should see "View"

    When I press "View"
    Then I should be on "data-policy/revisions/1?data_policy=1"
    And I should see the text "Description"
    And I should see the text "First version of the data policy."
    And I should see the text "Authored by"
    And I should see the link "behatsitemanager"

    When I am on "data-policy"
    Then I should not see the text "Data policy is not created."

    Given I am logged in as "behatuser"
    Then I should be on "data-policy-agreement?destination=/stream"
    And I should see the heading "Data policy agreement" in the "Page title block" region
    And I should see the text "Our data policy has been updated on"
    And I should see the text "Agreement to the data policy is required for continue using this platform. If you do not agree with the data policy, you will be guided to"
    And I should see the link "the account cancellation"
    And I should see the text "process."
    And I should see the text "I agree with the"
    And I should see the link "data policy"
    And I should see "Save"

    When I click "the account cancellation"
    Then I should see the heading "Cancel account" in the "Page title block" region
    When I click "Cancel"
    And I click "data policy"
    And I wait for AJAX to finish
    Then I should see "Data policy" in the ".ui-dialog-title" element
    And I should see the text "First version of the data policy."

    When I logout
    And I click "Sign up"
    Then I should see the text "I agree with the"
    And I should see the link "data policy"

    When I click "data policy"
    And I wait for AJAX to finish
    Then I should see "Data policy" in the ".ui-dialog-title" element
    And I should see the text "First version of the data policy."
