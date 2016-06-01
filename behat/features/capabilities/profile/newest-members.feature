@api @profile @user @members @stability @perfect @community @newest @overview @block @LU @critical
Feature: See newest topics in the community
  Benefit: In order to discover content
  Role: LU
  Goal/desire: I want to see newest topics block and overview

  Scenario: Successfully show my upcoming events as a LU
#    TODO: Test visibility settings (Public, Community)

    Given I am logged in as an "authenticated user"
    Then I should not see "Behat User 1"
    And I should not see "Behat User 2"

    Given users:
      | name         | mail                   | status | created    |
      | Behat User 1 | behatuser1@example.com | 1      | 1893456000 |
      | Behat User 2 | behatuser2@example.com | 1      | 1893456000 |

    Given I am on the homepage

    Then I should see "Newest members"
    And I break
    And I should see "Behat User 1"
    And I should see "Behat User 2"

    When I click the xth "4" link with the text "View all"
    Then I should see "Behat User 1"
    And I should see "Behat User 2"
    And I should see "Newest members"

