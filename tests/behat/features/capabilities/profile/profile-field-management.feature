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
