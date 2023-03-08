@api @javascript
Feature: A profile can be viewed depending on platform configuration

  Background:
    Given users:
      | name    | status | roles    |
      | target  | 1      | verified |
    And I disable that the registered users to be verified immediately

  Scenario: Anonymous users can't view profile pages
    Given I am an anonymous user

    When I try to view the profile of target

    Then I should be asked to login

  Scenario: Authenticated users can view public data on profile pages
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the authenticated role

    When I try to view the profile of target

    Then I should see "Jane Doe"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Authenticated users can not view community data on profile pages by default
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the authenticated role

    When I try to view the profile of target

    Then I should not see "Jane Doe"

  Scenario Outline: Verified users can view community and public data on profile pages
    Given the profile field settings:
      | Field name         | User can edit value | Visibility   | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the verified role

    When I try to view the profile of target

    Then I should see "Jane Doe"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

    Examples:
     | visibility |
     | Public     |
     | Community  |

  Scenario: Verified users can not view private data on profile pages
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the verified role

    When I try to view the profile of target

    Then I should not see "Jane Doe"

  Scenario: Content managers can not view private data on profile pages by default
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the contentmanager role

    When I try to view the profile of target

    Then I should not see "Jane Doe"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Content managers can view private data on profile pages when so configured
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | true                            | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | true                            | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the contentmanager role

    When I try to view the profile of target

    Then I should see "Jane Doe"

  Scenario: Site managers can view private data on profile pages
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And I am logged in as a user with the sitemanager role

    When I try to view the profile of target

    Then I should see "Jane Doe"

  Scenario: User always sees their own data even if it's private
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And I am logged in as a user with the authenticated role
    And I have a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |

    When I am viewing my profile

    Then I should see "Jane Doe"

  Scenario: User preference is ignored if they can't edit it even if it's filled
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane    |
      | vis_32785136480d615c3c4094e52fd0 | private |
      | field_profile_last_name          | Doe     |
      | vis_41e45bfd9baa40f1fb7f60e2e284 | private |
    And I am logged in as a user with the verified role

    When I try to view the profile of target

    Then I should see "Jane Doe"

  Scenario: User preference is respected if they can edit it
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | true                     | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Community  | true                     | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane    |
      | vis_32785136480d615c3c4094e52fd0 | private |
      | field_profile_last_name          | Doe     |
      | vis_41e45bfd9baa40f1fb7f60e2e284 | private |
    And I am logged in as a user with the verified role

    When I try to view the profile of target

    Then I should not see "Jane Doe"

  Scenario Outline: Users can not see disabled fields regardless of role
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And the profile fields are disabled:
      | Field name              |
      | Last name               |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane    |
      | field_profile_last_name          | Doe     |
    And I am logged in as a user with the <role> role

    When I try to view the profile of target

    Then I should see "Jane"
    And I should not see "Doe"

    Examples:
      | role           |
      | authenticated  |
      | verified       |
      | contentmanager |
      | sitemanager    |
