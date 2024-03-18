@disabled @api @topic @stability @perfect @critical @DS-1933 @stability-4 @topic-download-count
Feature: Download Topic
  Benefit: In order to see which files are popular
  Role: As a Verified
  Goal/desire: I want to see number of downloads for files attached to nodes

  Scenario: Track downloads of files attached to nodes
    Given I enable the module "social_download_count"
    And I am logged in as an "verified"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a test topic"
    When I fill in the following:
      | Title | This is a test topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I check the box "News"
    And I attach the file "/files/humans.txt" to "Attachments"
    And I wait for AJAX to finish
    And I press "Create topic"
    And I should see "Topic This is a test topic has been created."
    And I should see "This is a test topic" in the "Hero block"
    And I should see "News"
    And I should see "Body description text" in the "Main content"
    And I click "Open or download file"
    Given I am logged in as an "verified"
    And I am on the homepage
    And I click "This is a test topic"
    And I should see text matching "humans(| \d+).txt"
    And I should see "1 download"
