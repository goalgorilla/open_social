@api @group @DS-5504 @javascript @stability @stability-3 @group-edit-group-type
Feature: Edit group type after creation
  Benefit: Have full control over groups and group content types
  Role: As a CM+
  Goal/desire: Being able to edit existing groups types after creation

  Scenario: Site Manager changes a group with content in it and the content visibility should update
    Given groups with non-anonymous owner:
      | label     | field_group_description | type         | langcode |
      | Nescafe   | Coffee time!!!          | closed_group | en       |
    And "topic_type" terms:
      | name |
      | Blog |
    And topics with non-anonymous author:
      | title     | body      | group   | field_content_visibility | langcode | field_topic_type |
      | Nespresso | What else | Nescafe | group                    | en       | Blog             |

    # Assert initial visibility is correct
    Given I am logged in as a user with the verified role
    When I am viewing the topic "Nespresso"
    Then I should be denied access

    # @When I change the group visibility for "Nescafe" from "closed" to "public" using the group edit form
    Given I am logged in as a user with the sitemanager role

    When I am editing the group Nescafe
    And I expand the Settings section
    And I should see checked the box "Closed group"
    And I click radio button "Public group"
    And I should see "Please note that changing the group type will also change the visibility of the group content and the way users can join the group"
    And I press "Save"
    And I wait for the batch job to finish

    Then I should be viewing the group Nescafe

    # Check that content visibility changed.
    Given I am logged in as a user with the verified role
    When I am viewing the topic "Nespresso"
    Then I should see "Nespresso"
