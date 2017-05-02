@api @group @stability @DS-956
Feature: Edit my group as a group manager
  Benefit: So I can update the group based on the changes in the group
  Role: As a GM
  Goal/desire: I want to edit my Groups

  Scenario: Successfully create and edit my group as a group manager
    Given users:
      | name              | mail             | field_profile_organization | status |
      | Group Manager One | gm_1@example.com | GoalGorilla                | 1      |
      | Group Member Two  | gm_2@example.com | Drupal                     | 1      |
    And I am logged in as "Group Manager One"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    And I wait for AJAX to finish
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "1 member"

    Given I am on "user"
    And I click "Groups"
    And I click "Test open group" in the "Main content"
    Then I should see "Test open group"

    # As a LU I want to see the information about a group
    When I click "About"
    Then I should see "Description text" in the "Main content"

    When I click "Edit group"
    And I wait for AJAX to finish
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text - edited"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I click "Test open group" in the "Main content"
    And I click "About"
    Then I should see "Description text - edited" in the "Main content"
    And I should see "1 member"

  # DS-706 As a Group Manager I want to manage group memberships
    And I click "Manage members"
    Then I should see "Members of Test open group"
    And I should see the link "Add member"
    And I should see "Member"
    And I should see "Group Manager One"
    And I should see "Organisation"
    And I should see "GoalGorilla"
    And I should see "Role"
    And I should see "Group Manager"
    And I should see "Operations"
    And I should see the button "Edit"
    When I press the "Toggle Dropdown" button
    Then I should see the link "Delete"
    When I press "Edit"
    Then I should see "Group Manager One"
    And I should see "Group Manager"
    And I should see the button "Save"
    And I should see the link "Delete"
    And I press "Save"
    And I should see "Members of Test open group"

  # DS-767 As a Group Manager I want to add a user to the group
    When I click "Add member"
    Then I should see "Add a member"
    And I fill in "Select a member" with "Group Member Two"
    And I should see "Group roles"
    And I should see "Group Manager"
    And I should see the button "Cancel"
    And I press "Save"
    Then I should see "Members of Test open group"
    And I should see "Group Member Two"
    And I should see "Drupal"
    And I should see "Member"
    And I click the xth "2" element with the css ".form-submit"
    And I show hidden checkboxes
    And I check the box "Group Manager"
    And I press "Save"
    And I click the xth "5" element with the css ".dropdown-toggle"
    And I click "Delete"
    Then I should see "This action cannot be undone"
    And I should see the button "Delete"
    And I should see the link "Cancel"
    And I click "Cancel"

  # DS-607 As a Group Manager I shouldn't be able to manage group content from other users
    And I logout
    And I am logged in as "Group Member Two"
    And I am on "user"
    And I click "Groups"
    And I click "Test open group"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Test group topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Continue to final step"
    And I press "Create node in group"
    And I should see "Test group topic"
    And I logout
    And I am logged in as "Group Manager One"
    And I am on "user"
    And I click "Groups"
    And I click "Test open group"
    And I click "Topics"
    And I click "Test group topic"
    And I should not see the link "Edit group"

  # DS-705 As a Group Manager I want to delete my own group
    And I logout
    And I am logged in as "Group Manager One"
    And I am on "user"
    And I click "Groups"
    And I click "Test open group"
    And I click "Edit group"
    And I click "Delete"
    And I should see "This action cannot be undone."
    And I should see the link "Cancel"
    And I should see the button "Delete"
    And I press "Delete"
