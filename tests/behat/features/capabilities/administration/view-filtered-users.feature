@account @profile @stability @stability-4 @perfect @api @3245778 @view-filtered-users
Feature: View filtered users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  Scenario: Successfully see user list filtered by groups
    Given users:
      | name         | mail                     | status |
      | Behat User 1 | behat_user_1@example.com | 1      |
      | Behat User 2 | behat_user_2@example.com | 1      |
      | Behat User 3 | behat_user_3@example.com | 1      |
    Given groups:
      | title         | description | author       | type         | language |
      | Behat Group 1 |             | Behat User 1 | closed_group | en       |
      | Behat Group 2 |             | Behat User 1 | open_group   | en       |
      | Behat Group 3 |             | Behat User 2 | open_group   | en       |
      | Behat Group 4 |             | Behat User 3 | public_group | en       |
    Given I am logged in as an "sitemanager"
    When I am on "admin/people"
    Then I should see "Closed group"
    And I should see "Open group"
    And I should see "Public group"
    And I should see "Behat Group 1"
    And I should see "Behat Group 2"
    And I should see "Behat Group 3"
    And I should see "Behat Group 4"
    And I should see "Behat User 1"
    And I should see "Behat User 2"
    And I should see "Behat User 3"
    When I select "Behat Group 2" from "Group"
    And I additionally select "Behat Group 3" from "Group"
    And I press "Filter"
    Then I should see "Behat User 1"
    And I should see "Behat User 2"
    And I should not see "Behat User 3"
