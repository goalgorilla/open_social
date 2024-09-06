@api
Feature: Group Managers block is accessible to users with the VU+ role
  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario: Only verified user or with higher role should see "Group Managers" block on the "About" group page
    Given users:
      | name          | status | roles       |
      | Manager       | 1      | sitemanager |
      | Verified      | 1      | verified    |

    And groups:
      | label         | field_group_description | field_flexible_group_visibility |field_group_allowed_join_method | author  | type            | created  |
      | Public Group  | My Description          | public                          | direct                         | Manager | flexible_group  | 01/01/01 |
      | Request Group | My Description          | public                          | request                        | Manager | flexible_group  | 01/01/01 |
      | Invite Group  | My Description          | public                          | added                          | Manager | flexible_group  | 01/01/01 |

    When I am an anonymous user
    And I am viewing the about page of group "Public group"
    And I should not see "Group managers"

    And I am viewing the about page of group "Request group"
    And I should not see "Group managers"

    And I am viewing the about page of group "Invite group"
    And I should not see "Group managers"

    And I am logged in as an "authenticated user"
    And I am viewing the about page of group "Public group"
    And I should not see "Group managers"

    And I am viewing the about page of group "Request group"
    And I should not see "Group managers"

    And I am viewing the about page of group "Invite group"
    And I should not see "Group managers"

    And I am logged in as Verified
    And I am viewing the about page of group "Public group"
    And I should see "Group managers"

    And I am viewing the about page of group "Request group"
    And I should see "Group managers"

    And I am viewing the about page of group "Invite group"
    And I should see "Group managers"

    And I am logged in as Manager
    And I am viewing the about page of group "Public group"
    And I should see "Group managers"

    And I am viewing the about page of group "Request group"
    And I should see "Group managers"

    And I am viewing the about page of group "Invite group"
    And I should see "Group managers"
