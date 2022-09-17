@api @post @stability @perfect @critical @YANG-4860 @database @stability-3 @album @post-album
Feature: Create Post with Photo
  Benefit: In order to share knowledge with people
  Role: As a Verified
  Goal/desire: I want to create Posts with photo's

  Scenario: Successfully create, edit and delete post

    Given I enable the optional module "social_album"
    And users:
      | name     | status | pass     | roles    |
      | PostUser |      1 | PostUser | verified |
    And I am logged in as "PostUser"

    When I add image "/files/opensocial.jpg" to the post form
    And I add image "/files/opensocial.jpg" to the post form
    And I click the xth "1" element with the css "button[name=field_post_image_0_remove_button]"

    Then I should see the text "Add image(s) to an album"
