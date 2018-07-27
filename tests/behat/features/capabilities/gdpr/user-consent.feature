@api @gdpr @user-consent @DS-5586 @stability @stability-4
Feature: Give user consent
  Benefit: So I can decide what to do with users personal data
  Role: As a SM
  Goal/desire: I want to know which users did (not) gave consent to the data policy

  Scenario: Successfully view user consent

    Given users:
      | name             | mail                         | status | roles       |
      | behatsitemanager | behatsitemanager@example.com | 1      | sitemanager |
      | behatuser1       | behatuser1@example.com       | 1      |             |
      | behatuser2       | behatuser2@example.com       | 1      |             |
      | behatuser3       | behatuser3@example.com       | 1      |             |

    Given I enable the module "social_gdpr"

    Given I am logged in as "behatsitemanager"
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

    Given I am logged in as "behatuser1"
    Then I should be on the homepage
    And I should see the success message "We published a new version of the data policy. You can review the data policy here."

    When I click "here"
    Then I should be on "data-policy-agreement"

    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should not see the text "User consents not found."
    And I should see "Undecided" in the "td.views-field-state" element

    When I am logged in as "behatuser1"
    Then I should not see the success message "We published a new version of the data policy. You can review the data policy here."

    When I am on "data-policy-agreement"
    And I press "Save"
    Then I should be on the homepage

    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should see "Not agree" in the "td.views-field-state" element

    When I am logged in as "behatuser1"
    And I am on "data-policy-agreement"
    And I check the box "data_policy"
    And I press "Save"
    Then I should be on the homepage

    When I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should see "Agree" in the "td.views-field-state" element

    When I am logged in as "behatuser2"
    And I click "here"
    And I am logged in as "behatuser3"
    And I click "here"
    And I press "Save"
    And I am logged in as "behatsitemanager"
    And I am on "admin/reports/data-policy-agreements"
    Then I should see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I check the box "Agree"
    And I press "Apply"
    Then I should see the link "behatuser1"
    And I should not see the link "behatuser2"
    And I should not see the link "behatuser3"

    When I uncheck the box "Agree"
    And I check the box "Not agree"
    And I press "Apply"
    Then I should not see the link "behatuser1"
    And I should not see the link "behatuser2"
    And I should see the link "behatuser3"

    When I uncheck the box "Not agree"
    And I check the box "Undecided"
    And I press "Apply"
    Then I should not see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should not see the link "behatuser3"

    When I check the box "Not agree"
    And I press "Apply"
    Then I should not see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I check the box "Agree"
    And I press "Apply"
    Then I should see the link "behatuser1"
    And I should see the link "behatuser2"
    And I should see the link "behatuser3"

    When I uncheck the box "Undecided"
    And I press "Apply"
    Then I should see the link "behatuser1"
    And I should not see the link "behatuser2"
    And I should see the link "behatuser3"
