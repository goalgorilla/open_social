@api @group @DS-4211 @ECI-632 @stability @stability-1 @group-create-flexible @javascript
Feature: Create flexible Group
  Benefit: So I can work together with others in a relative small circle
  Role: As a LU
  Goal/desire: I want to create flexible Groups

  Scenario: Successfully create flexible group
    Given I enable the module "social_group_flexible_group"
    Given users:
      | name           | mail                     | status | roles |
      | GivenUserOne   | group_user_1@example.com | 1      |       |
      | GivenUserTwo   | group_user_2@example.com | 1      |       |
      | GivenUserThree | group_user_3@example.com | 1      |       |
      | SiteManagerOne | site_manager@example.com | 1      | sitemanager  |
    Given "event_types" terms:
      | name     |
      | Webinar  |
      | Other    |
    And I am logged in as "GivenUserOne"
    And I am on "group/add"
    Then I click radio button "Flexible group By choosing this option you can customize many group settings to your needs." with the id "edit-group-type-flexible-group"
    And I press "Continue"
    When I click radio button "Community" with the id "edit-field-flexible-group-visibility-community"
    And I fill in "Title" with "Test flexible group"
    And I show hidden inputs
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
    # Needs to happen after waiting for Ajax finish call, the ajax finish resets the states.
    And I click radio button "Open to join" with the id "edit-field-group-allowed-join-method-direct"
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

    # I can see members from flexible groups with Community visibility as outsider.
    Given I am logged in as "GivenUserTwo"
    And I am on "all-members"
    And I click "GivenUserOne"
    And I click "Groups" in the "Tabs"
    And I should see "Test flexible group" in the "Main content"
    And I should not see the link "Add a group" in the "Main content"
    And I click "Test flexible group"
    And I click "Members"
    And I should see "GivenUserOne"

    # I can join flexible groups with Community visibility as outsider when Direct is selected.
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
    And I select post visibility "Community"
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
    And I click radio button "Community" with the id "edit-field-content-visibility-community"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I press "Create event"
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
    When I fill in "Title" with "Test group community topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Community" with the id "edit-field-content-visibility-community"
    And I click radio button "Discussion"
    And I press "Create topic"

    # Create a topic in the flexible group, visible to group members only.
    When I click "Test flexible group"
    And I click "Topics"
    And I should see the link "Create Topic" in the "Sidebar second"
    And I click "Create Topic"
    When I fill in "Title" with "Test group private topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Group members" with the id "edit-field-content-visibility-group"
    And I click radio button "Discussion"
    And I press "Create topic"

    # Check the topic is shown correctly to author after saving.
    And I should see "Test group private topic"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Test flexible group"

    # Check the topic visibilities as member of the group.
    # all-topics overview.
    Given I am at "all-topics"
    Then I should see "Test group community topic" in the "Main content"
    And I should see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should see "Test group community topic" in the "Main content"
    And I should see "Test group private topic" in the "Main content"

    # Check the topic visibilities as AN.
    Given I logout
    # all-topics overview.
    And I am at "all-topics"
    Then I should not see "Test group community topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should not see "Test group community topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Check the topic visibilities as LU not part of the group.
    Given I am logged in as "GivenUserThree"
    # all-topics overview.
    And I am at "all-topics"
    Then I should see "Test group community topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Search page.
    Given Search indexes are up to date
    And I am on "search/all"
    When I fill in "search_input" with "Test group"
    Then I should see "Test group community topic" in the "Main content"
    And I should not see "Test group private topic" in the "Main content"

    # Test flexible group with only public content visibility for LU.
    Given I am logged in as "GivenUserOne"
    And I am on "group/add"
    Then I click radio button "Flexible group By choosing this option you can customize many group settings to your needs." with the id "edit-group-type-flexible-group"
    And I press "Continue"
    When I click radio button "Public" with the id "edit-field-flexible-group-visibility-public"
    When I fill in "Title" with "Cheesy test of flexible group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Cheesy description text"
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
    # Check this as an user with LU permissions.
    Given I am logged in as an "authenticated user"
    When I am on "all-groups"
    And I click "Cheesy test of flexible group"
    Then I should see the link "Stream"
    And I should see the link "About"
    And I should see the link "Events"
    And I should see the link "Topics"
    And I should see the link "Members"

    # Test flexible group with members visibility similar to secret groups.
    Given I am logged in as "GivenUserOne"
    And I am on "group/add/flexible_group"
    When I click radio button "Group members only (secret)" with the id "edit-field-flexible-group-visibility-members"
    When I fill in "Title" with "Flexible group - Secret option"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Cheesy description text"
    And I fill in "Location name" with "Cheese St"
    And I select "US" from "Country"
    Then I wait for AJAX to finish
    And I press "Save"
    # Check this group as an anonymous user.
    And I logout
    Then I am on "all-groups"
    And I should not see "Flexible group - Secret option"

    # Check this as an user with LU permissions.
    Given I am logged in as an "authenticated user"
    When I am on "all-groups"
    And I should not see "Flexible group - Secret option"

    # Check this as an user with SM permissions.
    Given I am logged in as "SiteManagerOne"
    When I am on "all-groups"
    Then I should see "Flexible group - Secret option"
    When I click "Flexible group - Secret option"
    Then I should see the link "Stream"
    And I should see the link "About"
    And I should see the link "Members"
    And I should see the link "Events"
    And I should see the link "Topics"

    # Test flexible group with community visibility and members only / invite for closed group.
    Given I am logged in as "GivenUserOne"
    And I am on "group/add/flexible_group"
    When I click radio button "Community" with the id "edit-field-flexible-group-visibility-community"
    And I show hidden checkboxes
    When I fill in "Title" with "Flexible group - Closed option"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Cheesy description text"
    And I fill in "Location name" with "Cheese St"
    And I select "US" from "Country"
    Then I wait for AJAX to finish
    And I uncheck the box "field_group_allowed_visibility[community]"
    And I press "Save"

    # Check this group as an anonymous user.
    And I logout
    Then I am on "all-groups"
    And I should not see "Flexible group - Closed option"

    # Check this as an user with LU permissions.
    Given I am logged in as an "authenticated user"
    When I am on "all-groups"
    And I should see "Flexible group - Closed option"
    And I click "Flexible group - Closed option"
    Then I should not see the link "Stream"
    And I should see the link "About"
    And I should see the link "Members"
    And I should not see the link "Events"
    And I should not see the link "Topics"

    # And a GM receives a notification about the joined user.
    When I am logged in as "GivenUserOne"
    And I wait for the queue to be empty
    And I am at "notifications"
    Then I should see text matching "GivenUserTwo joined the Test flexible group"
