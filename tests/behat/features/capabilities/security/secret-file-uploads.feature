@api @javascript
Feature: Secret files
  Files should be hidden behind time limited unguessable URLs.

  Background:
    Given I set the configuration item "entity_access_by_field.settings" with key "default_visibility" to "community"
    And users:
      | name                  | mail                            | status | field_profile_first_name  | field_profile_last_name | field_profile_organization | field_profile_function | roles    |
      | private_file_user_1   | private_file_user_1@example.com | 1      | Private                   | Ryan                    | Privateering               | Private                | verified |
    And I am logged in as "private_file_user_1"

  Scenario: Topic with secret image and secret attachment
    Given I am on "node/add/topic"
    And I check the box "News"
    And I fill in the following:
      | Title | Private: topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Private: topic text"
    And I attach the file "/files/opensocial.jpg" to "Image"
    And I wait for AJAX to finish
    And I attach the file "/files/humans.txt" to "edit-field-files-0-upload"
    And I wait for AJAX to finish

    When I press "Create topic"

    Then I should see "Topic Private: topic has been created."
    And I should see "Private: topic" in the "Hero block"
    And I should have uploaded 0 private files
    And I should have uploaded 0 public files
    And I should have uploaded 2 secret files

  Scenario: Comment with secret attachment
    Given I enable the module "social_comment_upload"
    And topics with non-anonymous author:
      | title        | field_topic_type | body                  | field_content_visibility | langcode |
      | Test content | News             | Body description text | community                | en       |

    When I am viewing the topic "Test content"
    And I fill in the following:
      | Add a comment | This is a test comment |
    And I press "Add attachment"
    And I attach the file "/files/humans.txt" to "edit-field-comment-files-0-upload"
    And I wait for AJAX to finish
    And I press "Comment"
    And I should see the success message "Your comment has been posted."

    Then I should have uploaded 0 private files
    And I should have uploaded 0 public files
    And I should have uploaded 1 secret files

  Scenario: Secret profile picture
    # Now save profile picture.
    Given I am on "/user"
    And I click "Edit profile information"
    And I attach the file "/files/opensocial.jpg" to "Profile image"
    And I wait for AJAX to finish

    When I press "Save"

    Then I should have uploaded 0 private files
    And I should have uploaded 0 public files
    And I should have uploaded 1 secret files

  Scenario: Secret post picture
    # Now create a post.
    Given I am on the homepage

    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I fill in "Say something about these image(s)" with "Private: post photo"
    And I select post visibility "Community"
    And I press "Post"

    Then I should have uploaded 0 private files
    And I should have uploaded 0 public files
    And I should have uploaded 1 secret files

  Scenario: Upload files in the WYSIWYG
    Given I view the topic creation page
    And I check the box "News"
    And I fill in "Title" with "Private WYSIWYG: topic"
    And I click on the image icon in the WYSIWYG editor
    And I wait for AJAX to finish
    And I attach the file "/files/opensocial.jpg" to "files[fid]"
    And I wait for AJAX to finish
    And I fill in "Alternative text" with "Just a private image test"
    And I click the xth "0" element with the css ".editor-image-dialog .form-actions .ui-button"
    And I wait for AJAX to finish

    When I press "Create topic"

    Then I should see "Topic Private WYSIWYG: topic has been created."
    And I should see "Private WYSIWYG: topic" in the "Hero block"
    And I should have uploaded 0 private files
    And I should have uploaded 0 public files
    And I should have uploaded 1 secret files
