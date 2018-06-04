@api @gdpr @data-policy @DS-5586 @stability @stability-4
Feature: Manage data policy revisions
  Benefit: In order to manage data policy revisions
  Role: As a LU
  Goal/desire: I want to manage data policy revisions

  Scenario: Successfully manage data policy revisions

    Given I enable the module "social_gdpr"
    Given I am logged in as a user with the "sitemanager" role and I have the following fields:
      | name | behatsitemanager |
    When I am on "data-policy/revisions/add"
    And I fill in the "Description" WYSIWYG editor with "First version of the data policy."
    Then I press "Save"
    When I click "Add new revision"
    Then I should see "Active" in the ".form-item-active-revision:not(.form-disabled) .control-label" element
    And I should see "Create new revision" in the ".form-item-new-revision.form-disabled .control-label" element
    When I fill in the "Description" WYSIWYG editor with "Second version of the data policy."
    And I press "Save"
    Then I should be on "data-policy/revisions"
    And I should see "(current revision)" in the ".revision-1" element
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
    And I click "Edit" in the "Main content" region
    And I check the box "Active"
    And I press "Save"
