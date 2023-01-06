@api @javascript
Feature: Manage which profile fields are enabled on a platform and the permissions around them

  Scenario Outline: Profile settings page is protected from non site manager roles
    Given I am logged in as a user with the <role> role

    When I am on "/admin/config/people/social-profile"

    Then I should be denied access

  Examples:
    | role            |
    | authenticated   |
    | verified        |
    | contentmanager |

  Scenario: Profile settings page is protected from anonymous users
    Given I am an anonymous user

    When I am on "/admin/config/people/social-profile"

    Then I should be asked to login

  Scenario: As a site manager I can configure the profile field visibility settings
    Given I am logged in as a user with the sitemanager role
    And I am on "/admin/config/people/social-profile"

    When I disable the fields on the profile fields form:
      | Field name  |
      | Expertise   |
      | Nationality |
    And I enable the fields on the profile fields form:
      | Field name  |
      | Nickname    |
    And I fill in the profile fields form with:
      | Field name  | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | User can edit value | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Address     | Private    | true                     | true                            | false                         | true                | false                            | false                          | true                 | true     |
      | Function    | Public     | false                    | false                           | false                         | true                | true                             | false                          | false                | false    |
      | Nickname    | Public     | false                    | true                            | false                         | true                | true                             | false                          | true                 | false    |
      | First name  | Community  | true                     | false                           | false                         | true                | false                            | false                          | true                 | false    |
      | Last name   | Community  | true                     | false                           | false                         | true                | false                            | false                          | true                 | false    |
    And I press "Save configuration"

    Then the profile field settings should be updated

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
    And the profile field settings:
      | Field name         | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | User can edit value | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | Address            | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Banner Image       | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Email              | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Expertise          | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | First name         | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Function           | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
      | Profile Image      | Private    | true                     | false                           | false                         | true                | false                            | false                          | true                 | true     |
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
    And I am an anonymous user

    When I am on the registration page

    Then I should see a required field labeled "Address"
    And I should see a required field labeled "Banner Image"
    And I should see a required field labeled "Email"
    And I should see a required field labeled "Expertise"
    And I should see a required field labeled "First Name"
    And I should see a required field labeled "Function"
    And I should see a required field labeled "Profile Image"
    And I should see a required field labeled "Interests"
    And I should see a required field labeled "Last name"
    And I should see a required field labeled "Nationality"
    And I should see a required field labeled "Nickname"
    And I should see a required field labeled "Organization"
    And I should see a required field labeled "Phone number"
    And I should see a required field labeled "Preferred language"
    And I should see a required field labeled "Profile tag"
    And I should see a required field labeled "Self introduction"
    And I should see a required field labeled "Summary"

  Scenario: Enabled fields show on the edit form
    # Profile tag field only shows when there's at least one tag.
    Given profile_tag terms:
      | name | parent |
      | Foo  |        |
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

  # TODO: This test fails because `status` is not respected for `FieldConfig`
  #  entities, we can't handle this on an access level because form validations
  #  for `required` fields would still be applied, perhaps we can config_load alter?
  # Looking at EntityViewDisplay/EntityFormDisplay ::getRenderer could solve this.
  # We'd have to figure something out for search though.
  Scenario: Disabled fields don't show on the edit form
    # Profile tag field only shows when there's at least one tag.
    Given profile_tag terms:
      | name | parent |
      | Foo  |        |
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

  # TODO: Scenario: Enabled fields show on the profile view page

  # TODO: Scenario: Disabled fields don't show on the profile view page

  # TODO: Scenario: Enabled fields can be used in search

  # TODO: Scenario: Disabled fields can't be used in search

  # TODO: Scenario: Access is properly checked

  # TODO: Scenario: Sitemanagers can always see things

