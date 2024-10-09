@api
Feature: Create and Delete a Post on Group
  Benefit: In order to share knowledge with people in group
  Role: As a Verified
  Goal/desire: I want to create Posts in a group

  Scenario: Successfully create and edit post in group
    Given users:
      | name           | mail                     | status | roles    |
      | Group User One | group_user_1@example.com | 1      | verified |
    And I am logged in as "Group User One"
    And I am on "group/add/flexible_group"

    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I click radio button "Community"
    And I fill in "Location name" with "GG HQ"
    And I select "NL" from "Country"
    And I wait for AJAX to finish
    And I fill in the following:
      | City           | Enschede          |
      | Street address | Oldenzaalsestraat |
      | Postal code    | 7514DR            |
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "GG HQ"
    And I should see "1 member"
    And I should see "Joined"
    And I should see "Test open group" in the "Hero block"

    And I click "Stream"
    And I fill in "Say something to the group" with "This is a community post in a group."
    And I press "Post"

    Then I should see the success message "Your post has been posted."
    And I should see "This is a community post in a group."
    And I should see "Group User One" in the ".media-heading" element
    And I click the post visibility dropdown
    And I should not see "Public"
    And I should not see "Closed"

    # Scenario: See post on profile stream
    And I am on "/user"
    And I should see "This is a community post in a group."

  Scenario: Successfully delete post in group
    Given users:
      | name           | mail                     | status | roles    |
      | Group User One | group_user_1@example.com | 1      | verified |
    And groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | direct                          |
    And group members:
      | group      | user            |
      | Test group | Group User One  |
    And I am logged in as "Group User One"

    When I am viewing the group "Test group"
    And I click "Stream"
    And I fill in "Say something to the group" with "This is a post in a group."
    And I press "Post"
    And I should see "This is a post in a group."
    And I should see "Group User One" in the ".media-heading" element
    And I am on the homepage
    And I should see "This is a post in a group."
    And I should see "Group User One" in the ".media-heading" element

    # Delete post
    Then I am viewing the group "Test group"
    And I click the element with css selector ".btn.btn-icon-toggle.dropdown-toggle.waves-effect.waves-circle"
    And I click "Delete"
    And I should see "Are you sure you want to delete the post"
    And I should see "This action cannot be undone."
    And I press "Delete"
    And I wait for AJAX to finish
    And I should see "has been deleted."
    And I should not see "This is a post in a group."
    And I am on the homepage
    And I should not see "This is a post in a group."

