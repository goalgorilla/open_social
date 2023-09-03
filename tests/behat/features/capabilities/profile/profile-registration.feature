@api @javascript
Feature: Registration makes it easy to fill out a profile

  Scenario: Fields configured to be shown on registration are shown there
    Given the profile fields are enabled:
      | Field name         |
      | Address            |
      | Banner Image       |
      | Email              |
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
#    Given I am logged in as a user with the sitemanager role
#    And I am on "/admin/config/people/social-profile"
#    And I fill in the profile fields form with:
    And the profile field settings:
      | Field name         | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | User can edit value | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Address            | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Banner Image       | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Email              | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Expertise          | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | First name         | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Function           | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Profile image      | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Interests          | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Last name          | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Nationality        | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Nickname           | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Organization       | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Phone number       | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Preferred language | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Profile tag        | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Self introduction  | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Summary            | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
#    And I press "Save configuration"
    And I am an anonymous user

    When I am on the registration page

    Then I should see a required field labeled "Country"
    And I should see a required field labeled "Banner Image"
    And I should see a required field labeled "Email"
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
    # @todo The profile tag is currently stored as separate configuration rather
    # than field level configuration so it doesn't yet work with our new system
    # it also requires tags to exist to show up.
    # And I should see a required field labeled "Profile tag"
    And I should see a required field labeled "Self introduction"
    And I should see a required field labeled "Summary"
