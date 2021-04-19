@api @comment @stability @YANG-4950 @stability-2 @comment-ajax
Feature: See Comments
  Benefit: In order to interact with people on the platform
  Role: As a LU
  Goal/desire: I want to see comments

  Scenario: Successfully see comments

    Given I enable the module "social_ajax_comments"
    Given users:
      | name       |
      | Behat User |
    Given "15" topics with title "Behat Topic [id]" by "Behat User"
    Given "60" comments with text "Behat Comment [id]" for "Behat Topic 15"

    When I am logged in as "Behat User"
    And I click "Behat Topic 15"
    Then I should see the text "Behat Comment 1"

    When I click "next"
    And I wait for AJAX to finish
    Then I should see the text "Behat Comment 1"

    When I click "Behat User"
    And I click "My topics"
    Then I should see the link "Behat Topic 15"
    And I should not see the link "Behat Topic 5"

    When I click "next"
    Then I should not see the link "Behat Topic 15"
    And I should see the link "Behat Topic 5"
    And I disable the module "social_ajax_comments"
    And I disable the module "ajax_comments"
