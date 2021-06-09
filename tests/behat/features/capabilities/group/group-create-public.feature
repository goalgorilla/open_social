@api @group @DS-4211 @ECI-632 @stability @stability-1 @group-create-public
Feature: Create Public Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a LU
  Goal/desire: I want to create Public Groups

  Scenario: Successfully create public group
    Given users:
      | name         | mail                     | status |
      | GivenUserOne | group_user_1@example.com | 1      |
      | GivenUserTwo | group_user_2@example.com | 1      |
      | Outsider     | outsider@example.com     | 1      |
    Given "event_types" terms:
      | name     |
      | Webinar  |
      | Other    |
    And I am logged in as "GivenUserOne"
    And I am on "group/add"
    Then I click radio button "Public group This is a public group. Users may join without approval and all content added in this group will be visible to all community members and anonymous users." with the id "edit-group-type-public-group"
    And I press "Continue"
    When I fill in "Title" with "Test public group"
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
    And I should see "Test public group" in the "Main content"
    And I should see "Technopark"
    And I should see "1 member"
    And I should see "Joined"
    And I should not see the link "Read more"

    And I should see "Test public group" in the "Hero block"
    And I should see the button "Joined"
    And I press "Joined"
    And I should see the link "Leave group"
    And I should see the link "Edit group" in the "Hero block"
    And I should see "Technopark" in the "Hero block"
    And I should see "Fedkovycha 60a" in the "Hero block"
    And I should see "79000" in the "Hero block"
    And I should see "Lviv" in the "Hero block"
    And I should see "Lviv oblast" in the "Hero block"

    When I click "About" in the "Tabs"
    Then I should see "Description text" in the "Main content"

    And I am logged in as "GivenUserTwo"
    And I am on "all-members"
    And I click "GivenUserOne"
    And I should see "Test public group" in the "Sidebar second"
    And I click "Groups" in the "Tabs"
    And I should see "Test public group" in the "Main content"
    And I should not see the link "Add a group" in the "Main content"
    And I click "Test public group"
    And I click "Members"
    And I should see "GivenUserOne"

    When I click "Stream" in the "Tabs"
    And I should see the link "Join"
    And I click "Join"
    And I should see "Join group Test public group"
    And I should see the button "Cancel"
    And I should see the button "Join group"
    And I press "Join group"
    And I am on "user"
    And I click "Groups" in the "Tabs"
    And I click "Test public group"
    Then I should see the button "Joined"

    # Create a post inside the public group, visible to public only
    When I fill in "Say something to the group" with "This is a public group post."
    And I select post visibility "Public"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a public group post."

    When I click "Events"
    And I should see the link "Create Event" in the "Sidebar second"
    And I click "Create Event"
    And I fill in the following:
      | Title | Test group event |
      | edit-field-event-date-0-value-date | 2025-01-01 |
      | edit-field-event-date-0-value-time | 11:00:00   |
      | edit-field-event-date-end-0-value-date | 2025-01-01 |
      | edit-field-event-date-end-0-value-time | 11:00:00 |
      | Location name | Technopark |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Create event"
    And I should see "Test group event"
    And I should see "Body description text" in the "Main content"
    And I should see the button "Enroll"
    And I should see the link "Test public group"
    And I click "Test public group"
    And I click "Events"
    And I should see "Test group event" in the "Main content"
    And I should see "Test public group" in the "Main content"

    When I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in "Title" with "Test group topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Create topic"
    And I should see "Test group topic"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Test public group"
    And I click "Test public group"
    And I click "Topics"
    And I should see "Test group topic" in the "Main content"
    And I should see "Test public group" in the "Main content"

    When I click "Stream" in the "Tabs"
    And I press "Joined"
    And I should see the link "Leave group"
    And I click "Leave group"
    And I should see "This action cannot be undone."
    And I should see the button "Cancel"
    And I should see the button "Leave group"
    And I press "Leave group"
    And I should see "GivenUserTwo"
    And I should see "Groups"
    And I should not see "Test public group"

    # DS-722 As an outsider I am not allowed to enrol to an event in group
    Given I am logged in as "Outsider"
    When I am on "/community-events"
    And I click "Test group event"
    And I should not see "Enroll" in the "Hero buttons"

    When I am on "stream"
    And I should see the link "Test public group" in the "Sidebar second"

    When I logout
    And I am on "all-groups"
    Then I should see the link "Test public group"

    When I click "Test public group"
    Then I should see "This is a public group post."
