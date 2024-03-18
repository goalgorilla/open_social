@api @group
Feature: Limit access to group creation
  I want to limit who can create group to ensure a healthy community.

  Scenario: LU should not be able to create groups when it requires being verified
    Given I disable that the registered users to be verified immediately
    And I am logged in as a user with the authenticated role

    When I am on "group/add"
    Then I should see "Access denied"

  Scenario: Verified users should be able to create groups
    Given I disable that the registered users to be verified immediately
    And I am logged in as a user with the verified role

    When I am on "group/add"
    Then I should not see "Access denied"
