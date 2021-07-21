@api @account @registration @stability @perfect @security @verified-user
Feature: User is Verified
  Benefit: In order to distinguish between actual verified users who earned it to engage with the community and new users who might need to process payment or details in order to become trusted.
  Role: As a Verified
  Goal/desire: New registered users get the "verified user & authenticated" role when they are registered.

  @verified-immediately-enabled
  Scenario: "Registered users are verified immediately" is enabled.
    # User registration.
    Given I am on the homepage
    When I click "Sign up"
      And I fill in the following:
        | Email address | verified_dude@example.com |
        | Username      | verified_dude             |
      And I press "Create new account"
    Then I should see the success message "A welcome message with further instructions has been sent to your email address."

    # Be sure that "Registered users are verified immediately" is enabled by
    # default.
    Given I am logged in as an "sitemanager"
    When I am on "admin/config/people/accounts"
    Then I should see checked the box "Registered users are verified immediately"

    # Check that the registered user has a Verified role.
    Given I am logged in as an "administrator"
    When I am on "admin/people"
      And I fill in the following:
        | Name or email contains | verified_dude@example.com |
      And I press "Filter"
    Then I should see "verified_dude"
      And I click "Edit account"
      And I should see checked the box "Verified user"

  @verified-immediately-disable
  Scenario: "Registered users are verified immediately" is disable.
    # Be sure that "Registered users are verified immediately" is disable.
    Given I am logged in as an "sitemanager"
    When I am on "admin/config/people/accounts"
    Then I should see checked the box "Registered users are verified immediately"
      And I uncheck the box "Registered users are verified immediately"
      And I press "Save configuration"
      And I logout

    # User registration.
    Given I am on the homepage
    When I click "Sign up"
      And I fill in the following:
        | Email address | not_verified_dude@example.com  |
        | Username      | not_verified_dude              |
      And I press "Create new account"
    Then I should see the success message "A welcome message with further instructions has been sent to your email address."

    # Check that the registered user has no a Verified role.
    Given I am logged in as an "administrator"
    When I am on "admin/people"
      And I fill in the following:
        | Name or email contains | not_verified_dude@example.com |
      And I press "Filter"
    Then I should see "not_verified_dude"
      And I click "Edit account"
      And I should see unchecked the box "Verified user"
      And I enable that the registered users to be verified immediately
