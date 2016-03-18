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
    And I click radio button "Discussion"
#    And I attach the file "16787988882_56b85ac11e_k.jpg" to "Image"
#    And I wait for AJAX to finish
    And I press "Save"
    # Then I should see "Topic This is a test topic has been created."
    And I should see the heading "This is a test topic" in the "Page title block"
    And I should see "Discussion" in the "Page title block"
    And I should see "Body description text" in the "Main content"
