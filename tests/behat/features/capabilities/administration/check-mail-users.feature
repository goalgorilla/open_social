@administration @api @notifications @javascript @stability @bulk-mails @no-update
Feature: Send bulk email
  Benefit: Be able to notify community members
  Role: As a SM
  Goal/desire: I want to be able to notify one or more community members

  # @todo https://www.drupal.org/project/social/issues/3334769

  @email-spool
  Scenario: Send bulk email as SM to the first 3 users
    Given users:
      | name      | status | roles    |
      | MailUser1 |      1 | verified |
      | MailUser2 |      1 | verified |
      | MailUser3 |      1 | verified |
    And I am logged in as a user with the "sitemanager" role

    #Select the first 3 users displayed on the user list to be export
    When I am on "admin/people"
      And I check the box "edit-views-bulk-operations-bulk-form-0"
      And I check the box "edit-views-bulk-operations-bulk-form-1"
      And I check the box "edit-views-bulk-operations-bulk-form-2"

      #Execute the Send bulk email action to the 3 users selected
      And I select "Send email" from "Action"
      And I press the "Apply to selected items" button
      And I should see the text "Send an email to 3 members"
      And I fill in "Subject" with "This is the e-mail subject"
      And I fill in the "edit-message-value" WYSIWYG editor with "The body for the e-mail to send"
      And I press the "Send email" button
      And I should see the text "Are you sure you wish to perform"
      And I press the "Execute action" button
      And I wait for the batch job to finish

    #Check the success messages
    Then I should see the text "The email(s) will be send in the background. You will be notified upon completion."
      And I should see "Action processing results: Send email (3)."
      And I wait for the queue to be empty
      #Check the details of the email received
      And I am at "notifications"
      And I should see the text "Background process"
      And I should see the text "This is the e-mail subject"
      And I should see the text "has finished"
      And I should have an email with subject "This is the e-mail subject" and in the content:
        | The body for the e-mail to send |
