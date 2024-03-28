@api
Feature: Redirect user to their settings page
  Benefit: To make it easier for users to access their settings page
  Role: As a Authenticated
  Goal/desire: I want to be redirected to "/user/*/edit" when I go to "/my-settings"

  Scenario: A user is redirected to their settings page
    Given I am logged in as a user with the "authenticated" role

    When I go to "/my-settings"

    Then the URL should match "^\/user\/[^\/]+\/edit$"
