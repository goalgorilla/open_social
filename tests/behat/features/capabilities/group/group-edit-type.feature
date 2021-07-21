@api @group @DS-5504 @stability @stability-3 @group-edit-group-type
Feature: Edit group type after creation
  Benefit: Have full control over groups and group content types
  Role: As a CM+
  Goal/desire: Being able to edit existing groups types after creation

  Scenario: Successfully add new content with the group selector

    Given users:
      | name        | pass | mail                    | status | roles       |
      | test_user_1 | 1234 | test_user_1@example.com | 1      | verified    |
      | test_user_2 | 1234 | test_user_2@example.com | 1      | sitemanager |
    Given groups:
      | title     | description    | author       | type         | language |
      | Nescafe   | Coffee time!!! | test_user_2  | closed_group | en       |
    Given "topic_types" terms:
      | name      |
      | Blog      |
    Given topic content:
      | title         | field_topic_type | status | field_content_visibility | alias          |
      | Nescafe Topic | Blog             | 1      | group                    | /nescafe-topic |

    # Scenario SM change Group Type with Topic Content in it.
    When I am logged in as "test_user_2"
      And I am on "/nescafe-topic"
    Then I should see "Nescafe Topic"
      And I click "Edit content"
      And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
      And I select group "Nescafe"
      And I wait for AJAX to finish
      And I press "Save"
    Then I should see "Nescafe"

    When I am logged in as "test_user_1"
      And I am on "/nescafe-topic"
    Then I should see "Access Denied"

    When I am logged in as "test_user_2"
      And I am on the stream of group "Nescafe"
    Then I should see "Closed group"
      And I should see "Nescafe"
    When I click "Edit group"
    Then I should see checked the box "Closed group"

    Then I click radio button "Public group This is a public group. Users may join without approval and all content added in this group will be visible to all community members and anonymous users." with the id "edit-group-type-public-group"
      And I wait for AJAX to finish
    Then I should see "Please note that changing the group type will also change the visibility of the group content and the way users can join the group"
      And I press "Save"
      And I wait for the batch job to finish
    Then I should see "Nescafe"

    When I click "Stream" in the "Tabs"
      And I click the post visibility dropdown
    Then I should see "Public"

    When I am logged in as "test_user_1"
      And I am on "/nescafe-topic"
    Then I should see "Nescafe Topic"
