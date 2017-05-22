@api @post @ @stability @perfect @critical @DS-1136 @database
Feature: Create Post with Photo
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Posts with photo's

  Scenario: Successfully create, edit and delete post
  Given users:
      | name             | status | pass             |
      | PostPhotoCreate1 |      1 | PostPhotoCreate1 |
    And I am logged in as "PostPhotoCreate1"
    And I am on the homepage
    And I should not see "PostPhotoCreate1" in the "Main content front"

   When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish

    When I fill in "Say something about this photo" with "This post with a photo."
    And I select post visibility "Public"
    And I press "Post"
   Then I should see the success message "Your post has been posted."
    And I should see "This post with a photo."
    And I should see "PostPhotoCreate1" in the "Main content front"
    And I should be on "/stream"

        # Scenario: edit the post
   When I click the xth "5" element with the css ".dropdown-toggle"
    And I click "Edit"
    And I fill in "Say something to the Community" with "This is a post with a photo edited."
    And I press "Post"
   Then I should see the success message "Your post has been saved."
