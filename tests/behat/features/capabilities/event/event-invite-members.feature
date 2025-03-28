@api @javascript
Feature: Send invite event email notifications
  Benefit: Email notifications attract users to the platform
  Role: As a SM
  Goal/desire: I want to be able to invite event members

  @email-spool
  Scenario: Send event invite email for new user

    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And users:
      | name            | mail                        | status | roles       |
      | site_manager_1  | site_manager_1@example.com  | 1      | sitemanager |
      | existing_user_1 | existing_user_1@example.com | 1      | verified    |
    And event content:
      | title         | field_event_date | status | field_content_visibility | field_event_an_enroll | author         |
      | Invite Event  | +2 days          | 1      | public                   | 1                     | site_manager_1 |

    # Enable "Allow invited user to skip email verification" option
    And I am logged in as an "administrator"
    And I go to "/admin/config/opensocial/event-invite"
    And I should see "Allow invited user to skip email verification"
    And I check the box "email_verification"
    And I press "Save configuration"

    # Send invite to the new user.
    And I am logged in as "site_manager_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My events"
    And I click "Invite Event"
    And I click "Manage enrollments"
    And I should see "Add enrollees"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    And I should see "Find people by name or email address"
    And I fill in select2 input ".form-type-select" with "new_test_user@example.com" and select "new_test_user@example.com"
    And I press "Send your invite(s) by email"
    And I wait for the batch job to finish
    And I wait for the queue to be empty
    And I should see "Invite(s) have been successfully sent."
    And I should have an email with subject "site_manager_1 has invited you to the event Invite Event on Open Social" and in the content:
      | Hi, I would like to invite you to my event Invite Event on Open Social. Kind regards, site_manager_1 See event About Open Social |

    # Register as new user and accept invitation.
    And I logout
    And I intend to create a user named "new_test_user"
    And I open register page with prefilled "new_test_user@example.com" and destination to invited node "Invite Event"

    And I fill in the following:
      | Username         | new_test_user |
      | Password         | new_test_pass |
      | Confirm password | new_test_pass |
    And I press "Create new account"
    And I should see "Registration successful. You are now logged in."
    And I should see "Enroll"

    # Send invite to existing user.
    And I logout
    And I am logged in as "site_manager_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My events"
    And I click "Invite Event"
    And I click "Manage enrollments"
    And I should see "Add enrollees"
    And I click the xth "1" element with the css ".btn.dropdown-toggle"
    And I click "Invite users"
    And I should see "Find people by name or email address"
    And I fill in select2 input ".form-type-select" with "existing_user_1@example.com" and select "existing_user_1@example.com"
    And I press "Send your invite(s) by email"
    And I wait for the batch job to finish
    And I wait for the queue to be empty
    And I should see "Invite(s) have been successfully sent. "

    And I should have an email with subject "site_manager_1 has invited you to the event Invite Event on Open Social" and in the content:
      | Hi, I would like to invite you to my event Invite Event on Open Social. Kind regards, site_manager_1 |

    # Login and check if invite has been sent to existing user.
    And I logout
    And I am logged in as "existing_user_1"
    And I go to "/my-invites"
    And I should see "1 Event invites"
    And I should see "Invite Event"
