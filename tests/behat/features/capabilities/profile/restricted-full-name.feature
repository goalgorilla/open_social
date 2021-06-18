@account @profile @api @issue-3039084 @stability @stability-2 @restricted-full-name
Feature: I want to restrict full name visibility when nickname is used
  Benefit: In order to have better privacy
  Role: LU
  Goal/desire: So I can hide my full name on the platform

  Background:
    Given I enable the module "social_profile_fields"
    And I enable the module "social_profile_privacy"
    And I enable the nickname field on profiles
    And users:
      | name   | mail                     | status | field_profile_first_name | field_profile_last_name | field_profile_nick_name | roles       |
      | user_1 | user_1@example.localhost | 1      | Open                     | User                    |                         |             |
      | user_2 | user_2@example.localhost | 1      | Secretive                | Person                  | Hide my name            |             |
      | user_3 | user_3@example.localhost | 1      |                          |                         | Completely Anonymous    |             |
      | sm     | site_manager@example.com | 1      |                          |                         |                         | sitemanager |


  Scenario: Extra protection for real names
    Given I restrict real name usage
    And Search indexes are up to date
    And I am logged in as an "authenticated user"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    But I should not see "Secretive Person"

    # Search only allows searching for real names when the nickname is not
    # filled in.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should not see "Hide my name"
    And I should not see "Secretive Person"

    When I search users for "Hide my name"
    Then I should see "Hide my name"

    # Searching for an exact full name should not expose it. This tests for a
    # reported bug that allowed users to guess hidden full names.
    When I search users for "Secretive Person"
    Then I should not see "Hide my name"

    # TODO: Add test for mentioning using Javascript?

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: View and search for real names when a user has the permission
    Given I restrict real name usage
    And Search indexes are up to date
    And I am logged in as a user with the "social profile privacy always show full name" permission

    # Profile displays the real name and nickname (if available).
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name (Secretive Person)"

    When I go to the profile of "user_3"
    Then I should see "Completely Anonymous"

    # Search always allows searching for real names.
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name (Secretive Person)"

    When I search users for "Hide my name"
    Then I should see "Hide my name (Secretive Person)"

    When I search users for "Completely"
    Then I should see "Completely Anonymous"

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  # This test ensures that searching by username works. It's included so that
  # when the next scenario (searching for username when names are restricted)
  # fails, we can be sure the cause is in the name restricting.
  # If this scenario fails then the next one will fail as well but something
  # else is broken.
  Scenario: Searching by username works when name is unrestricted
    Given I unrestrict real name usage
    And Search indexes are up to date
    And I am logged in as an "authenticated user"

    When I search users for "user"
    Then I should see "Open User"
    And I should see "Hide my name"
    And I should see "Completely Anonymous"

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: Searching by username still works when name is restricted
    Given I restrict real name usage
    And Search indexes are up to date
    And I am logged in as an "authenticated user"

    When I search users for "user"
    Then I should see "Open User"
    And I should see "Hide my name"
    And I should see "Completely Anonymous"

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  # This scenarios intentionally comes last since it's the Open Social default
  # and least likely to break. This reduces test times.
  Scenario: Nickname replaces full name when filled in
    Given I unrestrict real name usage
    And Search indexes are up to date
    And I am logged in as an "authenticated user"

    # Profile displays the correct name.
    When I go to the profile of "user_1"
    Then I should see "Open User"

    When I go to the profile of "user_2"
    Then I should see "Hide my name"
    And I should not see "Secretive Person"

    # Search shows Nickname but allows searching for real name
    When I search users for "Open"
    Then I should see "Open User"

    When I search users for "Secretive"
    Then I should see "Hide my name"

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  # TODO: Add test for mentioning using Javascript?

  Scenario: Successfully show First Name only
    # Globally enable the Firstname field and disable the Lastname, Nickname
    # fields access.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name"
    And I click the element with css selector "#edit-fields-field-profile-last-name--3"
    And I click the element with css selector "#edit-fields-field-profile-nick-name--3"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Check the profile of myself, and I should see Firstname and Lastname even
    # if the Lastname is hidden.
    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Open User"

    # Check my topic, and I should see Firstname and Lastname of the author even
    # if the Lastname is hidden.
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Ressinel's Topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Ressinel's Topic has been created."
    Then I should see "Open User"

    # Check the profile of someone else, and now I should see the Firstname
    # only.
    And I am on the profile of "user_2"
    And I click "Information"
    Then I should not see "Secretive Person"
    But I should see "Secretive"

    # Check the topic of someone else, and I should see only the Firstname of
    # the author.
    Given I am logged in as "user_2"
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Open"

    # We provide the ability to edit access to the Firstname, Lastname, and
    # Nickname fields for each user separately.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name--2"
    And I click the element with css selector "#edit-fields-field-profile-last-name--2"
    And I click the element with css selector "#edit-fields-field-profile-nick-name--2"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Enable the Firstname field and disable the Lastname, Nickname fields
    # access on my profile.
    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-first-name-1]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-last-name-0]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-nick-name-0]"
    And I press "Save"

    # Check the profile of myself, and I should see Firstname and Lastname even
    # if the Lastname is hidden.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Open User"

    # Check my topic, and I should see Firstname and Lastname of the author even
    # if the Lastname is hidden.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Open User"

    # Check the profile by someone else, and I should see the Firstname only.
    Given I am logged in as "user_2"
    Then I am on the profile of "user_1"
    And I click "Information"
    And I should not see "Open User"
    But I should see "Open"

    # Check the topic of someone else, and I should see only the Firstname of
    # the author.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Open"

    # Check sorting members in group.
    # First of all we need create a group.
    And I am on "group/add"
    And I press "Continue"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Test open group" in the "Hero block"

    # Adding members to a group.
    And I click "Manage members"
    Then I should see "Add members"
    When I click the group member dropdown
    And I click "Add directly"
    And I fill in select2 input ".form-type-select" with "Open User" and select "Open"
    And I wait for "3" seconds
    And I should see the button "Cancel"
    And I press "Save"

    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Open" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Open" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # SM can view hidden fields, check if it works properly for him.
    Given I am logged in as "sm"
    And I am on "/all-groups"
    And I click "Test open group"
    And I click "Manage members"
    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: Successfully show Last Name only
    # Globally enable the Firstname field and disable the Lastname, Nickname
    # fields access.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name--3"
    And I click the element with css selector "#edit-fields-field-profile-last-name"
    And I click the element with css selector "#edit-fields-field-profile-nick-name--3"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Check the profile of myself, and I should see Firstname and Lastname even
    # if the Lastname is hidden.
    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Open User"

    # Check my topic, and I should see Firstname and Lastname of the author even
    # if the Lastname is hidden.
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Ressinel's Topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Ressinel's Topic has been created."
    Then I should see "Open User"

    # Check the profile of someone else, and now I should see the Firstname
    # only.
    And I am on the profile of "user_2"
    And I click "Information"
    Then I should not see "Secretive Person"
    But I should see "Person"

    # Check the topic of someone else, and I should see only the Firstname of
    # the author.
    Given I am logged in as "user_2"
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "User"

    # We provide the ability to edit access to the Firstname, Lastname, and
    # Nickname fields for each user separately.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name--2"
    And I click the element with css selector "#edit-fields-field-profile-last-name--2"
    And I click the element with css selector "#edit-fields-field-profile-nick-name--2"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Enable the Firstname field and disable the Lastname, Nickname fields
    # access on my profile.
    Given I am logged in as "user_1"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-first-name-0]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-last-name-1]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-nick-name-0]"
    And I press "Save"

    # Check the profile of myself, and I should see Firstname and Lastname even
    # if the Lastname is hidden.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Open User"

    # Check my topic, and I should see Firstname and Lastname of the author even
    # if the Lastname is hidden.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Open User"

    # Check the profile by someone else, and I should see the Firstname only.
    Given I am logged in as "user_2"
    Then I am on the profile of "user_1"
    And I click "Information"
    And I should not see "Open User"
    But I should see "User"

    # Check the topic of someone else, and I should see only the Firstname of
    # the author.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "User"

    # Check sorting members in group.
    # First of all we need create a group.
    And I am on "group/add"
    And I press "Continue"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Test open group" in the "Hero block"

    # Adding members to a group.
    And I click "Manage members"
    Then I should see "Add members"
    When I click the group member dropdown
    And I click "Add directly"
    And I fill in select2 input ".form-type-select" with "Open User" and select "User"
    And I wait for "3" seconds
    And I should see the button "Cancel"
    And I press "Save"

    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # SM can view hidden fields, check if it works properly for him.
    Given I am logged in as "sm"
    And I am on "/all-groups"
    And I click "Test open group"
    And I click "Manage members"
    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"

  Scenario: Successfully show Nick Name only
    # Globally enable the Nickname field and disable the Firstname, Lastname
    # fields access.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name--3"
    And I click the element with css selector "#edit-fields-field-profile-last-name--3"
    And I click the element with css selector "#edit-fields-field-profile-nick-name"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Check the profile of myself, and I should see the Nickname.
    Given I am logged in as "user_2"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Hide my name"

    # Check my topic, and I should see the Nickname of the author.
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in the following:
      | Title | Ressinel's Topic |
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text."
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Ressinel's Topic has been created."
    Then I should see "Hide my name"

    # Check the profile of someone else, and I should see the Nickname or
    # default username if Nickname is empty.
    And I am on the profile of "user_1"
    And I click "Information"
    And I should not see "Open User"
    But I should see "user_1"

    # Check the topic of someone else, and I should see the Nickname of the
    # author or default username if Nickname is empty.
    Given I am logged in as "user_1"
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Hide my name"

    # We provide the ability to edit access to the Firstname, Lastname, and
    # Nickname fields for each user separately.
    Given I am logged in as an "administrator"
    And I am on "admin/config/people/social-profile"
    And I click the element with css selector "#edit-fields-field-profile-first-name--2"
    And I click the element with css selector "#edit-fields-field-profile-last-name--2"
    And I click the element with css selector "#edit-fields-field-profile-nick-name--2"
    And I press "Save configuration"
    Then I wait for the batch job to finish

    # Enable the Nickname field and disable the Firstname, Lastname fields
    # access on my profile.
    Given I am logged in as "user_2"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Settings"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-first-name-0]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-last-name-0]"
    And I click the element with css selector "label[for=edit-profile-privacy-fields-field-profile-nick-name-1]"
    And I press "Save"

    # Check the profile of myself, and I should see the Nickname or default
    # username if Nickname is empty.
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "My profile"
    Then I should see "Hide my name"

    # Check my topic, and I should see the Nickname of the author or default
    # username if Nickname is empty.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should see "Hide my name"

    # Check the profile by someone else, and I should see the Nickname or
    # default username if Nickname is empty.
    Given I am logged in as "user_1"
    Then I am on the profile of "user_2"
    And I click "Information"
    And I should not see "Secretive Person"
    But I should see "Hide my name"

    # Check the topic of someone else, and I should see the Nickname of the
    # author or default username if Nickname is empty.
    When I am on "all-topics"
    Then I should see "Ressinel's Topic"
    And I click "Ressinel's Topic"
    Then I should not see "Secretive Person"
    But I should see "Hide my name"

    # Check sorting members in group.
    # First of all we need create a group.
    And I am on "group/add"
    And I press "Continue"
    When I fill in "Title" with "Test open group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Description text"
    And I press "Save"
    And I should see "Test open group" in the "Main content"
    And I should see "Test open group" in the "Hero block"

    # Adding members to a group.
    And I click "Manage members"
    Then I should see "Add members"
    When I click the group member dropdown
    And I click "Add directly"
    And I fill in select2 input ".form-type-select" with "Hide my name" and select "Hide my name"
    And I wait for "3" seconds
    And I should see the button "Cancel"
    And I press "Save"

    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # SM can view hidden fields, check if it works properly for him.
    Given I am logged in as "sm"
    And I am on "/all-groups"
    And I click "Test open group"
    And I click "Manage members"
    # Sorting members by ASC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # Sorting members by DESC and checking if it works properly.
    And I click the element with css selector "a[title='sort by Member']"
    And I should see "Open User" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:first-of-type td.views-field-profile-entity-sortable" element
    And I should see "Hide my name" in the ".view-display-id-page_group_manage_members .card__block--table tbody tr:last-of-type td.views-field-profile-entity-sortable" element

    # TODO: This should happen automatically see: https://github.com/goalgorilla/open_social/pull/1306
    And I disable the module "social_profile_fields"
    And I disable the module "social_profile_privacy"
