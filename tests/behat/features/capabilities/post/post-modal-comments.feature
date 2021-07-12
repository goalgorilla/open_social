@api @post @stability @perfect @critical @YANG-4830 @database @stability-2 @post-modal-comments
Feature: Comment on a Post with an Image
  Benefit: In order to give my opinion on a post with an image
  Role: As a LU
  Goal/desire: I want to comment on a post with an image

  Scenario: Successfully create a comment on a post with an image in the modal window

    Given I enable the module "social_ajax_comments"
    Given users:
      | name     | status | pass     |
      | PostUser |      1 | PostUser |
    And I am logged in as "PostUser"

    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I fill in "Say something about these image(s)" with "This post with a photo."
    And I press "Post"
    And I click the xth "0" element with the css ".post-with-image a" in the "Main content"
    And I wait for AJAX to finish
    And I wait for "3" seconds
    And I fill in "Comment #1" for "Post comment" in the "Modal"
    And I press "Comment" in the "Modal"
    And I wait for AJAX to finish
    And I wait for "1" seconds
    Then I should see the success message "Your comment has been posted." in the "Modal"
    And I should see the text "Comment #1" in the "Modal"

    When I fill in "Comment #2" for "Post comment" in the "Modal"
    And I press "Comment" in the "Modal"
    And I wait for AJAX to finish
    Then I should see the success message "Your comment has been posted." in the "Modal"
    And I should see the text "Comment #2" in the "Modal"
