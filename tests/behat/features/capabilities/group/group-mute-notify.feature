@api @notifications @group @DS-7598 @stability @stability-3 @group-mute-group-notifications
Feature: Mute/Unmute group notifications
  Benefit: In order to be able to get more fine grained control about notifications which I find relative to me
  Role: As a LU
  Goal/desire: Being able to get more fine grained control about notifications

  Background:
    Given users:
      | name     | pass | mail                 | status | roles       |
      | dude_1st | 1234 | dude_1st@example.com | 1      | verified    |
      | dude_2nd | 1234 | dude_1st@example.com | 1      | sitemanager |
    Given groups:
      | title                | description            | author       | type           | language |
      | Ressinel's group 1st | Good nickname, dude!!! | dude_1st     | open_group     | en       |
      | Ressinel's group 2nd | Good nickname, dude!!! | dude_1st     | closed_group   | en       |

  @group-mute-group-notifications-group-page
  Scenario: LU able to mute/umute group notifications
    Given I am logged in as "dude_1st"
      And I am on "/my-groups"
    Then I should see "Ressinel's group 1st" in the "Main content"
      And I click "Ressinel's group 1st"
    Then I should see the button "Joined"
      And I press "Joined"
      And I should see the link "Mute group"
    When I click "Mute group"
      And I wait for AJAX to finish
    Then I should see "Unmute group"
    When I click "Unmute group"
      And I wait for AJAX to finish
    Then I should see "Mute group"

  @group-mute-group-notifications-overview-page
  Scenario: LU able to view all Groups muted
    Given I am logged in as "dude_1st"
      And I am on "/my-groups"
    Then I should see "Ressinel's group 1st" in the "Main content"
      And I click "Ressinel's group 1st"
    Then I should see the button "Joined"
      And I press "Joined"
      And I should see the link "Mute group"
    When I click "Mute group"
      And I wait for AJAX to finish
    Then I should see "Unmute group"
    When I am on "/my-groups"
    Then I should see "Ressinel's group 2nd"
    When I select "My muted groups" from "Muted groups"
      And I press the "Apply" button
    Then I should not see "Ressinel's group 2nd"
      But I should see "Ressinel's group 1st"
    When I select "My unmuted groups" from "Muted groups"
      And I press the "Apply" button
    Then I should not see "Ressinel's group 1st"
      But I should see "Ressinel's group 2nd"
    When I press the "Reset" button
    Then I should see "Ressinel's group 1st"
      And I should see "Ressinel's group 2nd"

  @email-spool
  Scenario: LU able to receive notifications from the unmuted group
    # Lets first check if sending mail works properly
    Given I am logged in as an "administrator"
      And I go to "/admin/config/swiftmailer/test"
      And I should see "This page allows you to send a test e-mail to a recipient of your choice."
    When I fill in the following:
      | E-mail | site_manager_1@example.com |
    Then I press "Send"
      And I should have an email with subject "Swift Mailer has been successfully configured!" and in the content:
        | This e-mail has been sent from Open Social by the Swift Mailer module. |

    # Add content to the group by another user.
    Given I am logged in as "dude_2nd"
    When I am on "/all-groups"
      And I click "Ressinel's group 1st"
      And I click "Topics"
      And I click "Create Topic"
      And I fill in the following:
        | Title | Topic for unmute notify |
      And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
      And I check the box "News"
      And I press "Create topic"
    Then I should see "Topic for unmute notify has been created."
      And I wait for the queue to be empty

    # Log in and check if we have notifications.
    Given I am logged in as "dude_1st"
    # Ensure that group notifications are not muted.
    When I am on "/my-groups"
    Then I should see "Ressinel's group 1st"
      And I click "Ressinel's group 1st"
    Then I should see the button "Joined"
      And I press "Joined"
      And I should see the link "Mute group"
      And I should see "Mute group"
    # There should be notifications.
    When I am on "/notifications"
    Then I should see "dude_2nd created a topic Topic for unmute notify in the Ressinel's group 1st group"
      And I should have an email with subject "New content has been added to a group you are in" and in the content:
        | dude_2nd created a topic Topic for unmute notify in the Ressinel's group 1st group |

  @email-spool
  Scenario: LU not able to receive notifications from the muted group
    # Lets first check if sending mail works properly
    Given I am logged in as an "administrator"
    And I go to "/admin/config/swiftmailer/test"
    And I should see "This page allows you to send a test e-mail to a recipient of your choice."
    When I fill in the following:
      | E-mail | site_manager_1@example.com |
    Then I press "Send"
    And I should have an email with subject "Swift Mailer has been successfully configured!" and in the content:
      | This e-mail has been sent from Open Social by the Swift Mailer module. |

    # Login and mute group notifications.
    Given I am logged in as "dude_1st"
    When I am on "/my-groups"
    Then I should see "Ressinel's group 1st"
      And I click "Ressinel's group 1st"
    Then I should see the button "Joined"
      And I press "Joined"
      And I should see the link "Mute group"
    When I click "Mute group"
      And I wait for AJAX to finish
    Then I should see "Unmute group"

    # Add content to the group by another user.
    Given I am logged in as "dude_2nd"
    Then I am on "/all-groups"
      And I click "Ressinel's group 1st"
      And I click "Topics"
      And I click "Create Topic"
      And I fill in the following:
        | Title | Topic for mute notify |
      And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
      And I check the box "News"
      And I press "Create topic"
    Then I should see "Topic for mute notify has been created."
      And I wait for the queue to be empty

    # Log in and check if we exactly have no notifications.
    Given I am logged in as "dude_1st"
    # There shouldn't be any notifications.
    When I am on "/notifications"
    Then I should not see "dude_2nd created a topic Topic for mute notify in the Ressinel's group 1st group"
      And I should not have an email with subject "New content has been added to a group you are in" and in the content:
        | dude_2nd created a topic Topic for mute notify in the Ressinel's group 1st group |
