@api @group @DS-3801 @DS-3776 @DS-3976 @DS-4211 @stability @stability-1 @group-access-roles
Feature: Group management for CM+
  Benefit: As a CM+ I want to have full control over groups
  Role: As a CM+
  Goal/desire: Control groups and their content

  Scenario Outline: As a outsider with the role CM+ I should be able to see and manage content from a closed group
    Given groups with non-anonymous owner:
      | label             | type         | field_group_description |
      | Test closed group | closed_group | Description text        |
    And topics with non-anonymous author:
      | title                   | group             | field_topic_type | body                  | field_content_visibility | langcode |
      | Test closed group topic | Test closed group | News             | Body description text | group                    | en       |
    And I am logged in as a user with the <role> role

    Then I open and check the access of content in group "Test closed group" and I expect access "allowed"

    When I am on "stream"
    Then I should see "Test closed group topic"

    When I am on "/all-topics"
    Then I should see "Test closed group topic"

    Examples:
      | role           |
      | contentmanager |
      | sitemanager    |

  Scenario Outline: As a CM+ I want to join a group regardless of join method
    Given groups with non-anonymous owner:
      | label             | type         | field_group_description | allow_request   |
      | Test closed group | closed_group | Description text        | <allow_request> |
    And I am logged in as a user with the <role> role

    When I am viewing the group "Test closed group"
    And I click Join
    And I press "Join group"

    Then I should see the button Joined

    Examples:
      | role           | allow_request |
      # @todo The "request to join" functionality causes CM/SM no longer to be able to join closed groups directly, this is a bug.
      # https://www.drupal.org/project/social/issues/3314736
      # | contentmanager | 1             |
      | contentmanager | 0             |
      # | sitemanager    | 1             |
      | sitemanager    | 0             |

  Scenario Outline: As a closed group member with the role CM+ I should be able to see and manage content from a closed group
    Given groups with non-anonymous owner:
      | label             | type         | field_group_description |
      | Test closed group | closed_group | Description text        |
    And topics with non-anonymous author:
      | title                   | group             | field_topic_type | body                  | field_content_visibility | langcode |
      | Test closed group topic | Test closed group | News             | Body description text | group                    | en       |
    And I am logged in as a user with the <role> role
    And I am a member of "Test closed group"

    Then I open and check the access of content in group "Test closed group" and I expect access "allowed"

    Examples:
      | role           |
      | contentmanager |
      | sitemanager    |

  Scenario Template:  Add a user with CM+ role (that has 'manage all groups' permission) on the Manage members tab so he gets the group admin role.
    Given I am logged in as a user with the verified role
    And groups owned by current user:
      | label             | type         | field_group_description |
      | Test closed group | closed_group | Description text        |
    And users:
      | name       | roles  |
      | Jane Deere | <role> |

    When I am on the stream of group "Test closed group"
    And I click "Manage members"
    And I click the group member dropdown
    And I click "Add directly"
    And I fill in select2 input ".form-type-select" with "Jane Deere" and select "Jane Deere"
    And I press "Save"
    And I click "Manage members"

    Then I should see "Group Admin"

    Examples:
      | role           |
      | contentmanager |
      | sitemanager    |
