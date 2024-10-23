@api
Feature: Redirect user to their stream page from /my-profile path
  Benefit: To make it easier for users to access their stream page
  Role: As a Authenticated
  Goal/desire: I want to be redirected to "/user/*/stream" when I go to "/my-profile"

  Scenario: A user is redirected to their stream page
    Given I am logged in as a user with the "authenticated" role

    When I go to "/my-profile"

    Then the url should match "^\/user\/[^\/]+\/stream"
