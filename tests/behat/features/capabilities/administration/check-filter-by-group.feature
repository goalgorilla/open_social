@administration @account @profile @stability @perfect @api @view-filtered-users
Feature: View filtered users as site manager
  Benefit: In order to see user list at
  Role: SM
  Goal/desire: See user list

  Scenario: Successfully see user list filtered by groups
    # Create 5 users and 4 groups
    Given users:
      | name           | mail                       | status |
      | Behat Owner    | behat_owner@example.com    | 1      |
      | Behat Member 1 | behat_member_1@example.com | 1      |
      | Behat Member 2 | behat_member_2@example.com | 1      |
      | Behat Member 3 | behat_member_3@example.com | 1      |
      | Behat Member 4 | behat_member_4@example.com | 1      |
    Given groups:
      | label         | field_group_description | author      | type           | langcode | field_flexible_group_visibility | field_group_allowed_visibility |
      | Behat Group 1 |                         | Behat Owner | flexible_group | en       | public                          | public                         |
      | Behat Group 2 |                         | Behat Owner | flexible_group | en       | public                          | public                         |
      | Behat Group 3 |                         | Behat Owner | flexible_group | en       | public                          | public                         |
      | Behat Group 4 |                         | Behat Owner | flexible_group | en       | public                          | public                         |

    # Adding users to groups
    Given I am logged in as "Behat Owner"

    # Add Behat member 1 to the Behat Group 1 group directly.
    When I am on "/all-groups"
      And I click "Behat Group 1"
      And I click "Manage members"
      And I click the group member dropdown
      And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 1" and select "Behat Member 1"
      And I wait for AJAX to finish
      And I press "Save"
      And I should see "1 new member joined the group."

    # Add Behat member 2 to the Behat Group 2 group directly.
    When I am on "/all-groups"
      And I click "Behat Group 2"
      And I click "Manage members"
      And I click the group member dropdown
      And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 2" and select "Behat Member 2"
      And I wait for AJAX to finish
      And I press "Save"
      And I should see "1 new member joined the group."

    # Add Behat member 2 to the Behat Group 3 group directly.
    When I am on "/all-groups"
      And I click "Behat Group 3"
      And I click "Manage members"
      And I click the group member dropdown
      And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 3" and select "Behat Member 3"
      And I wait for AJAX to finish
      And I press "Save"
      And I should see "1 new member joined the group."

    # Add Behat member 4 to the Behat Group 4 group directly.
    When I am on "/all-groups"
      And I click "Behat Group 4"
      And I click "Manage members"
      And I click the group member dropdown
      And I click "Add directly"
    Then I fill in select2 input ".form-type-select" with "Behat Member 4" and select "Behat Member 4"
      And I wait for AJAX to finish
      And I press "Save"
      And I should see "1 new member joined the group."

    # Checks if the filter by members in a group is working
    Given I am logged in as an "sitemanager"
      And I am on "admin/people"
      And I should see "Closed group"
      And I should see "Open group"
      And I should see "Public group"
      And I should see "Behat Group 1"
      And I should see "Behat Group 2"
      And I should see "Behat Group 3"
      And I should see "Behat Group 4"
      And I should see "Behat Member 1"
      And I should see "Behat Member 2"
      And I should see "Behat Member 3"
      And I should see "Behat Member 4"
    When I select "Behat Group 1" from "Group"
      And I additionally select "Behat Group 2" from "Group"
      And I press "Filter"
      And I wait for AJAX to finish
    Then I should see "Behat Member 1"
      And I should see "Behat Member 2"
      And I should not see "Behat Member 3"
      And I should not see "Behat Member 4"
