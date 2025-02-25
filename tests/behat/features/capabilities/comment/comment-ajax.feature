@api
Feature: See Comments
  Benefit: In order to interact with people on the platform
  Role: As a Verified
  Goal/desire: I want to see comments

  Scenario: Successfully see comments

    Given I enable the module "social_ajax_comments"
    And users:
      | name       | roles    |
      | Behat User | verified |
    And "15" topics with title "Behat Topic [id]" by "Behat User"
    And "60" comments with text "Behat Comment [id]" for "Behat Topic 15"

    And I am logged in as "Behat User"
    And I am at "/all-topics"
    And I click "Behat Topic 15"
    And I should see the text "Behat Comment 1"
    And I should not see the text "Behat Comment 51"

    And I click "next"
    And I wait for AJAX to finish
    And I should not see the text "Behat Comment 1"
    And I should see the text "Behat Comment 51"

    And I click "Behat User"
    And I click "My topics"
    And I should see the link "Behat Topic 15"
    And I should not see the link "Behat Topic 5"

    And I click "next"
    And I should not see the link "Behat Topic 15"
    And I should see the link "Behat Topic 5"
    And I disable the module "social_ajax_comments"
    And I disable the module "ajax_comments"
