@api @javascript
Feature: Activity Stream Filter on Dashboard
  Benefit: Filter activity stream content by vocabulary tags on dashboard pages
  Role: As a Site Manager
  Goal/Desire: I want to configure activity stream blocks on dashboards to filter content by vocabulary tags

  Scenario: Filter activity stream content by tags on dashboard
    Given I enable the module "social_dashboard"
    And users:
      | name        | status | pass        | roles      |
      | CreateUser  | 1      | CreateUser  | verified   |
      | SeeUser     | 1      | SeeUser     | verified   |

    # "News" is a default topic type created by social_topic_install(), no
    # need to create it here - doing so would create an ambiguous duplicate.

    # Clear cache to ensure the new field and permissions are available
    And the cache has been cleared

    # Create content with the tag programmatically
    And topics:
      | author     | title                  | body                                    | field_content_visibility | field_topic_type |
      | CreateUser | Test Topic with Tag    | This is a test topic with a tag         | public                   | News              |
    And I wait for the queue to be empty

    # Setup activity filter settings
    And I am logged in as an "sitemanager"
    And I go to "admin/config/opensocial/activity-filter-settings"
    And I check the box "edit-vocabulary-topic-types"
    And I press "Save configuration"

    # Create a dashboard
    And I go to "node/add/dashboard"
    And I fill in "edit-title-0-value" with "Test Dashboard with Filter"
    And I press "Create dashboard"
    And I should see " Dashboard Test Dashboard with Filter has been created."
    And I should see "Edit layout for Test Dashboard with Filter"

    # Add Activity Stream: Explore Stream Block
    And I click "Add block "
    And I wait for AJAX to finish
    And I click "Activity Stream: Explore Stream Block"
    And I wait for AJAX to finish

    # Configure the block with vocabulary and tag
    And I select "topic_types" from "Vocabulary"
    And I wait for AJAX to finish
    And I select "News" from "Tags"
    And I press "Add block"
    And I wait for AJAX to finish

    # Save the dashboard layout
    And I press "Save"
    And I should see "The layout override has been saved."

    # Verify that the content is displayed on the dashboard
    And I should see "Test Topic with Tag"

    # Verify as another user - navigate to the dashboard
    And I am logged in as "SeeUser"
    And I open the "dashboard" node with title "Test Dashboard with Filter"
    And I should see "Test Topic with Tag"

