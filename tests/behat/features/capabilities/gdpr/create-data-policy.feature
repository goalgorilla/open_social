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
    Given I am logged in as "behatsitemanager" with the "without consent" permission
    When I am on "admin/config/people/data-policy"

    # Create a new data policy entity, since now it is possible to use multiple checkboxes and multiple entities
    Then I should see the link "Add new entity"
    When I click "Add new entity"
    Then I should be on "/admin/config/people/data-policy/add"
    And I should see the text "Name"
    And I should see the text "Description"
    And I fill in "Name" with "First version of the data policy"
    When I fill in the "Description" WYSIWYG editor with "Description for the first version of the data policy"
    And I press "Save"

    # Create a new revision for the new data policy entity.
    Then I should be on "/admin/config/people/data-policy"
    And I should see the link "First version of the data policy"
    And I should see "Revisions" in the "table" element
    And I click "Revisions" on the row containing "First version of the data policy"
    Then I should see the link "Add new revision"
    And I click "Add new revision"
    And I should see the text "Name"
    And I should see the text "Description"
    And I should see the text "Revision log message"
    And I fill in "Name" with "Second version of the data policy"
    When I fill in the "Description" WYSIWYG editor with "Description for the second version of the data policy"
    And I press "Save"

    # Active this new revision.
    And I click the xth "0" element with the css ".dropbutton__toggle"
    Then I should see "Edit"
    When I click the xth "0" element with the css ".edit.dropbutton-action a"
    Then I should see the text "Active"
    And I should see the text "When this field is checked, after submitting the form, a new revision will be created which will be marked active."
    And I should see the text "Revision log message"
    And I should see the text "Briefly describe the changes you have made."
    And I check the box "Active"
    And I press "Save"

    # Add mandatory checkbox.
    When I am on "admin/config/people/data-policy/settings"
    Then I should see "Consent text"
    And I fill in "Consent text" with "I agree with the [id:1*]"
    And I press "Save configuration"

    # Create a new revision for the first entity, it can be our created entity or some existing entity.
    When I am on "admin/config/people/data-policy"
    Then I click the xth "0" element with the css ".revisions.dropbutton-action a"
    And I should see the link "Add new revision"
    And I click "Add new revision"
    And I should see the text "Name"
    And I should see the text "Description"
    And I should see the text "Revision log message"
    And I fill in "Name" with "Third version of the data policy"
    When I fill in the "Description" WYSIWYG editor with "Description for the third version of the data policy"
    And I press "Save"

    # Active this new revision for the first entity.
    And I click the xth "0" element with the css ".dropbutton__toggle"
    Then I should see "Edit"
    When I click the xth "0" element with the css ".edit.dropbutton-action a"
    Then I should see the text "Active"
    And I should see the text "When this field is checked, after submitting the form, a new revision will be created which will be marked active."
    And I should see the text "Revision log message"
    And I should see the text "Briefly describe the changes you have made."
    And I check the box "Active"
    And I press "Save"

    Given I am logged in as "behatuser"
    Then I should be on "data-policy-agreement?destination=/stream"

    And I should see the text "Our data policy has been updated on"
    And I should see the text "Agreement to the data policy is required for continue using this platform. If you do not agree with the data policy, you will be guided to"
    And I should see the link "the account cancellation"
    And I should see the text "process."
    And I should see the text "I agree with the"
    And I should see the link "third version of the data policy"
    And I should see "Save"

    When I click "the account cancellation"
    Then I should see the text "Are you sure you want to cancel your account?"
    When I click "Cancel"
    And I click "third version of the data policy"
    And I wait for AJAX to finish
    Then I should see "Third version of the data policy" in the ".ui-dialog-title" element
    And I should see the text "Description for the third version of the data policy"

    When I logout
    And I am on "user/register"
    Then I should see the text "I agree with the"
    And I should see the link "third version of the data policy"

    When I click "third version of the data policy"
    And I wait for AJAX to finish
    Then I should see "Third version of the data policy" in the ".ui-dialog-title" element
    And I should see the text "Description for the third version of the data policy"
