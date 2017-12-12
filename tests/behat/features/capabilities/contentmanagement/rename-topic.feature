@api @rename-topic @DS-4344
Feature: Rename Topic
  Benefit: Customization for the definition of topics.
  Role: SM
  Goal/desire: Provide a option for SM to customize the word topic.

  # Enable and rename topic.
  Scenario: Successfully enable, rename and save the settings.
    Given I enable the module "social_renamer"
    Given I am logged in as an "sitemanager"
    When I am on "/admin/dashboard"
    And I click "Rename settings"
    Then I should be on "/admin/config/opensocial/renamer"
    And I should see "Social Renamer"
    When I check the box "Rename topic"
    And I fill in the following:
      | rename_topic_topic    | Project    |
      | rename_topic_a_topic  | A Project  |
      | rename_topic_topics   | Projects   |
    Then I press "Save configuration"
    And I should see the following success messages:
      | The configuration options have been saved. |

  # Check on the topic names on the platform
  Scenario: See the renamed topic word reflected in the platform
    Given I am logged in as an "authenticated user"
    When I am on the homepage
    Then I should see "Newest projects in the community"

    # Check all topics overview
    When I am on the homepage
    And I click the xth "3" element with the css ".dropdown-toggle"
    Then I should see the link "All projects"
    And I click "All projects"
    Then I should see "All projects"

    # Check create new topic
    When I am on the homepage
    And I click the xth "0" element with the css ".dropdown-toggle"
    Then I should see the link "New project"
    And I click "New project"
    Then I should see "Create Project"

    # Check local navigation topics
    When I click the xth "2" element with the css ".dropdown-toggle"
    And I break
    Then I should see "My projects"
    When I click "My profile"
    Then I should see the link "Projects"
