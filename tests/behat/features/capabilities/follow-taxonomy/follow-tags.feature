@api @follow-taxonomy @stability @YANG-5609 @stability-4 @follow-tags
Feature: Follow Tags
  Benefit: Provide follow tag
  Role: As LU
  Goal/Desire: I want to follow tags and receive notifications about content with tags

  @email-spool
  Scenario: Successfully follow added tags
    Given I set the configuration item "system.site" with key "name" to "Open Social"
    And I enable the module "social_tagging"
    And I enable the module "social_follow_tag"
    And "social_tagging" terms:
      | name         | parent     |
      | Category 1   |            |
      | Category 1.1 | Category 1 |
      | Category 1.2 | Category 1 |
      | Category 2   |            |
      | Category 2.1 | Category 2 |
      | Category 2.2 | Category 2 |
    And users:
      | name            | mail                      | status | roles          | field_profile_first_name | field_profile_last_name |
      | follower        | follower@test.user        | 1      |                | Jack                     | Richer                  |
      | content_creator | content.creator@test.user | 1      | contentmanager | Mike                     | Tyson                   |

    # Save tag config to clear form cache.
    Given I am logged in as an "sitemanager"
    And I go to "admin/config/opensocial/tagging-settings"
    And I press "Save configuration"

    # Create topic to work with it later.
    Given I am logged in as "content_creator"
    And I go to "node/add/topic"
    And I click radio button "News"
    And I fill in "Title" with "Simple topic"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic to check update activity"
    And I click radio button "Community"
    And I press "Settings"
    And I set alias as "simple-topic"
    And I press "Create topic"

    Then I should see "Simple topic" in the "Hero block" region
    And I should see "This is a topic to check update activity" in the "Main content"

    # Create topic with tag than user car follow tags.
    Then I go to "node/add/topic"
    And I click radio button "News"
    And I fill in "Title" with "Topic with tags"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic for follow tag feature"
    And I click radio button "Community"
    And I select "Category 1.1" from "Category 1"
    And I additionally select "Category 1" from "Category 1"
    And I additionally select "Category 2" from "Category 2"
    And I additionally select "Category 2.2" from "Category 2"
    And I press "Settings"
    And I set alias as "topic-with-tags"
    And I press "Create topic"

    Then I should see "Topic with tags" in the "Hero block" region
    And I should see "This is a topic for follow tag feature" in the "Main content"
    And I should see the link "Category 1" in the "Sidebar second"
    And I should see the link "Category 1.1" in the "Sidebar second"
    And I should see the link "Category 2" in the "Sidebar second"
    And I should see the link "Category 2.2" in the "Sidebar second"

    # Check if user see topic and all added tags.
    Given I am logged in as "follower"
    And I go to "topic-with-tags"
    And I should see "This is a topic for follow tag feature" in the "Main content"
    And I should see the link "Category 1" in the "Sidebar second"
    And I should see the link "Category 1.1" in the "Sidebar second"
    And I should see the link "Category 2" in the "Sidebar second"
    And I should see the link "Category 2.2" in the "Sidebar second"
    And I wait for the queue to be empty

    # Follow tags
    When I click "Category 1"
    And I click the element with css selector ".popup-info.open a.follow-term-link"
    And I wait for AJAX to finish
    And I click "Category 2.2"
    And I click the element with css selector ".popup-info.open a.follow-term-link"
    And I wait for AJAX to finish
    And I click the element with css selector "body"
    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Following tags"

    Then I should see "Category 1"
    And I should see "Category 2.2"

    # Create topic with tag to trigger activity
    Given I am logged in as "content_creator"
    And I go to "node/add/topic"
    And I click radio button "News"
    And I fill in "Title" with "Topic with tags second"
    And I fill in the "edit-body-0-value" WYSIWYG editor with "This is a topic to check create activity"
    And I click radio button "Community"
    And I select "Category 1" from "Category 1"
    And I press "Settings"
    And I set alias as "topic-with-tags-second"
    And I press "Create topic"

    # Add tags to the existing topic to trigger activity.
    Then I go to "simple-topic"
    And I click "Edit content"
    And I select "Category 2.2" from "Category 2"
    And I press "Save"

    Then I should see "Simple topic" in the "Hero block" region
    And I should see "This is a topic to check update activity" in the "Main content"
    And I should see the link "Category 2.2" in the "Sidebar second"

    Then the cache has been cleared
    And I wait for the queue to be empty

    # Check notifications/stream/emails.
    Given I am logged in as "follower"
    And I am at "notifications"
    Then I should see "Mike Tyson created a topic Topic with tags second with the tag(s) that you follow."
    Then I should see "Mike Tyson added tag(s) you follow to a topic."
    Then I should have an email with subject "Notification from Open Social" and in the content:
      | content                                                                            |
      | Hi Jack Richer                                                                     |
      | Mike Tyson created a topic Topic with tags second with the tag(s) that you follow. |
      | the notification above is sent to you Immediately                                  |
    And I should have an email with subject "Notification from Open Social" and in the content:
      | content                                                     |
      | Hi Jack Richer                                              |
      | Mike Tyson added tag(s) you follow to a topic Simple topic. |
      | the notification above is sent to you Immediately           |
    And I am on the homepage
    Then I should see "Mike Tyson added tag(s) you follow to a topic."
    And I should see "Simple topic"
