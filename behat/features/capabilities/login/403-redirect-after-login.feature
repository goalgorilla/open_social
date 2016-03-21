@login @security @stability @api
Feature: Redirect AN user from 403 after login
  Benefit: In order to get fast to the content I want to see
  Role: An
  Goal/desire: I want to be redirected to the page I wanted to see after login in

  Scenario: Successfully redirected to the login page
    Given I am an anonymous user
    And I am on the homepage
    When I go to "user/2"
    Then I should be on "user/login?destination=/user/2"
     And I should see the error message "Access denied. You must log in to view this page."

  Scenario: Successfully redirected after login via 403 page
    Given users:
      | name       | status | pass |
      | r4032login |      1 | r4032login |
      And I am an anonymous user
      And I go to "user/2"
      And I should be on "user/login?destination=/user/2"
     When I fill in the following:
        | Username or email address | r4032login |
        | Password | r4032login |
     And I press "Log in"
    Then I should be on "user/2"
