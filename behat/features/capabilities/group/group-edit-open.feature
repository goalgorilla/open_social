@api @group @stability @DS-956
Feature: Edit my group as a group manager
  Benefit: So I can update the group based on the changes in the group
  Role: As a GM
  Goal/desire: I want to edit my Groups

  Scenario: Successfully create and edit my group as a group manager
    Given users:
      | name              | mail             | field_profile_organization | status |
      | Group Manager One | gm_1@example.com | GoalGorilla                | 1      |
    And I am logged in as "Group Manager One"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    When I fill in "Title" with "Test open group"
    And I fill in "edit-field-group-description-0-value" with "Description text"
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
    And I fill in "edit-field-group-description-0-value" with "Description text - edited"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Description text - edited"
    And I should see "1 member"

  # DS-706 As a Group Manager I want to manage group memberships
    When I click "Test open group" in the "Main content"
    And I click "Members"
    Then I should see "Members of Test open group"
    And I should see the link "Add member"
    And I should see "Member"
    And I should see "Organisation"
    And I should see "Role"
    And I should see "Operations"
    And I should see "Group Manager One"
    And I should see "GoalGorilla"
    And I should see "Group Manager"
    And I should see the button "Edit"
    When I press the "Toggle Dropdown" button
    Then I should see the link "Delete"
    When I press "Edit"
    Then I should see "Group Manager One"
    And I should see "Group roles"
    And I should see "Group Manager"
    And I should see the button "Save"
    And I should see the link "Delete"

