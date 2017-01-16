@api @topic @stability @perfect @critical @DS-1933
Feature: Download Topic
  Benefit: In order to see which files are popular
  Role: As a LU
  Goal/desire: I want to see number of downloads for files attached to nodes

  Scenario: Track downloads of files attached to nodes
    Given I enable the module "social_download_count"
    And I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I attach the file "/files/humans.txt" to "Add a new file"
    And I press "Save"
    And I should see "Topic This is a test topic has been created."
    And I should see "This is a test topic" in the "Hero block"
    And I should see "Discussion" in the "Main content"
    And I should see "Body description text" in the "Main content"
    And I should not see "Enrollments"
    And I click "humans.txt"
    Given I am logged in as an "authenticated user"
    And I am on the homepage
    And I click "This is a test topic"
    And I should see "humans.txt"
    And I should see "1 download"
