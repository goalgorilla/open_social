@api
Feature: Redirect user to their edit profile page from /edit-profile path
  Benefit: To make it easier for users to access their edit profile page
  Role: As a Authenticated
  Goal/desire: I want to be redirected to "/user/*/profile" when I go to "/edit-profile"

  Scenario: A user is redirected to their edit profile page
    Given I am logged in as a user with the "authenticated" role

    When I go to "/edit-profile"

    Then the url should match "^\/user\/[^\/]+\/profile"
