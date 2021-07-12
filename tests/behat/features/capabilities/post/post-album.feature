@api @post @stability @perfect @critical @YANG-4860 @database @stability-3 @album @post-album
Feature: Create Post with Photo
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Posts with photo's

  Scenario: Successfully create, edit and delete post

    Given I enable the module "social_album"
    Given users:
      | name     | status | pass     |
      | PostUser |      1 | PostUser |
    And I am logged in as "PostUser"

    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    Then I should see the text "Add image(s) to an album"

    When I attach the file "/files/opensocial.jpg" to hidden field "files[field_post_image_1][]"
    And I wait for AJAX to finish
    And I click the xth "1" element with the css "button[name=field_post_image_0_remove_button]"
    Then I should see the text "Add image(s) to an album"
