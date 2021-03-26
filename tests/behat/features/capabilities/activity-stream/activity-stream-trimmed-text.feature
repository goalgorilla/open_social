@api @stability @activity_stream @comment @stability-2 @activity-stream-trimmed-text
Feature: See trimmed title of group in the activity stream
  Benefit: Participate in discussions on the platform
  Role: As a LU
  Goal/desire: I do not want to see too long group names in the activity stream

  @group
  Scenario: See trimmed group title in activity
    Given users:
      | name        | status | pass        |
      | CreateUser  | 1      | CreateUser  |
      | SeeUser     | 1      | SeeUser     |
    And I am logged in as "CreateUser"

    And I am on "group/add"
    And I press "Continue"
    When I fill in "Title" with "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat" in the "Hero block"

    When I click "Stream"
    And I fill in "Say something to the group" with "This is a community post in a group."
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a community post in a group."
    And I should see "CreateUser" in the ".media-heading" element
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor i ..." in the ".media-heading" element
    When I am on "/user"
    Then I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor i ..." in the ".media-heading" element
