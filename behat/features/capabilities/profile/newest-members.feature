@api @profile @user @members @stability @perfect @community @newest @overview @block @LU @critical
Feature: See newest users in the community
  Benefit: In order to discover new people
  Role: LU
  Goal/desire: I want to see newest users block and overview

  Scenario: Do not show users that do not exists
#    TODO: Test visibility settings (Public, Community)
    Given I am logged in as an "authenticated user"
    Then I should not see "Behat User 1"
    And I should not see "Behat User 2"
    When I click the xth "4" link with the text "View all"
    Then I should not see "Behat User 1"
    And I should not see "Behat User 2"

  Scenario: Show newest users in the community
    Given users:
      | name         | pass | mail                   | status | created    |
      | Behat User 1 | test | behatuser1@example.com | 1      | 1893456000 |
      | Behat User 2 | test | behatuser2@example.com | 1      | 1893456000 |

    Given I am logged in as an "authenticated user"

    Then I should see "Newest members"
    And I should see "Behat User 1"
    And I should see "Behat User 2"

    When I click the xth "4" link with the text "View all"
    Then I should see "Behat User 1"
    And I should see "Behat User 2"
    And I should see "Newest members"

