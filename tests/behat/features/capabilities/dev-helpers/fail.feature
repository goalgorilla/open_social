@dev-helpers @fail
Feature: Failing test

  Scenario: Drupal not available
    When I am on the homepage
    Then I should not see "Page not found"