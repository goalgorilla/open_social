@api @notifications @stability @stability-3 @YANG-4199 @group-invite-members
Feature: Send invite group email notifications
  Benefit: Email notifications attract users to the platform
  Role: As a SM
  Goal/desire: I want to be able to invite group members

  @email-spool
  Scenario: Send group invite email for new user

    Given I set the configuration item "system.site" with key "name" to "Open Social"
    Given users:
      | name   | mail  | status | roles |
      | site_manager_1 | site_manager_1@example.com | 1      | sitemanager  |
      | existing_user_1 | existing_user_1@example.com | 1      |   |
    Given groups:
      | title             | description                    | author          | type           | language |
      | Test-invite-group | Something that wanted share..  | site_manager_1  | flexible_group | en       |

    # Lets first check if sending mail works properly
    Given I am logged in as an "administrator"
    And I go to "/admin/config/swiftmailer/test"
    And I should see "This page allows you to send a test e-mail to a recipient of your choice."
    When I fill in the following:
      | E-mail | site_manager_1@example.com |
    Then I press "Send"
    And I should have an email with subject "Swift Mailer has been successfully configured!" and in the content:
      | This e-mail has been sent from Open Social by the Swift Mailer module. |

    # Enable "Allow invited user to skip email verification" option for groups
    When I go to "/admin/config/opensocial/social-group"
    Then I click the element with css selector ".claro-details__summary"
    And I should see "Allow invited user to skip email verification"
    Then I check the box "email_verification"
    And I press "Save configuration"

    # Send invite to the new user.
    Given I am logged in as "site_manager_1"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I click "Test-invite-group"
    When I click "Manage members"
    Then I should see "Add members"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    Then I should see "Invite members to group: Test-invite-group"
    And I fill in select2 input ".form-type-select" with "new_test_user@example.com" and select "new_test_user@example.com"
    And I press "Send your invite(s) by email"
    Then I wait for the batch job to finish
    And I wait for the queue to be empty
    Then I should see "Invite sent to new_test_user@example.com"
    And I should have an email with subject "site_manager_1 has invited you to join a group on Open Social." and in the content:
      | Hi, I would like to invite you to join my group Test-invite-group on Open Social. Kind regards, site_manager_1  Accept invite	About Open Social |

    # Register as new user and accept invitation.
    Given I logout
    And I intend to create a user named "new_test_user"
    Then I open register page with prefilled "new_test_user@example.com" and destination to invited group "Test-invite-group"

    When I fill in the following:
      | Username | new_test_user |
      | Password | new_test_pass |
      | Confirm password | new_test_pass |
    And I press "Create new account"
    Then I should see "Registration successful. You are now logged in."
    And I should see "You have accepted the invitation"
    And I should see "Joined"

    # Send invite to existing user.
    Given I logout
    And I am logged in as "site_manager_1"
    When I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I click "Test-invite-group"
    When I click "Manage members"
    Then I should see "Add members"
    When I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    Then I should see "Invite members to group: Test-invite-group"
    And I fill in select2 input ".form-type-select" with "existing_user_1@example.com" and select "existing_user_1@example.com"
    And I press "Send your invite(s) by email"
    Then I wait for the batch job to finish
    And I wait for the queue to be empty
    Then I should see "Invite sent to existing_user_1"

    And I should have an email with subject "site_manager_1 has invited you to join a group on Open Social." and in the content:
      | Hi, I would like to invite you to join my group Test-invite-group on Open Social. Kind regards, site_manager_1  Accept invite	About Open Social |

    # Login and check if invite has been sent to existing user.
    Given I logout
    And I am logged in as "existing_user_1"
    Then I go to "/my-invites"
    And I should see "1 group invite"
    And I should see "Test-invite-group"
