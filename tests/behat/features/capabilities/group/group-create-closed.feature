@api @group @DS-3428 @stability
Feature: Create Closed Group
  Benefit: I want to create a closed group, where only group members can see the content.
  Role: As a LU
  Goal/desire: I want to create Closed Groups

  Scenario: Successfully create closed group
    Given users:
      | name           | mail                     | status |
      | Group User One | group_user_1@example.com | 1      |
      | Group User Two | group_user_2@example.com | 1      |
    And I am logged in as "Group User One"
    And I am on "/group/add/closed_group"
    When I fill in "Title" with "Test closed group"
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
    And I should see "Test closed group" in the "Main content"
    And I should see "Disclosed"
    And I should see "1 member"
    And I should see "Joined"
    And I should see the link "Read more"

    And I click "Test closed group"
    And I should see "Test closed group" in the "Hero block"
    And I should see the button "Joined"
    And I should see the link "Edit group" in the "Hero block"
    And I should see "Disclosed" in the "Hero block"
    And I should see "Padangstraat 11" in the "Hero block"
    And I should see "7556SP" in the "Hero block"

  # Create a post inside the closed group, visible to group members only
    When I fill in "Say something to the group" with "This is a closed group post."
    And I select post visibility "Group members"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a closed group post."

  # Create a topic inside the closed group
    When I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Test closed group topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Save and publish"
    And I should see "Test closed group topic"

  # Create an event inside the closed group
    When I click "Events"
    And I should see the link "Create Event" in the "Sidebar second"
    And I click "Create Event"
    And I fill in the following:
      | Title | Test closed group event |
      | Date  | 2025-01-01  |
      | Time  | 11:00:00    |
      | Location name       | Technopark |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Save and publish"
    And I should see "Test closed group event"

  # As a non-member of the closed group, when I click on the closed group
  # I should be redirected to /group/x/about. I should not see the stream, events or topics page.
    Given users:
      | name           | mail                      | status |
      | Platform User  | platform_user@example.com | 1      |
    And I am logged in as "Platform User"
    And I am on "/all-groups"
    Then I should see "Test closed group"
    When I click "Test closed group"
    Then I should see "About Group"
    Then I should not see "Test closed group topic"
    Then I should not see "Test closed group event"
    And I should not see "This is a closed group post."
    And I should not see "Stream"
    And I should not see "Events"
    And I should not see "Topics"
    And I should not see "all upcoming events"
    And I should not see "all topics"

  # As a non-member, I should not be able to join a closed group
    And I should not see the link "Join" in the "Hero block"

  # As a non-member, I should not be able to see topics from a closed group across the platform
    When I am on "stream"
    Then I should not see "Test closed group topic"
    When I am on "/all-topics"
    Then I should not see "Test closed group topic"

  # As a member of the closed group I want to leave the group
    When I am logged in as "Group User One"
    And I am on "/all-groups"
    Then I click "Test closed group"
    And I should see the button "Joined"
    When I click the xth "4" element with the css ".dropdown-toggle"
    And I should see the link "Leave group"
    And I click "Leave group"
    And I should see "Test closed group" in the "Hero block"
    And I should see "This action cannot be undone."
    And I should see the button "Cancel"
    And I should see the button "Leave group"
    And I press "Leave group"
    And I should see "Groups"
    And I should not see "Test closed group"

