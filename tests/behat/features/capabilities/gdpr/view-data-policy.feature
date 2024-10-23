@api
Feature: Create data policy and add revisions using administration page
  Benefit: In order to create and add new revision to data policy
  Role: As a SM
  Goal/desire: I want to create and add new revision to data policy

  Background:
    # Create users and enable Social GDPR module.
    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
      | behatuser        | behatuser@example.com        | 1      | verified    |
    And I enable the module "social_gdpr"

  Scenario: Set Data Policy mandatory and test it in a verified user

    Given data_policies:
      | name        | field_description                 |
      | Data policy | Description for the  data policy  |
    And I am logged in as "behatsitemanager"
    And I am on "admin/config/people/data-policy/settings"
    And I should see "Consent text"

    When I fill in "Consent text" with "I read and consent to the [id:1*]"
    And I press "Save configuration"
    And I am logged in as "behatuser"

    Then I should be on "data-policy-agreement?destination=/stream"
    And I should see the text "Our data protection statement has been updated on"
    And I should see the text "Consent to the data protection statement(s) is required for continuing using this platform. If you do not consent, you will be guided to"
    And I should see the link "the account cancellation"
    And I should see the text "process."
    And I should see the text "I read and consent to the"
    And I should see the link "data policy"
    And I should see "Save"

  Scenario: Cancellation account and data policy

    Given data_policies:
      | name        | field_description                 |
      | Data policy | Description for the  data policy  |
    And I set the GDPR Consent Text to "I read and consent to the [id:1*]"

    When I am logged in as "behatuser"
    And I should be on "data-policy-agreement?destination=/stream"
    And I should see the link "the account cancellation"
    And I click "the account cancellation"

    Then I should see the text "Are you sure you want to cancel your account?"
    And I click "Cancel"
    And I click "data policy"
    And I wait for AJAX to finish
    And I should see "Data policy"
    And I should see the text "Description for the data policy"

  Scenario: Check data policy when register an user

    Given data_policies:
      | name        | field_description                 |
      | Data policy | Description for the  data policy  |
    And I logout

    When I am on "user/register"
    And I should see the text "I read and consent to the"
    And I should see the link "data policy"

    Then I click "data policy"
    And I wait for AJAX to finish
    And I should see "Data policy"
    And I should see the text "Description for the data policy"
