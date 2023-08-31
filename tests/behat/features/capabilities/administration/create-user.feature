@api @javascript @email-spool
Feature: User creation for site managers

  Scenario: As a site manager I can create a user from the people overview
    Given I am logged in as a user with the sitemanager role
    And I am on "/admin/people"

    When I click "Add user"
    And I fill in "test@example.com" for "Email address"
    And I fill in "test" for "Username"
    And I fill in "test" for "Password"
    And I fill in "test" for "Confirm password"
    And I press "Create new account"

    Then I should see "A welcome message with further instructions has been emailed to the new user test"
    And I should have an email with subject "An administrator created an account for you at Open Social" and in the content:
      | content                                                                                                                                                   |
      | test,                                                                                                                                                     |
      | A site administrator at Open Social has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser: |
      | This link can only be used once to log in and will lead you to a page where you can set your password.                                                    |
      | After setting your password, you will be able to log in at http://web/user in the future using:                                                           |
      | username: test                                                                                                                                            |
      | password: Your password                                                                                                                                   |
      | Open Social team                                                                                                                                          |

  Scenario: As a site manager I can create a user from the people overview without sending a welcome email
    Given I am logged in as a user with the sitemanager role
    And I am on "/admin/people"

    When I click "Add user"
    And I fill in "test@example.com" for "Email address"
    And I fill in "test" for "Username"
    And I fill in "test" for "Password"
    And I fill in "test" for "Confirm password"
    And I uncheck the box "Notify user of new account"
    And I press "Create new account"

    Then I should see "Created a new user account for test"

  Scenario: A real name should show up in the welcome email if a site manager fills it out during account creation
    Given the profile fields are enabled:
      | Field name         |
      | First name         |
      | Last name          |
    And the profile field settings:
      | Field name         | User can edit value | Visibility | User can edit visibility | Always show for Content manager | Always show for Verified user | Allow editing by Content manager | Allow editing by verified user | Show at registration | Required |
      | First name         | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | false    |
      | Last name          | true                | Private    | false                    | false                           | false                         | false                            | false                          | true                 | false    |
    And I am logged in as a user with the sitemanager role
    And I am on "/admin/people"

    When I click "Add user"
    And I fill in "test@example.com" for "Email address"
    And I fill in "test" for "Username"
    And I fill in "test" for "Password"
    And I fill in "test" for "Confirm password"
    And I fill in "John" for "First name"
    And I fill in "Doe" for "Last name"
    And I press "Create new account"

    Then I should see "A welcome message with further instructions has been emailed to the new user test"
    And I should have an email with subject "An administrator created an account for you at Open Social" and in the content:
      | content                                                                                                                                                   |
      | John Doe,                                                                                                                                                 |
      | A site administrator at Open Social has created an account for you. You may now log in by clicking this link or copying and pasting it into your browser: |
      | This link can only be used once to log in and will lead you to a page where you can set your password.                                                    |
      | After setting your password, you will be able to log in at http://web/user in the future using:                                                           |
      | username: test                                                                                                                                            |
      | password: Your password                                                                                                                                   |
      | Open Social team                                                                                                                                          |

  Scenario: As a site manager I can create a user from the people overview when I configured profile fields to be required but disabled them
    Given all profile fields are disabled
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
    And I am logged in as a user with the sitemanager role
    And I am on "/admin/people"

    When I click "Add user"
    And I fill in "test@example.com" for "Email address"
    And I fill in "test" for "Username"
    And I fill in "test" for "Password"
    And I fill in "test" for "Confirm password"
    And I press "Create new account"

    Then I should see "A welcome message with further instructions has been emailed to the new user test"
