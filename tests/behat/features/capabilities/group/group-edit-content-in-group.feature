@api @group @DS-4357 @stability @stability-3
Feature: Move content after creation
  Benefit: Have full control over where I place my content
  Role: As a LU+
  Goal/desire: Being able to move content during and after creation

  Scenario: Successfully add new content with the group selector

    Given users:
      | name  | pass | mail                     | status |
      | harry | 1234 | harry@example.com | 1      |
      | sally | 1234 | sally@example.com | 1      |
    Given groups:
      | title    | description     | author   | type        | language |
      | Motorboats | Vroem vroem..  | sally | closed_group  | en |
      | Kayaking | Kayaking in NY | harry | open_group  | en |
    # Create a new topic
    When I am logged in as "harry"
    And I am on "node/add/topic"
    And I select group "Kayaking"
    And I wait for AJAX to finish
    Then I should see "Changing the group may have impact on the visibility settings."
    And I fill in "Title" with "I love this sport"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Do you to?"
    And I click radio button "Discussion"
    And I press "Save"
    And I should see "Kayaking" in the "Main content"

    # Edit topic
    When I click "Edit content"
    Then I should see "Moving content after creation function has been disabled. In order to move this content, please contact a site manager."
