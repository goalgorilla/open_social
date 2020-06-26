@api @group @DS-4211 @ECI-632 @stability @stability-1 @group-create-flexible
Feature: Create flexible Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a LU
  Goal/desire: I want to create flexible Groups

  Scenario: Successfully create flexible group
    Given I enable the module "social_group_flexible_group"
    Given users:
      | name           | mail                     | status |
      | GivenUserOne   | group_user_1@example.com | 1      |
      | GivenUserTwo   | group_user_2@example.com | 1      |
      | GivenUserThree | group_user_2@example.com | 1      |
    Given "event_types" terms:
      | name     |
      | Webinar  |
      | Other    |
    And I am logged in as "GivenUserOne"
    And I am on "group/add"
    Then I click radio button "Flexible group By choosing this option you can customize many group settings to your needs." with the id "edit-group-type-flexible-group"
    And I press "Continue"
    When I fill in "Title" with "Test flexible group"
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
    And I should see "Test flexible group" in the "Main content"
    And I should see "Technopark"
    And I should see "1 member"
    And I should see "Joined"
    And I should not see the link "Read more"

    And I should see "Test flexible group" in the "Hero block"
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
    And I should see "Test flexible group" in the "Sidebar second"
    And I click "Groups" in the "Tabs"
    And I should see "Test flexible group" in the "Main content"
    And I should not see the link "Add a group" in the "Main content"
    And I click "Test flexible group"
    And I click "Members"
    And I should see "GivenUserOne"

    When I click "Stream" in the "Tabs"
    And I should see the link "Join"
    And I click "Join"
    And I should see "Join group Test flexible group"
    And I should see the button "Cancel"
    And I should see the button "Join group"
    And I press "Join group"
    And I am on "user"
    And I click "Groups" in the "Tabs"
    And I click "Test flexible group"
    Then I should see the button "Joined"

    # Create a post inside the flexible group, visible to public.
    When I fill in "Say something to the group" with "This is a flexible group post."
    And I select post visibility "Public"
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a flexible group post."

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
    And I click radio button "Public - visible to everyone including people who are not a member" with the id "edit-field-content-visibility-public"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Save"
    And I should see "Test group event"
    And I should see "Body description text" in the "Main content"
    And I should see the button "Enroll"
    And I should see the link "Test flexible group"
    And I click "Test flexible group"
    And I click "Events"
    And I should see "Test group event" in the "Main content"
    And I should see "Test flexible group" in the "Main content"

    # Create a topic in the flexible group, visible to public.
    When I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in "Title" with "Test group public topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Public - visible to everyone including people who are not a member" with the id "edit-field-content-visibility-public"
    And I click radio button "Discussion"
    And I press "Save"

    # Create a topic in the flexible group, visible to group members only.
    When I click "Test flexible group"
    And I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in "Title" with "Test group private topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Group members - visible only to group members" with the id "edit-field-content-visibility-group"
    And I click radio button "Discussion"
    And I press "Save"

    # Check the topic is shown correctly to author after saving.
    And I should see "Test group private topic"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Test flexible group"

    # Check the topic visibilities as member of the group.
    # all-topics overview.
    Given I am at "all-topics"
    Then I should see "Test group public topic" in the "Main content"
    And I should see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should see "Test group public topic" in the "Main content"
    And I should see "Test group private topic" in the "Main content"

    # Check the topic visibilities as AN.
    Given I logout
    # all-topics overview.
    And I am at "all-topics"
    Then I should see "Test group public topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should see "Test group public topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Check the topic visibilities as LU not part of the group.
    Given I am logged in as "GivenUserThree"
    # all-topics overview.
    And I am at "all-topics"
    Then I should see "Test group public topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should see "Test group public topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Test flexible group with only public content visibility for LU.
    Given I am logged in as "GivenUserOne"
    And I am on "group/add"
    Then I click radio button "Flexible group By choosing this option you can customize many group settings to your needs." with the id "edit-group-type-flexible-group"
    And I press "Continue"
    When I show hidden checkboxes
    Then I uncheck the box "field_group_allowed_visibility[community]"
    Then I uncheck the box "field_group_allowed_visibility[group]"
    When I fill in "Title" with "Cheesy test of flexible group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Cheesy description text"
    And I fill in "Location name" with "Cheese St"
    And I select "US" from "Country"
    Then I wait for AJAX to finish
    And I press "Save"
    # Check this group as an anonymous user.
    And I logout
    Then I am on "all-groups"
    And I should see "Cheesy test of flexible group"
    When I click "Cheesy test of flexible group"
    Then I should see the link "Stream"
    And I should see the link "About"
    And I should see the link "Events"
    And I should see the link "Topics"
    And I should see the link "Members"
    # Check this as an user with SM permissions.
    Given I am logged in as an "authenticated user"
    When I am on "all-groups"
    And I click "Cheesy test of flexible group"
    Then I should see the link "Stream"
    And I should see the link "About"
    And I should see the link "Events"
    And I should see the link "Topics"
    And I should see the link "Members"
