@wip @api @topic @stability @perfect @critical @DS-341
Feature: Create Topic
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Topics

  Scenario: Successfully create topic
    Given I am logged in as an "authenticated user"
    And I am on "node/add/topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Save"
    Then I should see "Topic This is a test topic has been created."
    And I should see the heading "This is a test topic" in the "Page title block"
    And I should see "Discussion" in the "Page title block"
    And I should see "Body description text" in the "Main content"
