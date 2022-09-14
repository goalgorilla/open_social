@album @api @security @stability @stability-1 @PROD-21898
Feature: Create Album
  Benefit: In order to show photos to the community
  Role: As a Verified
  Goal/desire: I want to create Albums

#  Scenario: Successfully create album
#    Given I am logged in as an "verified"
#    And I am on "user"
#    And I click "Albums"
#    And I click "Create new album"
#    When I fill in the following:
#      | Title                                       | This is a test album |
#      | edit-field-content-visibility-community     | Community            |
#    And I press "Create album"
#    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
#    And I should see "This is a test album"
#    And I should see "Community"

#  Scenario: Successfully create album in group
#    Given users:
#      | name  | pass | mail              | status | roles       |
#      | harry | 1234 | harry@example.com | 1      | verified    |
#      | sally | 1234 | sally@example.com | 1      | verified    |
#      | smith | 1234 | sm@example.com    | 1      | sitemanager |
#    Given groups:
#      | title                 | description      | author | type           | language | field_content_visibility | field_group_allowed_join_method  |
#      | Test Flexible Group   | Description text | harry  | flexible_group | en       | public                   | direct                           |
#      | Motorboats            | Vroem vroem..    | sally  | flexible_group | en       | group members            | direct                           |
#      | Kayaking              | Kayaking in NY   | harry  | open_group     | en       |                          |                                  |
#      | Closed one            | Kayaking in NY   | harry  | closed_group   | en       |                          |                                  |
#    When I am logged in as "harry"
#    And I am on "/all-groups"
#    And I click "Motorboats"
#    And I click "Join"
#    And I press "Join group"
#    And I click "Albums"
#    And I click "Create new album"
#    When I fill in the following:
#      | Title                                       | This is a test album |
#      | edit-field-content-visibility-community     | Group members        |
#    And I should see "Test flexible group"
#    And I press "Create album"
#    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
#    And I should see "This is a test album"
#    And I should see "Group members" in the "Post visibility"
#    When I fill in the following:
#      | edit-field-post-0-value     | Test message post for album |
#    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
#    And I wait for AJAX to finish
#    And I press "Post"
#    And I wait for AJAX to finish
#    Then I should see "Motorboats"

  Scenario: Successfully create public album
    Given I am logged in as an "verified"
    And I am on "/node/add/album"
    When I fill in the following:
      | Title                                       | This is a test album |
      | edit-field-content-visibility-community     | public               |
    And I press "Create album"
    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
    And I should see "This is a test album"
    And I should see "Public" in the "Post visibility"
    When I fill in the following:
      | edit-field-post-0-value     | Test message post for album |
    When I attach the file "/files/opensocial.jpg" to hidden field "edit-field-post-image-0-upload"
    And I wait for AJAX to finish
    And I press "Post"
    And I wait for AJAX to finish
    Then I should see "Your post has been posted."
