@api @javascript
Feature: All group overview filters

  Background:
    Given I enable the module "social_group_flexible_group"

  Scenario: As user I can not filter on the field group type if there are no types added
    Given I am an anonymous user
    And I set the configuration item "social_group.settings" with key "social_group_type_required" to TRUE

    When I am viewing the groups overview

    Then I should not see "Type" in the "Sidebar second"

  Scenario: As user I can not filter on the field group type if the setting is disabled even if there are options
    Given I am an anonymous user
    And I disable group type settings
    And "group_type" terms:
      | name |
      | Local Group |

    When I am viewing the groups overview

    Then I should not see "Type" in the "Sidebar second"

  Scenario: As user I can filter on the field group type if flexible groups is selected as filter option
    Given I am an anonymous user
    And I enable group type settings
    And "group_type" terms:
      | name |
      | Local Group |

    When I am viewing the groups overview

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
    And I select "Local Group" from "Type"
    And I press "Filter"

    Then I should see "This is a local group"
    And I should not see "This is not a local one"
