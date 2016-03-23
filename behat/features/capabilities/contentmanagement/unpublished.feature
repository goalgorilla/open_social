@wip @api @topic @stability @perfect @critical @DS-486
Feature: Un/publish a node
  Benefit: In order to make drafts
  Role: as a LU
  Goal/desire: I want to un/publish

  Scenario: Successfully create unpublished topic
    Given I am logged in as an "authenticated user"
      And I am on "node/add/topic"
    When I fill in "Title" with "This is a test topic"
      And I fill in the following:
        | Title | This is a test topic |
      And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
      And I click radio button "Discussion"
      And I show hidden buttons
      And I press "Save as unpublished"
    Then I should see "Topic This is a test topic has been created."
      And I should see the heading "This is a test topic" in the "Page title block"
      And I should see "Discussion" in the "Page title block"
      And I should see "Body description text" in the "Main content"

    When I am on "user"
      And I click "Topics"
    Then I should see "This is a test topic"
      And I should see "Discussion"

    When I click "This is a test topic"
      And I click "Edit"
      And I show hidden buttons
      And I press "Save and publish"
    Then I should see the heading "This is a test topic"
      And I should see "Discussion"
      And I should see "Body description text"