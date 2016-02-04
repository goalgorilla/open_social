@api @topic @stability
Feature: Create Topic
  Benefit: In order to share knowledge with people
  Role: As a LU
  Goal/desire: I want to create Topics

  Scenario: Successfully create topic
    Given I am logged in as an "authenticated user"
    And I am on "node/add/topic"
    When I fill in the following:
         | Title | This is a test topic |
         | Description | Body description text. |
    And I select the radio button "Discussion"
    And I press "Save"
    Then I should see "Topic This is a test topic has been created."
