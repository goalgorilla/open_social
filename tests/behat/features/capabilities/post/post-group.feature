@api @group @DS-677 @DS-642 @DS-4211 @stability @stability-3 @test
Feature: Create Post in Group
  Benefit: In order to share knowledge with people in group
  Role: As a LU
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post in group
    Given users:
      | name           | mail                     | status |
      | Group User One | group_user_1@example.com | 1      |
      | Group User Two | group_user_2@example.com | 1      |
    Given groups:
     | title           | description      | author         | type        | language |
     | Test open group | Description text | Group User One | open_group  | en       |

   Given I am logged in as "Group User One"
     And I am on "/all-groups"
    Then I should see "Test open group"

    When I click "Test open group"
     And I fill in "Say something to the group" with "This is a community post in a group."
     And I press "Post"
    Then I should see the success message "Your post has been posted."
     And I should see "This is a community post in a group."
     And I should see "Group User One" in the ".media-heading" element

    # Scenario: See post on profile stream
    When I am on "/user"
    Then I should see "This is a community post in a group."
