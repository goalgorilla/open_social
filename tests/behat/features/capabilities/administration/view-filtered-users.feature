@account @profile @stability @stability-4 @perfect @api @3245778 @view-filtered-users
Feature: View filtered users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  Scenario: Successfully see user list filtered by groups
    Given users:
      | name           | mail                       | status |
      | Behat Owner    | behat_owner@example.com    | 1      |
      | Behat Member 1 | behat_member_1@example.com | 1      |
      | Behat Member 2 | behat_member_2@example.com | 1      |
      | Behat Member 3 | behat_member_3@example.com | 1      |
    Given groups:
      | title         | description | author      | type         | language |
      | Behat Group 1 |             | Behat Owner | closed_group | en       |
      | Behat Group 2 |             | Behat Owner | open_group   | en       |
      | Behat Group 3 |             | Behat Owner | open_group   | en       |
      | Behat Group 4 |             | Behat Owner | public_group | en       |

    Given I am logged in as "Behat Owner"

    # Add a member to the Behat Group 1 group directly.
    When I am on "/all-groups"
    And I click "Behat Group 1"
    And I click "Manage members"
    And I click the group member dropdown
    And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 1" and select "Behat Member 1"
    And I wait for "3" seconds
    And I press "Save"

    # Add a member to the Behat Group 2 group directly.
    When I am on "/all-groups"
    And I click "Behat Group 2"
    And I click "Manage members"
    And I click the group member dropdown
    And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 2" and select "Behat Member 2"
    And I wait for "3" seconds
    And I press "Save"

    # Add a member to the Behat Group 3 group directly.
    When I am on "/all-groups"
    And I click "Behat Group 3"
    And I click "Manage members"
    And I click the group member dropdown
    And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 3" and select "Behat Member 3"
    And I wait for "3" seconds
    And I press "Save"

    # Add a member to the Behat Group 4 group directly.
    When I am on "/all-groups"
    And I click "Behat Group 4"
    And I click "Manage members"
    And I click the group member dropdown
    And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 4" and select "Behat Member 4"
    And I wait for "3" seconds
    And I press "Save"

    Given I am logged in as an "sitemanager"
    When I am on "admin/people"
    Then I should see "Closed group"
    And I should see "Open group"
    And I should see "Public group"
    And I should see "Behat Group 1"
    And I should see "Behat Group 2"
    And I should see "Behat Group 3"
    And I should see "Behat Group 4"
    And I should see "Behat Member 1"
    And I should see "Behat Member 2"
    And I should see "Behat Member 3"
    When I select "Behat Group 1" from "Group"
    And I additionally select "Behat Group 2" from "Group"
    And I press "Filter"
    And I wait for "3" seconds
    Then I should see "Behat Member 1"
    And I should see "Behat Member 2"
    And I should not see "Behat Member 3"
    And I should not see "Behat Member 4"
