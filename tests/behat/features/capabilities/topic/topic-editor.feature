@api
Feature: Create a Topic with a co-author
  so that other VU as well can edit the topic

  Scenario: Successfully create topic with a co-author
  Background:
    Given I enable the module "social_collaboration"

    And users:
      | name         | mail                      | status | roles    |
      | Editor user  | editor_user@example.com   | 1      | verified |
      | Basic user   | basic_user@example.com    | 1      | verified |

    And I am logged in as a user with the sitemanager role
    
    When I am on "node/add/topic"  
    And I check the box "News"
    And I fill in "Title" with "This is a topic for collaboration"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Collaborate with your ideas"
    And I click the element with css selector "#edit-editors"
    And I select "User" from "field_social_collaboration_editors[0][target_type]"
    And I fill in "edit-field-social-collaboration-editors-0-target-id" with "Editor user"
    And I press "Create topic"
    
    Then I should see "Topic This is a topic for collaboration"
    And I should see "This is a topic for collaboration" in the "Hero block"
    And I should see "News"
    And I should see "Co-authors" in the "#block-socialblue-coauthors-block" element
    And I should see "Editor user"

  Scenario: Successfully edit topic as the co-author
    Given I am logged in as "Editor user"
    
    When I open the "topic" node with title "This is a topic for collaboration"
    And I should see "This is a topic for collaboration"
    And I should see the link "Edit content"
    And I click "Edit content"
    
    Then I should see "Edit Topic This is a topic for collaboration"
    And I check the box "Blog"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "Collaborate with your ideas. Idea 1: VC"
    And I press "Save"
    And I should see "Blog"
    And I should see "Topic This is a topic for collaboration has been updated."
    And I should see "Collaborate with your ideas. Idea 1: VC"

  Scenario: Non co-author VU should not be able to edit the content
    Given I am logged in as "Basic user"
    
    When I open the "topic" node with title "This is a topic for collaboration"
    
    Then I should see "This is a topic for collaboration"
    And I should not see the link "Edit content"

