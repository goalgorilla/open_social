@wip @api @post @stability @perfect @critical @DS-677 @DS-642
Feature: Create Post on Group
  Benefit: In order to share knowledge with people in group
  Role: As a LU
  Goal/desire: I want to create Posts

  Scenario: Successfully create, edit and delete post in group
    Given users:
      | name      | status | pass      |
      | PostUser1 |      1 | PostUser1 |
    Given groups:
      | title      | type       | author    | description           | language |
      | Open group | open_group | PostUser1 | This is an open group | en       |
    Given I am logged in as "PostUser1"
      And I am on the stream of group "Open group"
      And I fill in "Post" with "This is a community post in a group."
      And I press "Save"
     Then I should see the success message "Created the Post."
      And I should see "This is a community post in a group."
      And I should see "PostUser1" in the ".media-heading" element

          # Scenario: See post on profile stream
     When I am on "/user"
      And I should not see "This is a community post in a group."
