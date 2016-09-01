@api @share @perfect @critical @DS-1829
Feature: Social Sharing
  Benefit: In order to share my page with external people
  Role: As a LU
  Goal/desire: I want to share my public content

  Scenario: Successfully create topic visible for community and public
    Given I am logged in as an "authenticated user"
    And I am on "node/add/topic"
    When I fill in "Title" with "This is a topic for community"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic for community"
    And I click radio button "Community - visible only to logged in members"
    And I click radio button "Discussion"
    And I press "Save and publish"
    Then I should see "Topic This is a topic for community has been created."
    And I should not see "Facebook"
    And I am on "node/add/topic"
    When I fill in "Title" with "This is a topic for public"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic for public"
    And I click radio button "Public - visible to everyone including people who are not a member"
    And I click radio button "Discussion"
    And I press "Save and publish"
    Then I should see "Topic This is a topic for public has been created."
    And I should see "Facebook"

    # Now visit the pages as anonymous user.
    When I logout
    Given I open the "topic" node with title "This is a topic for public"
    Then I should see "This is a topic for public"
    And I should see "Facebook"
    Given I open the "topic" node with title "This is a topic for community"
    Then I should not see "This is a topic for community"
    And I should see "Access denied."
