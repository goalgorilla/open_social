@api @group @DS-811 @DS-816 @stability @stability-1
Feature: Create Open Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a LU
  Goal/desire: I want to create Open Groups

  Scenario: Successfully create open group
    Given users:
      | name           | mail                     | status |
      | Group User One | group_user_1@example.com | 1      |
      | Group User Two | group_user_2@example.com | 1      |
    And I am logged in as "Group User One"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    Then I click radio button "Open group This is an open group. Users may join without approval and all content added in this group will be visible for non members as well." with the id "edit-group-type-open-group"
    And I press "Continue"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I fill in "Location name" with "Technopark"
    And I select "UA" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
      | City | Lviv |
      | Street address | Fedkovycha 60a |
      | Postal code | 79000 |
      | Oblast | Lviv oblast |
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Technopark"
    And I should see "1 member"
    And I should see "Joined"
    And I should see the link "Read more"

  # DS-761 As a LU I want to view the hero area of a group
    And I click "Test open group"
    And I should see "Test open group" in the "Hero block"
    And I should see the button "Joined"
    And I click the xth "4" element with the css ".dropdown-toggle"
    And I should see the link "Leave group"
    And I should see the link "Edit group" in the "Hero block"
    And I should see "Technopark" in the "Hero block"
    And I should see "Fedkovycha 60a" in the "Hero block"
    And I should see "79000" in the "Hero block"
    And I should see "Lviv" in the "Hero block"
    And I should see "Lviv oblast" in the "Hero block"

    # As a LU I want to see the information about a group
    When I click "About"
    Then I should see "Description text" in the "Main content"

  # @TODO: Uncomment this when Group hero caching will be fixed.
  # DS-648 As a LU I want to see the members of a group
    And I logout
    And I am logged in as "Group User Two"
    And I am on "all-members"
    And I click "Group User One"
  # And I should see "Recently joined groups" in the "Sidebar second"
    And I should see "Test open group" in the "Sidebar second"
    And I click "Groups"
    And I should see "Test open group" in the "Main content"
    And I should not see the link "Add a group" in the "Main content"
    And I click "Test open group"
  # And I should see "Newest members" in the "Sidebar second"
  # And I should see "Group User One" in the "Sidebar second"
    And I click "Members"
    And I should see "Members of Test open group"
    And I should see "Group User One"

  # DS-647 As a LU I want to join a group
    And I should see the link "Join" in the "Hero block"
    And I click "Join"
    And I should see "Join group Test open group"
    And I should see the button "Cancel"
    And I should see the button "Join group"
    And I press "Join group"
    And I should see "Test open group"
    And I click "Test open group"
    And I should see the button "Joined"

  # DS-643 As a LU I want to see the events of a group
    When I click "Events"
    And I should see the link "Create Event" in the "Sidebar second"
    And I click "Create Event"
    And I fill in the following:
      | Title | Test group event |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-date | 2025-01-01 |
      | Time  | 11:00:00    |
      | Location name       | Technopark |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
  # TODO: Change title of this button when we will have one step
    And I click radio button "Community - visible only to logged in members" with the id "edit-field-content-visibility-community"
    And I press "Save and publish"
    And I should see "Test group event"
    And I should see "Body description text" in the "Main content"
    And I should see the button "Enroll"
  # DS-639 As a LU I want to see which group the content belongs to, on the detail page
    And I should see the link "Test open group" in the "Main content"
    And I click "Test open group"
  # TODO: And I should see "Upcoming Events" in the "Sidebar second"
  # And I should see "Test group event" in the "Sidebar second"
  # And I should see "1 Jan" in the "Sidebar second"
    And I click "Events"
    And I should see "Test group event" in the "Main content"
    And I should see "Test open group" in the "Main content"

  # DS-644 As a LU I want to see the topics of a group
    When I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in the following:
      | Title |Test group topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I click radio button "Community - visible only to logged in members" with the id "edit-field-content-visibility-community"
    And I press "Save and publish"
    And I should see "Test group topic"
    And I should see "Body description text" in the "Main content"
   # DS-639 As a LU I want to see which group the content belongs to, on the detail page
    And I should see the link "Test open group" in the "Main content"
    And I click "Test open group"
  # TODO: And I should see "Latest Topics" in the "Sidebar second"
  # And I should see "Test group topic" in the "Sidebar second"
    And I click "Topics"
    And I should see "Test group topic" in the "Main content"
    And I should see "Test open group" in the "Main content"

  # As a outsider with the role CM+ I should be able to see and manage content from a closed group
    Given I am logged in as a user with the "contentmanager" role
    Then I open and check the access of content in group "Test open group" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test group topic"
    When I am on "/all-topics"
    Then I should see "Test group topic"
    And I logout

  # As a outsider with the role CM+ I should be able to see and manage content from a closed group
    Given I am logged in as a user with the "sitemanager" role
    Then I open and check the access of content in group "Test open group" and I expect access "allowed"
    When I am on "stream"
    Then I should see "Test group topic"
    When I am on "/all-topics"
    Then I should see "Test group topic"
    And I logout

  # DS-703 As a LU I want to leave a group
    Given I am logged in as "Group User Two"
    And I am on "user"
    And I click "Groups"
    And I click "Test open group"
    And I should see the button "Joined"
    And I click the xth "4" element with the css ".dropdown-toggle"
    And I should see the link "Leave group"
    And I click "Leave group"
    And I should see "Test open group" in the "Hero block"
    And I should see "This action cannot be undone."
    And I should see the button "Cancel"
    And I should see the button "Leave group"
    And I press "Leave group"
    And I should see "Group User Two" in the "Hero block"
    And I should see "Groups"
    And I should not see "Test open group"

  # DS-722 As an outsider I am not allowed to enrol to an event in group
    When I click "Events"
    And I click "Test group event"
    And I should not see "Enroll" in the "Hero buttons"

  # Check for latest groups block on LU homepage
    When I am on "stream"
  # And I should see "Newest groups" in the "Sidebar second"
    And I should see the link "Test open group" in the "Sidebar second"
