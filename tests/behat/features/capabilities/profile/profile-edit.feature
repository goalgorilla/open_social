@api @account @profile @DS-701 @api @edit-profile
Feature: Users can edit profiles
  Benefit: In order to present myself to other people
  Role: LU
  Goal/desire: I want to have a profile

  Background:
    Given I disable that the registered users to be verified immediately

  Scenario: As an authenticated user I can edit my own profile
    Given I am logged in as a user with the authenticated role
    And I am viewing my profile

    When I click "Edit profile information"
    And I fill in the following:
      | First name   | John        |
      | Last name    | Doe         |
      | Function     | Behat test  |
      | Organization | GoalGorilla |
      | Phone number | 911         |
    And I select "UA" from "Country"
    And I wait for AJAX to finish
    And I fill in the following:
      | City           | Lviv           |
      | Street address | Fedkovycha 60a |
      | Postal code    | 79000          |
      | Oblast         | Lviv oblast    |
    And I fill in the "edit-field-profile-self-introduction-0-value" WYSIWYG editor with "Self intro text."
    And I press "Save"

    Then I should see "The profile has been saved"
    And I should see "John Doe"
    And I should see "GoalGorilla"
    And I should see "Behat test"
    And I should see "911"
    And I should see "Fedkovycha 60a"
    And I should see "79000"
    And I should see "Lviv"
    And I should see "Lviv oblast"
    And I should see "Self intro text"

  Scenario: As an anonymous user I can not edit my profile
    Given I am an anonymous user

    When I try to edit the profile of anonymous

    Then I should be asked to login

  Scenario: As a sitemanager I can edit the profile of another user
    Given users:
      | name    | status | roles    |
      | janedoe | 1      | verified |
    And I am logged in as a user with the sitemanager role

    When I try to edit the profile of janedoe
    And I fill in the following:
      | First name   | Jane        |
      | Last name    | Doe         |
      | Function     | Boss        |
      | Organization | NSA         |
    And I press "Save"

    Then I should see "The profile has been saved"
    And I should see "Jane Doe"
    And I should see "NSA"
    And I should see "Boss"

  Scenario: A content manager can edit fields on another user's profile if configured
    Given users:
      | name    | status | roles    |
      | janedoe | 1      | verified |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And I am logged in as a user with the contentmanager role

    When I try to edit the profile of janedoe
    And I fill in the following:
      | First name   | Jane        |
      | Last name    | Doe         |
    And I press "Save"

    Then I should see "The profile has been saved"
    And I should see "Jane Doe"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: A content manager can not edit fields on another user's profile if configured
    Given users:
      | name    | status | roles    |
      | janedoe | 1      | verified |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Last name          | true                | Community  | false                    | false                           | false                         | false                            | false                          | true                 | true     |
    And I am logged in as a user with the contentmanager role

    When I try to edit the profile of janedoe

    # When a user can't edit any fields they should be denied access to the
    # entire page.
    Then I should be denied access

  Scenario: Enabled fields show on the edit form
    # Profile tag field only shows when there are tags in a hierarchy.
    Given profile_tag terms:
      | name        | parent      |
      | Profile tag |             |
      | Foo         | Profile tag |
    And the profile fields are enabled:
      | Field name         |
      | Address            |
      | Banner Image       |
      | Expertise          |
      | First name         |
      | Function           |
      | Profile Image      |
      | Interests          |
      | Last name          |
      | Nationality        |
      | Nickname           |
      | Organization       |
      | Phone number       |
      | Preferred language |
      | Profile tag        |
      | Self introduction  |
      | Summary            |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Address            | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Banner Image       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Expertise          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | First name         | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Function           | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Profile Image      | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Interests          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Nationality        | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Nickname           | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Organization       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Phone number       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Preferred language | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Profile tag        | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Self introduction  | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Summary            | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
    And I am logged in as a user with the authenticated role

    When I am editing my profile

    Then I should see a required field labeled "Country"
    And I should see a field labeled "Street address"
    And I should see a field labeled "City"
    And I should see a field labeled "Postal code"
    And I should see a required field labeled "Banner Image"
    And I should see a required field labeled "Expertise"
    And I should see a required field labeled "First name"
    And I should see a required field labeled "Function"
    And I should see a required field labeled "Profile image"
    And I should see a required field labeled "Interests"
    And I should see a required field labeled "Last name"
    And I should see a required field labeled "Nationality"
    And I should see a required field labeled "Nickname"
    And I should see a required field labeled "Organization"
    And I should see a required field labeled "Phone number"
    And I should see a required field labeled "Profile tag"
    And I should see a required field labeled "Self introduction"
    And I should see a required field labeled "Summary"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Disabled fields don't show on the edit form
    # Profile tag field only shows when there are tags in a hierarchy.
    Given profile_tag terms:
      | name        | parent      |
      | Profile tag |             |
      | Foo         | Profile tag |
    And the profile fields are disabled:
      | Field name         |
      | Address            |
      | Banner Image       |
      | Expertise          |
      | First name         |
      | Function           |
      | Profile Image      |
      | Interests          |
      | Last name          |
      | Nationality        |
      | Nickname           |
      | Organization       |
      | Phone number       |
      | Profile tag        |
      | Self introduction  |
      | Summary            |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Address            | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Banner Image       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Expertise          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | First name         | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Function           | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Profile Image      | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Interests          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Nationality        | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Nickname           | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Organization       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Phone number       | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Profile tag        | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Self introduction  | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
      | Summary            | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | true     |
    And I am logged in as a user with the authenticated role

    When I am editing my profile

    Then I should not see the text "Country"
    And I should not see the text "Street address"
    And I should not see the text "City"
    And I should not see the text "Postal code"
    And I should not see the text "Banner Image"
    And I should not see the text "Expertise"
    And I should not see the text "First Name"
    And I should not see the text "Function"
    And I should not see the text "Profile Image"
    And I should not see the text "Interests"
    And I should not see the text "Last name"
    And I should not see the text "Nationality"
    And I should not see the text "Nickname"
    And I should not see the text "Organization"
    And I should not see the text "Phone number"
    And I should not see the text "Profile tag"
    And I should not see the text "Self introduction"
    And I should not see the text "Summary"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout
