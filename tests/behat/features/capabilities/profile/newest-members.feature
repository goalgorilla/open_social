@api @profile @user @members @stability @perfect @community @newest @overview @block @verified @critical @stability-4 @newest-members
Feature: See newest users in the community
  Benefit: In order to discover new people
  Role: As a Verified
  Goal/desire: I want to see newest users block and overview

  Scenario: Do not show users that do not exists
#    TODO: Test visibility settings (Public, Community)
    Given I am logged in as an "verified"
    Then I should not see "Behat User 1"
    And I should not see "Behat User 2"
    When I am on "all-members"
    Then I should not see "Behat User 1"
    And I should not see "Behat User 2"

  Scenario: Show newest users in the community
    Given users:
      | name         | pass | mail                   | status | created    | roles    |
      | Behat User 1 | test | behatuser1@example.com | 1      | 1893456000 | verified |
      | Behat User 2 | test | behatuser2@example.com | 1      | 1893456000 | verified |

    Given I am logged in as an "verified"

    Then I should see "All members"
    And I should see "Behat User 1"
    And I should see "Behat User 2"

    When I am on "all-members"
    Then I should see "Behat User 1"
    And I should see "Behat User 2"
    And I should see "All members"
