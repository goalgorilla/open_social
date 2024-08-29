@api @no-update
Feature: User is Verified
  Benefit: In order to distinguish between actual verified users who earned it to engage with the community and new users who might need to process payment or details in order to become trusted.
  Role: As a Verified
  Goal/desire: New registered users get the "verified user & authenticated" role when they are registered.

  @verified-immediately-enabled
  Scenario: "New users automatically get the Verified User role assigned" is enabled.
    # User registration.
    Given I am on the homepage

    When I click "Sign up"
    And I fill in the following:
      | Email address | verified_dude@example.com |
      | Username      | verified_dude             |
    And I press "Create new account"

    Then I should see the success message "A welcome message with further instructions has been sent to your email address."

    # Be sure that "New users automatically get the Verified User role assigned" is enabled by
    # default.
    And I am logged in as an "sitemanager"
    And I am on "admin/config/people/accounts"
    And I should see checked the box "New users automatically get the Verified User role assigned"

    # Check that the registered user has a Verified role.
    And I am logged in as an "administrator"
    And I am on "admin/people"
    And I fill in the following:
      | Name or email contains | verified_dude@example.com |
    And I press "Filter"
    And I should see "verified_dude"
    And I click "Edit account"
    And I should see checked the box "Verified user"

  @verified-immediately-disable
  Scenario: "New users automatically get the Verified User role assigned" is enabled.
    # Be sure that "New users automatically get the Verified User role assigned" is disable.
    Given I am logged in as an "sitemanager"
    And I am on "admin/config/people/accounts"
    And I should see checked the box "New users automatically get the Verified User role assigned"
    And I uncheck the box "New users automatically get the Verified User role assigned"
    And I press "Save configuration"
    And I logout

    # User registration.
    And I am on the homepage
    And I click "Sign up"
    And I fill in the following:
      | Email address | not_verified_dude@example.com  |
      | Username      | not_verified_dude              |
    And I press "Create new account"
    And I should see the success message "A welcome message with further instructions has been sent to your email address."

    # Check that the registered user has no a Verified role.
    And I am logged in as an "administrator"
    And I am on "admin/people"
    And I fill in the following:
      | Name or email contains | not_verified_dude@example.com |
    And I press "Filter"
    And I should see "not_verified_dude"
    And I click "Edit account"
    And I should see unchecked the box "Verified user"
    And I enable that the registered users to be verified immediately
