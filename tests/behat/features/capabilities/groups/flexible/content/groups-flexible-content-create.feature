@api @javascript @flexible-groups @flexible-groups-content
Feature: Create content in flexible groups

  Background:
    Given I enable the module "social_group_flexible_group"
    And I disable that the registered users to be verified immediately

  Scenario Outline: Anonymous users can not create content in public groups
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And I am an anonymous user

    When I am viewing the <group_page> page of group "Test group"

    Then I should see "Test group"
    And I should not see the link <content_type_create_button>

    Examples:
      | visibility | group_page | content_type_create_button |
      | public     | topics     | "Create Topic"             |
      | public     | events     | "Create Event"             |

  Scenario Outline: Non-platform manager members can not create content in any groups they can see if they're not a member
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And I am logged in as a user with the <role> role

    When I am viewing the <group_page> page of group "Test group"

    Then I should see "Test group"
    And I should not see the link <content_type_create_button>

    Examples:
      | role           | visibility | group_page | content_type_create_button |
      | authenticated  | public     | events     | "Create Event"             |
      | verified       | public     | topics     | "Create Topic"             |
      | verified       | public     | events     | "Create Event"             |
      | verified       | community  | topics     | "Create Topic"             |
      | verified       | community  | events     | "Create Event"             |

    Scenario Outline: Platform managers can create content in all groups they can see if they're not a member
      Given groups with non-anonymous owner:
        | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
        | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
      And I am logged in as a user with the <role> role

      When I am viewing the <group_page> page of group "Test group"

      Then I should see "Test group"
      And I should see the link <content_type_create_button>

      Examples:
        | role           | visibility | group_page | content_type_create_button |
        | contentmanager | public     | topics     | "Create Topic"             |
        | contentmanager | public     | events     | "Create Event"             |
        | contentmanager | community  | topics     | "Create Topic"             |
        | contentmanager | community  | events     | "Create Event"             |
        | contentmanager | members    | topics     | "Create Topic"             |
        | contentmanager | members    | events     | "Create Event"             |
        | sitemanager    | public     | topics     | "Create Topic"             |
        | sitemanager    | public     | events     | "Create Event"             |
        | sitemanager    | community  | topics     | "Create Topic"             |
        | sitemanager    | community  | events     | "Create Event"             |
        | sitemanager    | members    | topics     | "Create Topic"             |
        | sitemanager    | members    | events     | "Create Event"             |

  Scenario Outline: Non-platform manager members can not select a group on a content creation form for a group they can see if they're not a member
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And I am logged in as a user with the <role> role

    When I view the <content_type> creation page

    Then I should not be able to select the group "Test group"

    # This does not contain the authenticated role since it can not create content.
    Examples:
      | role           | visibility | content_type |
      | verified       | public     | topic        |
      | verified       | public     | event        |
      | verified       | community  | topic        |
      | verified       | community  | event        |

  Scenario Outline: Platform managers can select a group on a content creation form for all groups they can see if they're not a member
    Given groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility |
      | Test group | Secret visibility       | flexible_group | en       | <visibility>                    |
    And I am logged in as a user with the <role> role

    When I view the <content_type> creation page

    Then I should be able to select the group "Test group"

    Examples:
      | role           | visibility | content_type |
      | contentmanager | public     | topic        |
      | contentmanager | public     | event        |
      | contentmanager | community  | topic        |
      | contentmanager | community  | event        |
      | contentmanager | members    | topic        |
      | contentmanager | members    | event        |
      | sitemanager    | public     | topic        |
      | sitemanager    | public     | event        |
      | sitemanager    | community  | topic        |
      | sitemanager    | community  | event        |
      | sitemanager    | members    | topic        |
      | sitemanager    | members    | event        |
