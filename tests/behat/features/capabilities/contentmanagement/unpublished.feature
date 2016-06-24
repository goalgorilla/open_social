@api @topic @stability @perfect @critical @DS-486
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
      And I press the "Toggle Dropdown" button
      And I click "Save as unpublished"
    Then I should see "Topic This is a test topic has been created."
      And I should see "This is a test topic" in the "Hero block"
      And I should see "Discussion" in the "Hero block"
      And I should see "Body description text" in the "Main content"

    When I click "Edit content"
      And I press the "Toggle Dropdown" button
      And I click "Save and publish"
      Then I should see "This is a test topic" in the "Hero block"

    When I am on "user"
      And I click "Topics"
      Then I should see "This is a test topic"
      And I should see "Discussion"
