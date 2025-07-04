@api @javascript @no-update
Feature: Send invite group email notifications
  Benefit: Email notifications attract users to the platform
  Role: As a SM
  Goal/desire: I want to be able to invite group members

  @email-spool
  Scenario: Send group invite email for new user

    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And I enable the module "social_group_flexible_group"
    # Disable users automatic verification.
    And I set the configuration item "social_user.settings" with key "verified_immediately" to 0

    And users:
      | name           | mail                       | status | roles       |
      | site_manager_1 | site_manager_1@example.com | 1      | sitemanager |
      | verified       | verified@example.com       | 1      | verified    |
      | authenticated  | authenticated@example.com  | 1      |             |
    And groups:
      | label             | field_group_description        | author          | type           | langcode | field_flexible_group_visibility |
      | Test-invite-group | Something that wanted share..  | site_manager_1  | flexible_group | en       | public                          |

    # Enable "Allow invited user to skip email verification" option for groups
    When I am logged in as an "administrator"
    And I go to "/admin/config/opensocial/social-group"
    And I click the element with css selector "#edit-group-invite"
    And I should see "Allow invited user to skip email verification"
    And I check the box "email_verification"
    And I press "Save configuration"

    # Make sure authenticated users can't be invited to the group.
    And I am logged in as "site_manager_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I should see "Test-invite-group"
    And I click "Test-invite-group"
    And I click "Manage members"
    And I should see "Add members"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    And I should see "Invite members to Test-invite-group"
    And I fill in select2 input ".form-type-select" with "authenticated@example.com" and select "authenticated@example.com"
    And I press "Send your invite(s) by email"
    And I should see the error message "There is already a user with the email authenticated@example.com on the platform. This user is not yet verified and can not be invited."

    # Send invite to the new user.
    And I fill in select2 input ".form-type-select" with "new_test_user@example.com" and select "new_test_user@example.com"
    And I press "Send your invite(s) by email"
    And I wait for the batch job to finish
    And I should see "Invite sent to new_test_user@example.com"
    And I wait for the queue to be empty

    And I should have an email with subject "site_manager_1 has invited you to join a group on Open Social." and in the content:
      | Hi, I would like to invite you to join my group Test-invite-group on Open Social. Kind regards, site_manager_1  Accept invite	About Open Social |

    # Register as new user and accept invitation.
    And I logout
    # Enable back users automatic verification.
    And I set the configuration item "social_user.settings" with key "verified_immediately" to 1

    And I intend to create a user named "new_test_user"
    And I open register page with prefilled "new_test_user@example.com" and destination to invited group "Test-invite-group"

    And I fill in the following:
      | Username         | new_test_user |
      | Password         | new_test_pass |
      | Confirm password | new_test_pass |
    And I press "Create new account"
    And I should see "Registration successful. You are now logged in."
    And I should see "You have accepted the invitation"
    And I should see "Joined"

    # Send invite to existing verified user.
    And I logout
    And I am logged in as "site_manager_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My groups"
    And I should see "Test-invite-group"
    And I click "Test-invite-group"
    And I click "Manage members"
    And I should see "Add members"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    And I should see "Invite members to Test-invite-group"
    And I fill in select2 input ".form-type-select" with "verified@example.com" and select "verified@example.com"
    And I press "Send your invite(s) by email"
    And I wait for the batch job to finish
    And I should see "Invite sent to verified"

    And I wait for the queue to be empty
    And I should have an email with subject "site_manager_1 has invited you to join a group on Open Social." and in the content:
      | Hi, I would like to invite you to join my group Test-invite-group on Open Social. Kind regards, site_manager_1  Accept invite	About Open Social |

    # Login and check if invite has been sent to existing user.
    And I logout
    And I am logged in as "verified"
    And I go to "/my-invites"
    And I should see "1 invite"
    And I should see "Test-invite-group"

#    # Make sure the invite is not shown as part of the "group membership count" when on SKY theme in the profile block.
#    Given I set the configuration item "socialblue.settings" with key "style" to "sky"
#    And I am on "/my-groups"
#    Then I should see "0" in the ".card__counter-quantity" element
#    And I set the configuration item "socialblue.settings" with key "style" to "default"
