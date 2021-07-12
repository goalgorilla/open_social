@api @group @stability @DS-956 @DS-4211 @stability-2 @group-edit-open
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
    And I am on "group/add"
    And I press "Continue"
    And I wait for AJAX to finish
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "1 member"

    Given I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    When I click "Test open group" in the "Main content"
    Then I should see "Test open group"

    # As a LU I want to see the information about a group
    When I click "About"
    Then I should see "Description text" in the "Main content"

    When I click "Edit group"
    And I wait for AJAX to finish
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text - edited"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    Then I should see "Description text - edited" in the "Main content"
    And I should see "1 member"

  # DS-706 As a Group Manager I want to manage group memberships
    And I click "Manage members"
    Then I should see "Add members"
    And I should see "Member"
    And I should see "Group Manager One"
    And I should see "Organization"
    And I should see "GoalGorilla"
    And I should see "Role"
    And I should see "Group Manager"
    And I should see "Actions"
    And I should see the button "Actions"
    And I click the xth "0" element with the css ".views-field-operations .btn-group--operations .dropdown-toggle"
    Then I should see the link "Remove"
    When I click "Edit"
    Then I should see "Group Manager One"
    And I should see "Group Manager"
    And I should see the button "Save"
    And I should see the link "Delete"
    And I press "Save"
    And I should see "Member"

  # DS-767 As a Group Manager I want to add a user to the group
    When I click the group member dropdown
    And I click "Add directly"
    And I fill in select2 input ".form-type-select" with "Group Member Two" and select "Group Member Two"
    And I should see the button "Cancel"
    And I press "Save"
    Then I should see "Group Member Two"
    And I should see "Drupal"
    And I should see "Member"
    And I click the xth "0" element with the css ".views-field-operations .btn-group--operations .dropdown-toggle"
    And I click "Remove"
    Then I should see "Remove a member"
    And I should see "Are you sure you want to remove Group Manager One from Test open group?"
    And I should see the button "Remove"
    And I should see the button "Cancel"

  # DS-607 As a Group Manager I shouldn't be able to manage group content from other users
    Given I logout
    And I am logged in as "Group Member Two"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I click "Test open group"
    And I click "Topics"
    And I click "Create Topic"
    And I fill in the following:
      | Title | Test group topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Test group topic"
    Given I logout
    And I am logged in as "Group Manager One"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I click "Test open group"
    And I click "Topics"
    And I click "Test group topic"
    Then I should not see the link "Edit group"
