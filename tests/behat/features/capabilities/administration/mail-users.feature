@api @notifications @stability @stability-3 @bulk-mails
Feature: Send bulk email
  Benefit: Be able to notify community members
  Role: As a SM
  Goal/desire: I want to be able to notify one or more community members

  @email-spool
  Scenario: Send bulk email as SM
    When I am logged in as a user with the "sitemanager" role
    And I am on "admin/people"
    When I check the box "edit-views-bulk-operations-bulk-form-0"
    And I check the box "edit-views-bulk-operations-bulk-form-1"
    And I check the box "edit-views-bulk-operations-bulk-form-2"
    And I select "Send email" from "Action"
    And I press the "Apply to selected items" button
    Then I should see the text "Send an email to 3 members"

    When I fill in the following:
      | Subject | This is the e-mail subject |
    And I fill in the "edit-message-value" WYSIWYG editor with "The body for the e-mail to send"
    And I press the "Send email" button
    Then I should see the text "Are you sure you wish to perform"
    And I press the "Execute action" button
    And I wait for the batch job to finish
    Then I should see the text "The email(s) will be send in the background. You will be notified upon completion."

    When I wait for the queue to be empty
    And I am at "notifications"
    Then I should see the text "Background process"
    And I should see the text "This is the e-mail subject"
    And I should see the text "has finished"
    And I should have an email with subject "This is the e-mail subject" and in the content:
      | The body for the e-mail to send |