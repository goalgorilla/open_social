@api
Feature: Group manager should to be able to disabled posts in groups for members
  Benefit: Members are not allowed to create posts in group
  Role: As a GM
  Goal/desire: I want to disable posting in groups

  Scenario: Create a flexible group with enabled/disabled posting
    # @todo This test relies on the old layout.
    Given the theme is set to old
    And users:
      | name          | mail             | status | roles    |
      | Group Manager | gm_1@example.com | 1      | verified |
      | Group Member  | gm_2@example.com | 1      | verified |
    And I am logged in as "Group Manager"
    And I am on "group/add/flexible_group"
    And I should see checked the box "Enable posts for members"
    And I fill in "Title" with "Test flexible group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I click radio button "Community" with the id "edit-field-flexible-group-visibility-community"
    And I show hidden inputs
    And I click radio button "Open to join" with the id "edit-field-group-allowed-join-method-direct"
    And I press "Save"

    When I click "Stream" in the "Tabs"
    And I fill in "Say something to the group" with "This is a post in Flexible Group."
    And I press "Post"
    And I should see the success message "Your post has been posted."
    And I should see "This is a post in Flexible Group."
    And I should see "Group Manager posted in Test flexible group" in the "Main content front"

    #Create the post as a group member.
    Then I am logged in as "Group Member"
    And I am on "all-groups"
    And I should see "Test flexible group"
    And I click "Test flexible group"
    And I should see the link "Join" in the "Hero block"
    And I should not see "Say something to the group"
    And I click "Join"
    And I should see "Join group Test flexible group"
    And I press "Join"
    And I should see "Say something to the group"
    And I should see "Group Manager posted in Test flexible group" in the "Main content front"
    And I fill in "Say something to the group" with "Member posted in Flexible Group."
    And I press "Post"
    And I should see "Group Member posted in Test flexible group" in the "Main content front"
    And I should see "Member posted in Flexible Group." in the "Main content front"
    # Disable posting in flegible group.
    And I am logged in as "Group Manager"
    And I am on "all-groups"
    And I click "Test flexible group"
    And I click "Edit group"
    And I uncheck the box "Enable posts for members"
    And I press "Save"
    And I click "Stream" in the "Tabs"
    And I should see "Say something to the group"
    And I am logged in as "Group Member"
    And I am on "all-groups"
    And I click "Test flexible group"
    And I should not see "Say something to the group"
    And I should see "Member posted in Flexible Group." in the "Main content front"
    And I should see "This is a post in Flexible Group." in the "Main content front"
