@api @group @DS-4357 @stability @stability-3 @group-edit-content-in-group
Feature: Move content after creation
  Benefit: Have full control over where I place my content
  Role: As a LU+
  Goal/desire: Being able to move content during and after creation

  Scenario: Successfully add new content with the group selector

    Given users:
      | name  | pass | mail              | status | roles         |
      | harry | 1234 | harry@example.com | 1      |               |
      | sally | 1234 | sally@example.com | 1      |               |
      | smith | 1234 | sm@example.com    | 1      |  sitemanager  |
    Given groups:
      | title      | description    | author | type       | language |
      | Motorboats | Vroem vroem..  | sally  | open_group | en       |
      | Kayaking   | Kayaking in NY | harry  | open_group | en       |
    # Create a new topic
    When I am logged in as "harry"
    And I am on "/all-groups"
    And I click "Motorboats"
    And I click "Join"
    And I press "Join group"
    And I am on "node/add/topic"
    And I click radio button "Discussion"
    And I fill in "Title" with "I love this sport"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Do you to?"
    And I select group "Kayaking"
    And I wait for AJAX to finish
    Then I should see "Changing the group may have impact on the visibility settings and may cause author/co-authors to lose access."
    And I press "Save"
    And I should see "Kayaking"
    And I wait for "2" seconds

    # Edit topic
    When I click "Edit content"
    Then I should see "Moving content after creation function has been disabled. In order to move this content, please contact a site manager."

    # Edit topic as SM to move in a new group.
    When I am logged in as "smith"
    And I am on "/all-topics"
    And I should see "I love this sport"
    And I click "I love this sport"
    Then I should see "Kayaking"
    And I click "Edit content"
    And I select group "Motorboats"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Motorboats"

    When I am logged in as "harry"
    And I am on the stream of group "Motorboats"
    Then I should see "harry created a topic in Motorboats"
    And I should see "I love this sport"
    And I am on the stream of group "Kayaking"
    And I should not see "I love this sport"

    # Edit topic as SM to move outside of a group in community.
    When I am logged in as "smith"
    And I am on "/all-topics"
    And I should see "I love this sport"
    And I click "I love this sport"
    And I empty the queue
    And I click "Edit content"
    And I select group "- None -"
    And I wait for AJAX to finish
    And I press "Save"
    And I run cron
    Then I should not see "Motorboats"
    And I should not see "Kayaking"

    When I am logged in as "harry"
    And I am on the stream of group "Motorboats"
    Then I should not see "harry created a topic in Motorboats"
    And I should not see "I love this sport"
    And I am on the stream of group "Kayaking"
    And I should not see "I love this sport"
    And I click "Home"
    Then I should see "harry created a topic"
    And I should see "I love this sport"

