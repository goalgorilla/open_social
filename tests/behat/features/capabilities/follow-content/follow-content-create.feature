@api @follow-content @stability @stability-3
Feature: Create Follow Content
  Benefit: So I can discover new content on the platform
  Role: As a LU
  Goal/desire: I want to create follow content

  Scenario: Successfully create follow content
    Given users:
      | name           | mail                      | status |
      | Behat User One | beat_user_one@example.com | 1      |
      | Behat User Two | beat_user_two@example.com | 1      |

    Given I am logged in as "Behat User One"
    When I click "Profile of Behat User One"
    And I click "Following"
    Then I should be on "/following"
    And I should see the heading "Content and posts I follow" in the "Page title block" region
    And I should see "Filter" in the ".region--complementary-top #block-following-filter .complementary-title" element
    And I should see "Type" in the ".region--complementary-top #block-following-filter .form-type-select.form-item-type .control-label" element
    And I should see "Apply" in the ".region--complementary-top #block-following-filter .form-actions" element
    And I should not see "Reset" in the ".region--complementary-top #block-following-filter .form-actions" element
    When I select "Post" from "Type"
    And I press "Apply"
    Then I should be on "/following?type=post"
    And I should see "Reset" in the ".region--complementary-top #block-following-filter .form-actions" element
    When I click "Reset"
    Then I should be on "/following"
    And I should not see "Reset" in the ".region--complementary-top #block-following-filter .form-actions" element
    When I am on the homepage
    And I fill in "Say something to the Community" with "Behat post"
    And I press "Post"
    And I am logged in as "Behat User Two"
    And I click "Behat User One"
    And I fill in "Write a comment..." with "Behat comment"
    And I press "Comment"
    And I am on "/following"
    Then I should see the link "Behat post"
    And I should see the text "Post"
    And I should see the link "Unfollow post"
