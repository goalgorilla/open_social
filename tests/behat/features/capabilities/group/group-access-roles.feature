@api @group @DS-3801 @DS-3776 @DS-3976 @DS-4211 @stability @stability-1
Feature: Group access roles
  Benefit: As a CM+ I want to have full control over groups
  Role: As a CM+
  Goal/desire: Control groups and their content

  Scenario: Successfully access a group I'm not a member of
    Given users:
      | name           | mail                     | status | roles              |
      | Group User One | group_user_1@example.com | 1      |                    |
      | Group User Two | group_user_2@example.com | 1      | sitemanager        |
  # Create a closed group to test the leaving of a closed group
    When I am logged in as "Group User One"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    Then I click radio button "Closed group This is a closed group. Users can only join by invitation and all content added in this group will be hidden for non members." with the id "edit-group-type-closed-group"
    And I press "Continue"
    When I fill in "Title" with "Test closed group 3"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I fill in "Location name" with "Disclosed"
    And I select "NL" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
    | City | Hengelo |
    | Street address | Padangstraat 11 |
    | Postal code | 7556SP |
    And I press "Save"
    Then I should see "Test closed group 3" in the "Main content"

  # Create a topic inside the closed group
    When I am on "user"
    And I click "Groups"
    And I click "Test closed group 3"
    When I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Test closed group 3 topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Save"
    And I should see "Test closed group 3 topic"

  # As a outsider with the role CM+ I should be able to see and manage content from a closed group
    Given I am logged in as a user with the "contentmanager" role
    Then I open and check the access of content in group "Test closed group 3" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test closed group 3 topic"
    When I am on "/all-topics"
    Then I should see "Test closed group 3 topic"
    And I logout

  # As a outsider with the role CM+ I should be able to see and manage content from a closed group
    Given I am logged in as a user with the "sitemanager" role
    Then I open and check the access of content in group "Test closed group 3" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test closed group 3 topic"
    When I am on "/all-topics"
    Then I should see "Test closed group 3 topic"
    When I am on "/all-groups"
    And I click "Test closed group 3"

  # DS-647 As a LU I want to join a group
    Then I should see the link "Join" in the "Hero block"
    And I click "Join"
    And I should see "Join group Test closed group 3"
    And I should see the button "Cancel"
    And I should see the button "Join group"
    And I press "Join group"
    And I am on "user"
    And I click "Groups"
    Then I click "Test closed group 3"
    And I should see the button "Joined"

  # Check if there is still access on all content if the CM+ joined the closed group
    Then I open and check the access of content in group "Test closed group 3" and I expect access "allowed"

  # As a CM+ member of this closed group I want to leave the group
    When I click "Test closed group 3"
    Then I should see the button "Joined"
    When I click the xth "4" element with the css ".dropdown-toggle"
    And I should see the link "Leave group"
    And I click "Leave group"
    And I should see "This action cannot be undone."
    And I should see the button "Cancel"
    And I should see the button "Leave group"
    And I press "Leave group"
    And I should see "Groups"
    And I should not see "Test closed group 3"

  # Add a user with CM+ role (that has 'manage all groups' permission) on the Manage members tab so he gets the group admin role.
    When I am logged in as "Group User One"
    When I click "Test closed group 3"
    And I click "Manage members"
    And I click "Add member"
    And I fill in "Group User Two" for "Select a member"
    And I press "Save"
    Then I should see "Group Admin"
