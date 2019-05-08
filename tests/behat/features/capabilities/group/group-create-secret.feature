@api @group @DS-3428 @DS-4211 @stability @stability-1 @group-create-secret
Feature: Create Secret Group
  Benefit: I want to create a secret group, where only group members can see the content.
  Role: As a LU
  Goal/desire: I want to create Secret Groups

  Scenario: Successfully create secret group
    Given users:
      | name             | mail                     | status | roles       |
      | Group User One   | group_user_1@example.com | 1      | sitemanager |
      | Group User Two   | group_user_2@example.com | 1      |             |
    And I enable the module "social_group_secret"
    And I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    Then I should not see "Secret group"
    Given I am logged in as "Group User One"
    And I am on "user"
    And I click "Groups"
    And I click "Add group"
    Then I click radio button "Secret group This is a secret group. Users can only join by invitation and the group itself and its content are hidden from non members." with the id "edit-group-type-secret-group"
    And I press "Continue"
    When I fill in "Title" with "Test secret group"
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
    And I should see "Test secret group" in the "Main content"
    And I should see "Disclosed"
    And I should see "1 member"
    And I should see "Joined"
    And I should not see the link "Read more"

    And I should see "Test secret group" in the "Hero block"
    And I should see the button "Joined"
    And I should see the link "Edit group" in the "Hero block"
    And I should see "Disclosed" in the "Hero block"
    And I should see "Padangstraat 11" in the "Hero block"
    And I should see "7556SP" in the "Hero block"

  # Create a post inside the secret group, visible to group members only
    When I click "Stream"
    And I fill in "Say something to the group" with "This is a secret group post."
    And I select post visibility "Group members"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a secret group post."

  # Create a topic inside the secret group
    When I click "Topics"
    And I break
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Test secret group topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Save"
    And I should see "Test secret group topic"

  # Create an event inside the secret group
    And I click "Test secret group"
    When I click "Events"
    And I should see the link "Create Event" in the "Sidebar second"
    And I click "Create Event"
    And I fill in the following:
      | Title | Test secret group event |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-date | 2025-01-01 |
      | Time  | 11:00:00    |
      | Location name       | Technopark |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Save"
    And I should see "Test secret group event"

  # Lets add another user on the Manage members tab.
    When I click "Test secret group"
    And I click "Manage members"
    And I click "Add members"
    And I fill in "Group User Two" for "Select members to add"
    And I press "Save"
    Then I click "Members"
    And I should see "Group User Two"
    And I logout

  # Now login as user two.
    Given I am logged in as "Group User Two"
    And I am on "/all-groups"
    Then I should see "Test secret group"
    When I click "Test secret group"
    And I should see the button "Joined"
    And I should see "Test secret group topic"
    And I should see "Test secret group event"
    And I should see "This is a secret group post."
    And I open and check the access of content in group "Test secret group" and I expect access "allowed"
    And I logout
    And I open and check the access of content in group "Test secret group" and I expect access "denied"

  # As a non-member of the secret group, I should not see anything.
    Given users:
      | name           | mail                      | status |
      | Platform User  | platform_user@example.com | 1      |
    And I am logged in as "Platform User"
    And I open and check the access of content in group "Test secret group" and I expect access "denied"
    And I am on "/all-groups"
    Then I should not see "Test secret group"

  # As a non-member, I should not be able to see topics from a secret group across the platform
    When I am on "stream"
    Then I should not see "Test secret group topic"
    When I am on "/all-topics"
    Then I should not see "Test secret group topic"
    And I logout

  # As a outsider with the role CM+ I should be able to see and manage content from a secret group
    Given I am logged in as a user with the "contentmanager" role
    Then I open and check the access of content in group "Test secret group" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test secret group topic"
    When I am on "/all-topics"
    Then I should see "Test secret group topic"
    And I logout

  # As a outsider with the role CM+ I should be able to see and manage content from a secret group
    Given I am logged in as a user with the "sitemanager" role
    Then I open and check the access of content in group "Test secret group" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test secret group topic"
    When I am on "/all-topics"
    Then I should see "Test secret group topic"
    And I logout

  # As a member of this secret group I want to leave the group
    Given I am logged in as "Group User Two"
    And I am on "/all-groups"
    Then I should see "Test secret group"
    When I click "Test secret group"
    And I should see the button "Joined"
    And I click the element with css selector "#hero .dropdown-toggle"
    And I should see the link "Leave group"
    And I click "Leave group"
    And I should see "This action cannot be undone."
    And I should see the button "Cancel"
    And I should see the button "Leave group"
    And I press "Leave group"
    And I should see "Groups"
    And I should not see "Test secret group 2"

   # Delete the group and all content of the group
    When I am logged in as "Group User One"
    And I am on "user"
    And I click "Groups"
    And I click "Test secret group"
    And I click "Edit group"
    And I click "Delete"
    And I should see "Are you sure you want to delete your group"
    And I should see the button "Cancel"
    And I should see the button "Delete"
    And I press "Delete"
    And I wait for AJAX to finish
    Then I should see "Your group and all of its topics, events and posts have been deleted."
    When I am on "user"
    And I click "Groups"
    Then I should not see "Test secret group"
    When I am on "/all-topics"
    Then I should not see "Test secret group topic"
    When I am on "/all-events"
    Then I should not see "Test secret group event"
