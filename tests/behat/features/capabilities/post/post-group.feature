@api @group @DS-677 @DS-642 @stability
Feature: Create Post on Group
  Benefit: In order to share knowledge with people in group
  Role: As a LU
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post in group
    Given users:
      | name           | mail                     | status |
      | Group User One | group_user_1@example.com | 1      |
      | Group User Two | group_user_2@example.com | 1      |
    And I am logged in as "Group User One"
    And I am on "user"
    And I click "Groups"
    And I click "Add a group"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I fill in "Location name" with "GG HQ"
    And I select "NL" from "Country"
    And I wait for AJAX to finish
    Then I should see "City"
    And I fill in the following:
      | City | Enschede |
      | Street address | Oldenzaalsestraat |
      | Postal code | 7514DR |
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "GG HQ"
    And I should see "1 member"
    And I should see "Joined"
    And I should see the link "Read more"

    And I click "Test open group"
    And I should see "Test open group" in the "Hero block"
    And I fill in "Say something to the group" with "This is a community post in a group."
    And I press "Post"
    Then I should see the success message "Your post has been posted."
    And I should see "This is a community post in a group."
    And I should see "Group User One" in the ".media-heading" element

          # Scenario: See post on profile stream
    When I am on "/user"
    And I should see "This is a community post in a group."
