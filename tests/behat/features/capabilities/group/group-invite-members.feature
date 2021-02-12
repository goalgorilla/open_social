@api @notifications @stability @YANG-4199 @group-invite-members
Feature: Send invite group email notifications
  Benefit: Email notifications attract users to the platform
  Role: As a SM
  Goal/desire: I want to be able to invite group members

  @email-spool
  Scenario: Send group invite email for new user

    Given I set the configuration item "system.site" with key "name" to "Open Social"
    Given users:
      | name   | mail  | status | roles |
      | site_manager_1 | site_manager_1@example.localhost | 1      | sitemanager  |
    Given groups:
      | title      | description    | author | type       | language |
      | Test invite group | Something that wanted share..  | site_manager_1  | flexible_group | en       |

    Given I logout
    And I am logged in as "site_manager_1"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I click "Test invite group"
    When I click "Manage members"
    Then I should see "Add members"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    Then I should see "Invite members to group: Test invite group"
    And I fill in select2 input ".form-type-select" with "new_test_user@example.com" and select "new_test_user@example.com"
    And I press "Send your invite(s) by email"
    And I wait for the queue to be empty
    Then I should see "Invite sent to new_test_user@example.com"
    Then I should have an email with subject "site_manager_1 has invited you to join a group on Open Social." and in the content:
      | I would like to invite you to join my group Test invite group on Open Social |
