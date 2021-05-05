@api @contentmanagement @stability @perfect @critical @DS-158 @stability-1 @node-visibility
Feature: Visibility
  Benefit: In order to control the distribution of information and to secure my privacy
  Role: As a LU
  Goal/desire: I want to set the visibility of content I create

  Scenario: Successfully create topic visible for community and public
    Given I am logged in as an "authenticated user"
    And I am on "node/add/topic"
    When I fill in "Title" with "This is a topic for community"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic for community"
    And I click radio button "Community"
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Topic This is a topic for community has been created."
    And I am on "node/add/topic"
    When I fill in "Title" with "This is a topic for public"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic for public"
    And I click radio button "Public"
    And I click radio button "Discussion"
    And I press "Create topic"
    Then I should see "Topic This is a topic for public has been created."

    # Now visit the pages as anonymous user.
    When I logout
    Given I open the "topic" node with title "This is a topic for public"
    Then I should see "This is a topic for public"
    Given I open the "topic" node with title "This is a topic for community"
    Then I should not see "This is a topic for community"
    And I should see "Access denied."

    # Check default visibility
    Given I set the configuration item "entity_access_by_field.settings" with key "default_visibility" to "public"
    Given topic content:
      | title              | field_topic_type | status |
      | Behat Topic public | Blog             | 1      |
    Given I am on the homepage
    Then I should see "All topics"
    And I should see "Behat Topic public"

    Given I set the configuration item "entity_access_by_field.settings" with key "default_visibility" to "community"
    Given topic content:
      | title                 | field_topic_type | status |
      | Behat Topic community | Blog             | 1      |
    Given I am on the homepage
    Then I should see "All topics"
    And I should not see "Behat Topic community"
