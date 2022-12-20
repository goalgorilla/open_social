@bia @api
Feature: Manage which profile fields are enabled on a platform and the permissions around them

  Scenario: Successfully see Profile Fields
    Given I am logged in as a user with the site_manager role

    When I go to "/admin/config/people/social-profile"

    Then I should see "Address"
      And I should see "Banner Image"
      And I should see "Email"
      And I should see "Expertise"
      And I should see "First name"
      And I should see "Function"
      And I should see "Profile image"
      And I should see "Interests"
      And I should see "Last name"
      And I should see "Nationality"
      And I should see "Nickname"
      And I should see "Organization"
      And I should see "Phone number"
      And I should see "Preferred language"
      And I should see "Profile tag"
      And I should see "Self introduction"
      And I should see "Summary"

  Scenario: Change the profile and check if it is updated on user profile
    Given I am logged in as a user with the site_manager role

    When I go to "/admin/config/people/social-profile"
      # Disabel Banner image field
      And I check the box "edit-fields-list-field-profile-banner-image-disabled"
      # Make Phone number field required
      And I check the box "edit-fields-list-field-profile-phone-number-required"
      # Make Self introduction field required
      And I check the box "edit-fields-list-field-profile-self-introduction-disabled"
      And I press "Save configuration"
      And the cache has been cleared
      # Going to user profile page to check if the changes were effective
      And I go to "/user/14/profile"

    # Check if the change on the admin area was effective
    Then I should not see "Banner Image"
      And I should not see "Self introduction"
      And I press "Save"
      Then I should see "1 error has been found: Phone number"
        And I should see "Phone number field is required."

  Scenario: Undo test changes
    Given I am logged in as a user with the site_manager role

    When I go to "/admin/config/people/social-profile"
      # Enable Banner image field
      And I uncheck the box "edit-fields-list-field-profile-banner-image-disabled"
      # Make Phone number field not required
      And I uncheck the box "edit-fields-list-field-profile-phone-number-required"
      # Make Self introduction field not required
      And I uncheck the box "edit-fields-list-field-profile-self-introduction-disabled"

    Then I press "Save configuration"
      And I should see "The configuration options have been saved."
