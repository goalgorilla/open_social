@album @api @security @stability @stability-1 @PROD-21898-perm
Feature: Create Album
  Benefit: In order to show photos to the community
  Role: As a Verified
  Goal/desire: I want to create Albums

  Scenario: Successfully create album in group
    Given users:
      | name  | pass | mail              | status | roles       |
      | harry | 1234 | harry@example.com | 1      | verified    |
      | sally | 1234 | sally@example.com | 1      | verified    |
      | smith | 1234 | sm@example.com    | 1      | sitemanager |
    Given groups:
      | title      | description      | author | type           | language | field_content_visibility | field_group_allowed_join_method |
      | TFG public | Description text | harry  | flexible_group | en       | public                   | direct                          |
      | TFG GM     | Vroem vroem..    | sally  | flexible_group | en       | group members            | direct                          |
      | TFG C      | Kayaking in NY   | harry  | flexible_group | en       | community                | direct                          |
    Given album content:
      | title                       | description      | author | type           | language | field_content_visibility | field_group |
      | Test Album public public    | Description text | harry  | flexible_group | en       | public                   | TFG public  |
      | Test Album public GM        | Description text | sally  | flexible_group | en       | public                   | TFG GM      |
      | Test album public C         | Description text | smith  | flexible_group | en       | public                   | TFG C       |
      | Test Album GM public        | Description text | harry  | flexible_group | en       | group member             | TFG public  |
      | Test Album GM GM            | Description text | sally  | flexible_group | en       | group member             | TFG GM      |
      | Test album GM C             | Description text | smith  | flexible_group | en       | group member             | TFG C       |
      | Test Album community public | Description text | harry  | flexible_group | en       | community                | TFG public  |
      | Test Album community GM     | Description text | sally  | flexible_group | en       | community                | TFG GM      |
      | Test album community C      | Description text | smith  | flexible_group | en       | community                | TFG C       |
    Given I am an anonymous user
    Given I open the "album" node with title "Test Album public public"
    And I click "Motorboats"
    And I click "Join"
    And I press "Join group"
    And I click "Albums"
    And I click "Create new album"
    When I fill in the following:
      | Title                                   | This is a test album |
      | edit-field-content-visibility-community | Group members        |
    And I should see "Test flexible group"
    And I press "Create album"
    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
    And I should see "This is a test album"
    And I should see "Group members" in the "Post visibility"
    When I fill in the following:
      | edit-field-post-0-value | Test message post for album |
    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I press "Post"
    And I wait for AJAX to finish
    Then I should see "Motorboats"

  Scenario: Successfully create public album
    Given I am logged in as an "verified"
    And I am on "/node/add/album"
    When I fill in the following:
      | Title                                   | This is a test album |
      | edit-field-content-visibility-community | public               |
    And I press "Create album"
    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
    And I should see "This is a test album"
    And I should see "Public" in the "Post visibility"
    When I fill in the following:
      | edit-field-post-0-value | Test message post for album |
    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I press "Post"
    And I wait for AJAX to finish
    Then I should see "Your post has been posted."
