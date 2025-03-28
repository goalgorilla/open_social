@api
Feature: Edit Comment
  Benefit: Correct mistakes I made when adding comments
  Role: As a Verified
  Goal/desire: I want to edit my comments on the platform

  Scenario: Successfully edit comment
    Given I am logged in as an "verified"
    And I am viewing a "topic" with the title "Comment edit topic"

    When I fill in the following:
      | Add a comment | This is my comment |
    And I press "Comment"

    Then I should see "This is my comment" in the "Main content"

    And I click the element with css selector ".comment .comment__actions .dropdown-toggle"
    And I should see the link "Edit"

    And I click "Edit"
    And I fill in the following:
      | Add a comment | This is my edited comment |
    And I press "Submit"

    And I should see "This is my edited comment"
