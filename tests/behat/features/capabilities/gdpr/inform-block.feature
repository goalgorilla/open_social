@api
Feature: Inform about personal data collection
  Benefit: So I can make an informed decision.
  Role: As a Verified
  Goal/desire: I want to understand what data the site collects about me

  Background:

    Given users:
      | name               | mail                           | status | roles         |
      | behatadministrator | behatadministrator@example.com | 1      | administrator |
      | behatsitemanager   | behatsitemanager@example.com   | 1      | sitemanager   |
      | behatuser          | behatuser@example.com          | 1      | verified      |
    And I enable the module "social_gdpr"
    And I turn off ckeditor
    And I set the GDPR Consent Text to "I read and consent to the [id:1*]"

    # Add data policy block
    And I am logged in as "behatadministrator"
    And I am on "admin/structure/block/add/data_policy_inform_block/"
    And I should see "Data Policy Inform" in the ".form-item--settings-admin-label" element
    And I select "Complementary top" from "Region"
    And I click the xth "0" element with the css ".button.form-submit"
    And I should see "The block configuration has been saved."
    And I logout

  Scenario: Check fields at Inform Consent form

    Given I am logged in as "behatsitemanager"

    When I am on "admin/config/system/inform-consent"
    And I click "Add page"

    Then I should be on "admin/config/system/inform-consent/add"
    And I should see checked the box "Enable on this page"
    And I should see "Title" in the ".form-item--label.form-type--textfield label.form-required" element
    And I should see "Page" in the ".form-item--page.form-type--textfield label.form-required" element
    And I should see "Summary" in the ".form-item--summary-value.form-type--textarea label.form-required" element
    And I should see "Description" in the ".form-item--body-value.form-type--textarea label:not(.form-required)" element

  Scenario: Add inform consent using administration page.

    Given I am logged in as "behatsitemanager"

    When I am on "admin/config/system/inform-consent"
    And I click "Add page"
    And I fill in "Title" with "Inform block title for sign up page"
    And I fill in "Page" with "/user/register"
    And I fill in "Summary" with "Inform block summary for sign up page"
    And I fill in "Description" with "Inform block description for sign up page"
    And I press "Save"

    Then I should be on "admin/config/system/inform-consent"
    And I should see the text "Saved the Inform block title for sign up page Example."
    And I should see the text "Inform block title for sign up page"
    And I should see the text "/user/register"
    And I should see the text "Yes"

  Scenario: Check inform block at Sign Up page.

    Given inform_blocks:
      | id | label                               | page           | summary                                    | body                                      | status |
      | 1  | Inform block title for sign up page | /user/register | Inform block summary for sign up page      | Inform block description for sign up page | 1      |
    And I click "Sign up"
    And I should see the heading "Inform block title for sign up page" in the "Sidebar second" region
    And I should see the text "Inform block summary for sign up page"
    And I should see the link "Read more"

    When I click "Read more"

    Then I wait for AJAX to finish
    And I should see "Inform block title for sign up page" in the ".ui-dialog-title" element
    And I should see "Inform block description for sign up page" in the ".ui-dialog-content" element

  Scenario: Add inform consent to user edit page.

    Given inform_blocks:
      | id | label                                 | page           | summary                                 | status |
      | 1  | Inform block title for user edit page | /user/*/edit   | Inform block summary for user edit page | 1      |

    When I am logged in as "behatuser"
    And I click "Profile of behatuser"
    And I click "Settings"

    Then I should see the heading "Inform block title for user edit page" in the "Sidebar second" region
    And I should see the text "Inform block summary for user edit page"
    And I should not see the link "Read more"

  Scenario: Add disabled inform consent to user edit page.

    Given inform_blocks:
      | id | label                                 | page           | summary                                  | status |
      | 1  | Inform block title for user edit page | /user/*/edit   | Inform block summary for user edit page  | 0      |

    When I am logged in as "behatuser"
    And I click "Profile of behatuser"
    And I click "Settings"

    Then I should not see the text "Inform block title for user edit page"
    And I should not see the text "Inform block summary for user edit page"
