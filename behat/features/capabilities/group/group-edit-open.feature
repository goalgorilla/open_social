@api @group @stability @DS-956
Feature: Edit my group as a group manager
  Benefit: So I can update the group based on the changes in the group
  Role: As a GM
  Goal/desire: I want to edit my Groups

  Scenario: Successfully create and edit my group as a group manager
    Given I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Description text"
    And I should see "1 member"

    Given I am on "user"
    And I click "Groups"
    And I click "Test open group" in the "Main content"
    Then I should see "Test open group"
    And I should see "Description text"
    And I should see "Edit"

    When I click "Edit"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text - edited"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Description text - edited"
    And I should see "1 member"
