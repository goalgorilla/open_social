@api @javascript
Feature: Users can be found in searched based on viewable profile information

  Background:
    Given users:
      | name    | status | roles    |
      | target  | 1      | verified |
    And I disable that the registered users to be verified immediately

  Scenario Outline: Search results are adjusted based on field status
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Public     | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name | Jane |
      | field_profile_last_name  | Doe  |
    And the profile fields are <status>:
      | Field name |
      | First name |
      | Last name  |
    And I am logged in as a user with the verified role
    And Search indexes are up to date

    When I search users for "Jane Doe"

    Then I should <result_see> "Jane Doe" in the search results
    And I should <no_result_see> "No results found." in the search results

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Examples:
    | status   | result_see | no_result_see |
    | enabled  | see        | not see       |
    | disabled | not see    | see           |

  Scenario Outline: Fields with user configured visibility are properly locked behind the corresponding permissions
    Given the profile field settings:
      | Field name         | User can edit value | Visibility   | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Public       | true                     | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Public       | true                     | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane         |
      | vis_32785136480d615c3c4094e52fd0 | <visibility> |
      | field_profile_last_name          | Doe          |
      | vis_41e45bfd9baa40f1fb7f60e2e284 | <visibility> |
    And I am logged in as a user with the "list user,view any profile profile<permission>" permissions
    And Search indexes are up to date

    When I search users for "Jane Doe"

    Then I should <result_see> "Jane Doe" in the search results
    And I should <no_result_see> "No results found." in the search results

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

    Examples:
     | visibility | result_see | no_result_see | permission                                                                                                                                        |
     | public     | see        | not see       |                                                                                                                           |
     | community  | see        | not see       | ,view any profile fields                                                                                                  |
     | community  | see        | not see       | ,view any profile profile fields                                                                                          |
     | community  | see        | not see       | ,view community profile fields                                                                                            |
     | community  | see        | not see       | ,view community profile profile fields                                                                                    |
     | community  | not see    | see           |                                                                                                                           |
     | private    | see        | not see       | ,view any profile fields                                                                                                  |
     | private    | see        | not see       | ,view any profile profile fields                                                                                          |
     | private    | see        | not see       | ,view private field_profile_first_name profile profile fields,view private field_profile_last_name profile profile fields |
     | private    | not see    | see           |                                                                                                                           |

  Scenario Outline: Fields with centrally configured visibility are properly locked behind the corresponding permissions
    Given the profile field settings:
      | Field name         | User can edit value | Visibility   | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane         |
      | field_profile_last_name          | Doe          |
    And I am logged in as a user with the "list user,view any profile profile<permission>" permissions
    And Search indexes are up to date

    When I search users for "Jane Doe"

    Then I should <result_see> "Jane Doe" in the search results
    And I should <no_result_see> "No results found." in the search results

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

    Examples:
     | visibility | result_see | no_result_see | permission                                                                                                                                        |
     | Public     | see        | not see       |                                                                                                                          |
     | Community  | see        | not see       | ,view any profile fields                                                                                                  |
     | Community  | see        | not see       | ,view any profile profile fields                                                                                          |
     | Community  | see        | not see       | ,view community profile fields                                                                                            |
     | Community  | see        | not see       | ,view community profile profile fields                                                                                    |
     | Community  | not see    | see           |                                                                                                                          |
     | Private    | see        | not see       | ,view any profile fields                                                                                                  |
     | Private    | see        | not see       | ,view any profile profile fields                                                                                          |
     | Private    | see        | not see       | ,view private field_profile_first_name profile profile fields,view private field_profile_last_name profile profile fields |
     | Private    | not see    | see           |                                                                                                                          |

  Scenario: Search indices are updated when managed visibility is changed
    Given the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Community  | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane         |
      | field_profile_last_name          | Doe          |
    And I am logged in as a user with the "verified" role
    And Search indexes are up to date
    And I search users for "Jane Doe"
    And I should see "Jane Doe" in the search results

    When I am logged in as a user with the sitemanager role
    And I am on "/admin/config/people/social-profile"
    And I fill in the profile fields form with:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | Private    | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And I press "Save configuration"
    And Search indexes are up to date
    And I am logged in as a user with the "verified" role
    And I search users for "Jane Doe"

    Then I should not see "Jane Doe" in the search results
    And I should see "No results found." in the search results

  Scenario Outline: Role permissions are set correctly for roles that can access user search
    Given the profile field settings:
      | Field name         | User can edit value | Visibility   | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
      | Last name          | true                | <visibility> | false                    | false                           | false                         | true                             | false                          | true                 | true     |
    And user target has a profile filled with:
      | field_profile_first_name         | Jane         |
      | field_profile_last_name          | Doe          |
    And I am logged in as a user with the "<role>" role
    And Search indexes are up to date

    When I search users for "Jane Doe"

    Then I should <result_see> "Jane Doe" in the search results
    And I should <no_result_see> "No results found." in the search results

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Examples:
    | role           | visibility | result_see | no_result_see |
    | verified       | Public     | see        | not see       |
    | verified       | Community  | see        | not see       |
    | verified       | Private    | not see    | see           |
    | contentmanager | Public     | see        | not see       |
    | contentmanager | Community  | see        | not see       |
    | contentmanager | Private    | not see    | see           |
    | sitemanager    | Public     | see        | not see       |
    | sitemanager    | Community  | see        | not see       |
    | sitemanager    | Private    | see        | not see       |
