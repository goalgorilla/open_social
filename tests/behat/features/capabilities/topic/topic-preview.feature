@api @topic @stability @perfect @critical @DS-1544
Feature: Preview Topic
  Benefit: In order to see how the page would look before saving changes
  Role: As a LU
  Goal/desire: I want to be able to preview Topics during editing

  Scenario: Successfully preview topic
    Given I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I attach the file "/files/humans.txt" to "Add a new file"
    And I press "Preview"
    And I wait for AJAX to finish
    And I should see "This is a test topic"

    When I select "Activity" from "View mode"
    And I wait for AJAX to finish
    And I should see "This is a test topic"

    When I select "Activity comment" from "View mode"
    And I wait for AJAX to finish
    And I should see "This is a test topic"

    When I select "Small teaser" from "View mode"
    And I wait for AJAX to finish
    And I should see "This is a test topic"

    When I select "Teaser" from "View mode"
    And I wait for AJAX to finish
    And I should see "This is a test topic"
