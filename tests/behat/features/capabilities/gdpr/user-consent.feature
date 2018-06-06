@api @gdpr @user-consent @DS-5586 @stability @stability-4
Feature: Give user consent
  Benefit: In order to give user consent
  Role: As a LU
  Goal/desire: I want to give user consent

  Scenario: Successfully give user consent

    Given I enable the module "social_gdpr"
    Given I am logged in as a user with the "sitemanager" role and I have the following fields:
      | name | behatsitemanager |
    When I am on "admin/config/people/data-policy/settings"
    Then I should see the heading "Data policy settings" in the "Admin page title block" region
    And I should see checked the box "Enforce consent"
    And I should see the text "A user should give your consent on data policy when he creates an account."
    When I uncheck the box "Enforce consent"
    And I press "Save configuration"
    Then I should see the text "The configuration options have been saved."
    And I should see unchecked the box "Enforce consent"
    When I am on "admin/reports/data-policy-agreements"
    Then I should see the heading "Data Policy Agreements" in the "Admin page title block" region
    And I should see the text "Data policy revision"
    And I should see unchecked the box "Agree"
    And I should see unchecked the box "Not agree"
    And I should see unchecked the box "Undecided"
    And I should see the text "User consents not found."
    Given I am logged in as a user with the "authenticated user" role and I have the following fields:
      | name | behatuser |
    Then I should be on the homepage
    And I should see the success message "We published a new version of the data policy. You can review the data policy here."
    When I click "here"
    Then I should be on "data-policy-agreement"
    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should not see the text "User consents not found."
    And I should see "Undecided" in the "td.views-field-state" element
    When I am logged in as "behatuser"
    Then I should not see the success message "We published a new version of the data policy. You can review the data policy here."
    When I am on "data-policy-agreement"
    And I press "Save"
    Then I should be on the homepage
    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should see "Not agree" in the "td.views-field-state" element
    When I am logged in as "behatuser"
    And I am on "data-policy-agreement"
    And I check the box "data_policy"
    And I press "Save"
    Then I should be on the homepage
    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should see "Agree" in the "td.views-field-state" element
