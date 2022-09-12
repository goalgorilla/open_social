@album @api @security @stability @stability-1 @PROD-21898
Feature: Create Album
  Benefit: In order to show photos to the community
  Role: As a Verified
  Goal/desire: I want to create Albums

  Scenario: Successfully create album
    Given I am logged in as an "verified"
    And I am on "user"
    And I click "Albums"
    And I click "Create new album"
    When I fill in the following:
      | Title                                       | This is a test album |
      | edit-field-content-visibility-community     | Community            |
    And I press "Create album"
    Then I should see "Album This is a test album is successfully created. Now you can add images to this album."
    And I should see "This is a test album"
    And I should see "Community"
