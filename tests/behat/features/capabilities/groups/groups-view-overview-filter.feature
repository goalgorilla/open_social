@api @javascript @group @group-overview
Feature: All group overview filters

  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: As user I can filter on the available group types on the group overview
    Given I am an anonymous user

    When I am viewing the groups overview

    Then the "Group type" select field should not contain the following options:
      | options         |
      | Secret group    |
      | Community group |
    And the "Group type" select field should contain the following options:
      | options         |
      | Flexible group  |
      | Public group    |
      | - Any -         |

#  @TODO when Flexible groups is our only group type this should work. For now it doesnt, as we have public and flexible
#  as group types enabled by default. In order to create a setup step for this test to run we would either need to
#  uninstall a group type, or remove permissions for AN / outsiders to not be able to view published group of type as
#  group permission. This doesn't help us in our confidence to do the group migration, so we can revisit this later.
#
#  Scenario: As user I can not filter on group types on the group overview if there is only one group type
#    Given I am an anonymous user
#
#    When I am viewing the groups overview
#
#    Then I should not see "Group type" in the "Sidebar second"

  Scenario: As user I can not filter on the field group type if there are no types added
    Given I am an anonymous user
    And I set the configuration item "social_group.settings" with key "social_group_type_required" to TRUE

    When I am viewing the groups overview

    Then I should not see "Type" in the "Sidebar second"

  Scenario: As user I can not filter on the field group type if the setting is disabled even if there are options
    Given I am an anonymous user
    And I set the configuration item "social_group.settings" with key "social_group_type_required" to FALSE
    And "group_type" terms:
      | name |
      | Local Group |

    When I am viewing the groups overview

    Then I should not see "Type" in the "Sidebar second"

  Scenario: As user I can filter on the field group type if flexible groups is selected as filter option
    Given I am an anonymous user
    And I set the configuration item "social_group.settings" with key "social_group_type_required" to TRUE
    And "group_type" terms:
      | name |
      | Local Group |

    When I am viewing the groups overview
    And I select "Flexible group" from "Group type"

    Then I should see "Type" in the "Sidebar second"

  Scenario: As user I can filter on the field group type and the right group(s) are shown
    Given I am an anonymous user
    And I set the configuration item "social_group.settings" with key "social_group_type_required" to TRUE
    And "group_type" terms:
      | name |
      | Local Group |
    And groups with non-anonymous owner:
      | label                   | field_group_description   | field_flexible_group_visibility | type            | created  | field_group_type |
      | This is a local group   | This is a local group     | public                          | flexible_group  | 01/01/01 | Local Group      |
      | This is not a local one | Just an ordinary on       | public                          | flexible_group  | 01/01/01 |                  |

    When I am viewing the groups overview
    And I select "Flexible group" from "Group type"
    And I select "Local Group" from "Type"
    And I press "Filter"

    Then I should see "This is a local group"
    And I should not see "This is not a local one"
