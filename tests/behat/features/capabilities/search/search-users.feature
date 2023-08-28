@api @search @stability @DS-700 @stability-3 @search-users
Feature: Search people
  Benefit: In order to find user on the platform (to find information about someone, find content of someone, or contact the user).
  Role: As a Verified
  Goal/desire: I want to find people

  Background:
    Given I disable that the registered users to be verified immediately
    And users:
      | name                 | status | roles    |
      | tjakka new user      | 1      |          |
      | tjakka verified user | 1      | verified |
      | blocked user         | 0      | verified |
    And groups with non-anonymous owner:
      | label                  | field_group_description | type           | field_flexible_group_visibility |
      | Tjakka public group    | Tjakka group            | flexible_group | public                          |
      | Tjakka community group | Tjakka group            | flexible_group | community                       |
      | Tjakka secret group    | Tjakka group            | flexible_group | members                         |
    And events with non-anonymous author:
      | title                  | body        | field_content_visibility | field_event_date    |
      | Tjakka public event    | Description | public                   | 2100-01-01T12:00:00 |
      | Tjakka community event | Description | community                | 2100-01-01T12:00:00 |
      | Tjakka group event     | Description | group                    | 2100-01-01T12:00:00 |
    And topics with non-anonymous author:
      | title                  | body          | status | field_content_visibility | field_topic_type |
      | Tjakka public topic    | Description   | 1      | public                   | news             |
      | Tjakka community topic | Description   | 1      | community                | news             |
      | Tjakka group topic     | Description   | 1      | group                    | news             |
    And Search indexes are up to date

  Scenario: Anonymous user can not use users search
    Given I am an anonymous user

    When I search users for "tjakka"

    Then I should be asked to login

  Scenario: Authenticated user can not use users search
    Given I am logged in as a user with the authenticated role

    And I search users for "tjakka"

    Then I should be denied access

  Scenario: Verified users can use users search
    Given I am logged in as a user with the verified role

    When I search users for "tjakka"

    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should not see "Tjakka public group"
    And I should not see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should not see "Tjakka public event"
    And I should not see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should not see "Tjakka public topic"
    And I should not see "Tjakka community topic"
    And I should not see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Contentmanager users can use users search
    Given I am logged in as a user with the contentmanager role

    When I search users for "tjakka"

    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should not see "Tjakka public group"
    And I should not see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should not see "Tjakka public event"
    And I should not see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should not see "Tjakka public topic"
    And I should not see "Tjakka community topic"
    And I should not see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Sitemanager users can use users search
    Given I am logged in as a user with the sitemanager role

    When I search users for "tjakka"

    # @todo it might be a UX improvement if SM can find unverified users.
    Then I should not see "tjakka new user"
    And I should see "tjakka verified user"
    And I should not see "blocked user"

    And I should not see "Tjakka public group"
    And I should not see "Tjakka community group"
    And I should not see "Tjakka secret group"

    And I should not see "Tjakka public event"
    And I should not see "Tjakka community event"
    And I should not see "Tjakka group event"

    And I should not see "Tjakka public topic"
    And I should not see "Tjakka community topic"
    And I should not see "Tjakka group topic"

    # Until https://github.com/jhedstrom/drupalextension/issues/641
    And I logout

  Scenario: Users can filter by Expertise
    Given expertise terms:
      | name      |
      | eCommerce |
    And users:
      | name               | status | roles    |
      | tjakka tagged user | 1      | verified |
    # @todo Replace all this
    And I am logged in as a user with the sitemanager role
    And I am on the profile of "tjakka tagged user"
    And I click "Edit profile information"
    And I fill in "Expertise" with "eCommerce"
    And I press "Save"
    # with this
    # And user "tjakka tagged user" has a profile filled with:
    #   | field_profile_expertise | eCommerce |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search users for "tjakka"
    And I fill in "Expertise" with "eCommerce"
    And I press "Filter"

    Then I should not see "tjakka new user"
    And I should not see "tjakka verified user"
    And I should not see "blocked user"
    And I should see "tjakka tagged user"

  Scenario: Users can filter by Interest
    Given interests terms:
      | name      |
      | eCommerce |
    And users:
      | name               | status | roles    |
      | tjakka tagged user | 1      | verified |
    # @todo Replace all this
    And I am logged in as a user with the sitemanager role
    And I am on the profile of "tjakka tagged user"
    And I click "Edit profile information"
    And I fill in "Interests" with "eCommerce"
    And I press "Save"
    # with this
    # And user "tjakka tagged user" has a profile filled with:
    #   | field_profile_interests | eCommerce |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search users for "tjakka"
    And I fill in "Interest" with "eCommerce"
    And I press "Filter"

    Then I should not see "tjakka new user"
    And I should not see "tjakka verified user"
    And I should not see "blocked user"
    And I should see "tjakka tagged user"

  Scenario: Users can filter by Profile tag
    Given profile_tag terms:
      | name        | parent      |
      | Profile tag |             |
      | eCommerce   | Profile tag |
    And users:
      | name               | mail                     | status | roles    |
      | tjakka tagged user | tjakka@example.localhsot | 1      | verified |
    And user "tjakka tagged user" has a profile filled with:
      | field_profile_profile_tag | eCommerce |
    And Search indexes are up to date
    And I am logged in as a user with the verified role

    When I search users for "tjakka"
    And I select "eCommerce" from "Profile tag"
    And I press "Filter"

    Then I should not see "tjakka new user"
    And I should not see "tjakka verified user"
    And I should not see "blocked user"
    And I should see "tjakka tagged user"

  Scenario: Search only shows results for profile of the type 'profile'
    # @todo Implement this to ensure search doesn't show non-user profiles.
