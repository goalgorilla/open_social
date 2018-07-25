@api @gdpr @data-policy @data-policy-revisions @DS-5586 @stability @stability-4
Feature: Manage data policy revisions
  Benefit: In order to be able to have multiple versions
  Role: As a SM
  Goal/desire: I want to manage data policy revisions

  Scenario: Successfully manage data policy revisions

    Given I enable the module "social_gdpr"

    Given I am logged in as a user with the "sitemanager" role and I have the following fields:
      | name | behatsitemanager |
    When I am on "data-policy/revisions/add"
    Then I should see "Active" in the ".form-item-active-revision:not(.form-disabled) .control-label" element
    And I should see "Create new revision" in the ".form-item-new-revision.form-disabled .control-label" element
    When I fill in the "Description" WYSIWYG editor with "Second version of the data policy."
    And I press "Save"
    Then I should be on "data-policy/revisions"
    And I should see "(current revision)" in the ".revision-1" element
    And I should not see "(current revision)" in the ".revision-2" element

    When I click the xth "0" element with the css ".revision-2 .dropdown-toggle"
    Then I should see the link "Edit" in the "Main content" region
    And I should see the link "Revert" in the "Main content" region

    When I logout
    And I click "Sign up"
    And I click "data policy"
    And I wait for AJAX to finish
    Then I should see the text "First version of the data policy."

    When I am logged in as "behatsitemanager"
    And I am on "data-policy/revisions"
    And I click the xth "0" element with the css ".revision-2 .dropdown-toggle"
    # Press "Edit" button in operations drop-down menu
    And I click the xth "1" element with the css ".revision-2 .dropdown-menu a"
    And I check the box "Active"
    And I press "Save"
    Then I should not see "(current revision)" in the ".revision-1" element
    And I should see "(current revision)" in the ".revision-2" element

    When I logout
    And I click "Sign up"
    And I click "data policy"
    And I wait for AJAX to finish
    Then I should see the text "Second version of the data policy."

    When I am logged in as "behatsitemanager"
    And I am on "data-policy/revisions/add"
    And I fill in the "Description" WYSIWYG editor with "Third version of the data policy."
    And I check the box "Active"
    And I press "Save"
    Then I should not see "(current revision)" in the ".revision-1" element
    And I should not see "(current revision)" in the ".revision-2" element
    And I should see "(current revision)" in the ".revision-3" element

    When I am on "data-policy/revisions"
    And I click the xth "0" element with the css ".revision-1 .dropdown-toggle"
    # Press "Revert" button in operations drop-down menu
    And I click the xth "1" element with the css ".revision-1 .dropdown-menu a"
    And I wait for AJAX to finish
    Then I should see the text "Are you sure to revert this revision"
    And I should see the text "After making this revision active users will be asked again to agree with this revision."
    And I should see the link "Cancel"
    And I should see "Yes" in the ".ui-dialog .form-submit" element

    When I press "Yes"
    Then I should see the success message containing "Data policy has been reverted to the revision from"
    And I should not see "(current revision)" in the ".revision-1" element
    And I should not see "(current revision)" in the ".revision-2" element
    And I should see "(current revision)" in the ".revision-3" element
    And I should not see "(current revision)" in the ".revision-4" element

    When I logout
    And I click "Sign up"
    And I click "data policy"
    And I wait for AJAX to finish
    Then I should see the text "Third version of the data policy."
