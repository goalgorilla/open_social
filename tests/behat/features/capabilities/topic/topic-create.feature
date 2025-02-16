@api @javascript
Feature: Create Topic
  Benefit: In order to share knowledge with people
  Role: As a Verified
  Goal/desire: I want to create Topics

  Scenario: Successfully create topic
    Given I am logged in as an "verified"

    When I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    And I fill in "Title" with "This is a test topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I check the box "News"
#    And I attach the file "/files/humans.txt" to "Attachments"
#    And I wait for AJAX to finish
    And I press "Create topic"

    Then I should see "Topic This is a test topic has been created."
    And I should see "This is a test topic" in the "Hero block"
    And I should see "News"
    And I should see "Body description text" in the "Main content"
#    And I should see "humans.txt"
    And I should not see "Enrollments"

  Scenario: Quick edit topic
    Given I am logged in as a user with the verified role
    And topics authored by current user:
      | title                | body                  | field_content_visibility | field_topic_type | langcode    | status |
      | This is a test topic | Body description text | public                   | News             | en          | 1      |
    And I am viewing the topic "This is a test topic"

    When I click "Edit content"
    And I fill in "Title" with "This is a test topic - edit"
    And I press "Save"

    Then I should see "Topic This is a test topic - edit has been updated"
    And I should see "This is a test topic - edit" in the "Hero block"

  Scenario: LU should not be able to create topic through their user profile
    Given I disable that the registered users to be verified immediately
    And I am logged in as an "authenticated user"

    When I am on "user"

    Then I should not see the link "Topics"

  Scenario: LU should not be able to create topic through the add topic path
    Given I disable that the registered users to be verified immediately
    And I am logged in as an "authenticated user"

    When I am on "node/add/topic"

    Then I should be denied access
