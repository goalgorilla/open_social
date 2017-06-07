@api @security @stability @perfect @critical @DS-3605
Feature: Private files
  Benefit: Upload files to private file directory
  Role: As a LU
  Goal/desire: Make sure uploaded files can not be accessed by unauthorised users

  Scenario: Create the files
    Given I enable the module "social_file_private"
    And I enable the module "social_comment_upload"
    And users:
      | name                  | mail                            | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function |
      | private_file_user_1   | private_file_user_1@example.com | 1      | Private                   | Ryan                    | Privateering               | Private              |
    And I am logged in as "private_file_user_1"

    # Create a topic with one attachment.
    Given I am on "node/add/topic"
    And I click radio button "Discussion"
    When I fill in the following:
      | Title | Private: topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Private: topic text"
    And I attach the file "/files/opensocial.jpg" to "Image"
    And I wait for AJAX to finish
    And I attach the file "/files/humans.txt" to "Add a new file"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Topic Private: topic has been created."
    And I should see "Private: topic" in the "Hero block"

    # Upload a attachment to a comment.
    When I fill in the following:
      | Add a comment | This is a test comment |
    And I press "Add attachment"
    And I attach the file "/files/humans.txt" to "edit-field-comment-files-0-upload"
    And I wait for AJAX to finish
    And I press "Comment"
    And I should see the success message "Your comment has been posted."

    # Now save profile picture.
    Given I am on "/user"
    And I click "Edit profile information"
    And I attach the file "/files/opensocial.jpg" to "Profile image"
    And I wait for AJAX to finish
    And I press "Save"

    # Now create a post.
    Given I am on the homepage
    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I fill in "Say something about this photo" with "Private: post photo"
    And I select post visibility "Community"
    And I press "Post"
    Then I should see the success message "Your post has been posted."

    # Check the files
    And User "private_file_user_1" should have uploaded "5" private files and "0" public files
    Then I open and check the access of the files uploaded by "private_file_user_1" and I expect access "allowed"
    Given I logout
    And I open the "topic" node with title "Private: topic"
    Then I should see "Access denied. You must log in to view this page."
    Then I open and check the access of the files uploaded by "private_file_user_1" and I expect access "denied"

  Scenario: Upload files in the WYSIWYG
    Given I enable the module "social_file_private"
    And users:
      | name                     | mail                               | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function |
      | wysiwyg_private_user_1   | wysiwyg_private_user_1@example.com | 1      | Real Slim                 | Shady                    | Privateering               | Private              |
    And I am logged in as "wysiwyg_private_user_1"

    Given I am on "node/add/topic"
    And I click radio button "Discussion"
    When I fill in the following:
      | Title | Private WYSIWYG: topic |
    And I click on the image icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I attach the file "/files/opensocial.jpg" to "files[fid]"
    And I wait for AJAX to finish
    And I fill in "Alternative text" with "Just a private image test"
    And I press "Save" in the "Modal footer"
    And I wait for AJAX to finish
    And I wait for "3" seconds
    And I press "Save"
    Then I should see "Topic Private WYSIWYG: topic has been created."
    And I should see "Private WYSIWYG: topic" in the "Hero block"
    And The image path in the body description should be private
    Then I open and check the access of the files uploaded by "wysiwyg_private_user_1" and I expect access "allowed"
    When I logout
    And I open the "topic" node with title "Private WYSIWYG: topic"
    Then I should see "Access denied. You must log in to view this page."
    Then I open and check the access of the files uploaded by "wysiwyg_private_user_1" and I expect access "denied"
