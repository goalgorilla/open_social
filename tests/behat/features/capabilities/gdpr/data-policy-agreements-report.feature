@api
Feature: Data policy agreements report
  Benefit: So I can access and view all agreements from data policy
  Role: As a SM
  Goal/desire: I want to know which users did (not) gave consent to the data policy

  Background:

    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
      | behatuser       | behatuser@example.com       | 1      | verified    |
    And I enable the module "social_gdpr"
    And data_policies:
      | name               | field_description       |
      | Terms & Conditions | No rights in this test  |
    And I set the GDPR Consent Text to "I read and consent to the [id:1]"

  Scenario: Check data policy agreements report without records
    Given I am logged in as "behatsitemanager"

    When I am on "admin/reports/data-policy-agreements"

    Then I should see the text "User consents not found."

  Scenario: Check data policy agreements report with Undecided record

    Given I am logged in as "behatuser"
    And I should be on the homepage
    And I should see the success message "We published a new version of the data protection statement. You can review the data protection statement here."
    And I click "here"
    And I should be on "data-policy-agreement"

    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"

    Then I should not see the text "User consents not found."
    And I should see "Undecided" in the "td.views-field-state" element

  Scenario: Check data policy agreements report with Not Agree record

    Given I am logged in as "behatuser"
    And I should see the success message "We published a new version of the data protection statement. You can review the data protection statement here."
    And I am on "data-policy-agreement"
    And I press "Save"
    And I should be on the homepage

    When I am logged in as "behatsitemanager" with the "without consent" permission
    And I am on "admin/reports/data-policy-agreements"

    Then I should not see the text "User consents not found."
    And I should see "Not agree" in the "td.views-field-state" element

  Scenario: Check data policy agreements report with Agree record

    Given I am logged in as "behatuser"
    And I am on "data-policy-agreement"
    And I check the box "edit-data-policy-data-policy-1"
    And I press "Save"
    And I should be on the homepage
    And I should not see the success message "We published a new version of the data protection statement. You can review the data protection statement here."

    When I am logged in as "behatsitemanager" with the "without consent" permission
    And I am on "admin/reports/data-policy-agreements"

    Then I should not see the text "User consents not found."
    And I should see "Agree" in the "td.views-field-state" element
