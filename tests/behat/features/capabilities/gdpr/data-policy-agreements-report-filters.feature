@api
Feature: Data policy agreements report filters
  Benefit: So I can access and view all agreements using filters from data policy
  Role: As a SM
  Goal/desire: I want to know which users did (not) gave consent to the data policy

  Background:

    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
      | behatuser1       | behatuser1@example.com       | 1      | verified    |
      | behatuser2       | behatuser2@example.com       | 1      | verified    |
      | behatuser3       | behatuser3@example.com       | 1      | verified    |
    And I enable the module "social_gdpr"
    And data_policies:
      | name               | field_description       |
      | Terms & Conditions | No rights in this test  |
    And I set the GDPR Consent Text to "I read and consent to the [id:1]"

    # Access with each user to save they data policy
    # The user 1 will have undecided data policy
    And I am logged in as "behatuser1"
    And I click "here"

    # The user 2 will have not agree data policy
    And I am logged in as "behatuser2"
    And I am on "data-policy-agreement"
    And I press "Save"

    # The user 3 will have agree data policy
    And I am logged in as "behatuser3"
    And I am on "data-policy-agreement"
    And I check the box "edit-data-policy-data-policy-1"
    And I press "Save"

  Scenario: Check Agree filter from data policy agreements report

    Given I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    And I should see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I check the box "Agree"
    And I press "Apply"

    Then I should not see the link "behatuser1"
    And I should not see the link "behatuser2"
    And I should see the link "behatuser3"

  Scenario: Check Not Agree filter from data policy agreements report

    Given I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    And I should see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I check the box "Not agree"
    And I press "Apply"

    Then I should not see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should not see the link "behatuser3"

  Scenario: Check Undecided filter from data policy agreements report

    Given I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    And I should see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I check the box "Undecided"
    And I press "Apply"

    Then I should see the link "behatuser1"
    And I should not see the link "behatuser2"
    And I should not see the link "behatuser3"
