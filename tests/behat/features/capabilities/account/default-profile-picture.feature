@api
Feature: Users should have a default profile picture if they don't set one

  Scenario: A user without profile picture should show the default image
    Given I am logged in as a user with the authenticated role

    When I am viewing my profile

    Then the image "Default profile image" should be loaded
