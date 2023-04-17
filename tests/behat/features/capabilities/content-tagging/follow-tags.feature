@api @javascript @follow-taxonomy @stability @YANG-5609 @stability-4 @follow-tags
Feature: Follow Tags
  Benefit: Provide follow tag
  Role: As LU
  Goal/Desire: I want to follow tags and receive notifications about content with tags

  # TODO: A user should be able to follow "Category 1" to get a notification from any child.
  @email-spool
  Scenario: Successfully follow added tags
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
      | name            | status | roles          | field_profile_first_name | field_profile_last_name |
      | follower        | 1      | verified       | Jack                     | Richer                  |
      | content_creator | 1      | contentmanager | Mike                     | Tyson                   |

    # Create topic to work with it later.
    Given topics:
      | author          | title                  | field_topic_type  | body                                     | field_content_visibility | field_social_tagging       |
      | content_creator | Simple topic           | News              | This is a topic to check update activity | community                |                            |
      | content_creator | Topic with tags        | News              | This is a topic for follow tag feature   | community                | Category 1.1, Category 2.2 |
    And I am logged in as "follower"

    When I am viewing the topic "Topic with tags"
    And I click "Category 1.1"
    And I click the element with css selector ".popup-info.open a.follow-term-link"
    And I wait for AJAX to finish
    And I click "Category 2.2"
    And I click the element with css selector ".popup-info.open a.follow-term-link"
    And I wait for AJAX to finish

    And I click the xth "0" element with the css ".navbar-nav .profile"
    And I click "Following tags"

    Then I should see "Category 1.1"
    And I should see "Category 2.2"

    # We must log the user out to ensure they have no sessions. Emails don't get
    # sent to users that have active sessions.
    And I logout

    # Create topic with tag to trigger activity
    Given topics:
      | author          | title                  | field_topic_type  | body                                     | field_content_visibility | field_social_tagging   |
      | content_creator | Topic with tags second | News              | This is a topic to check create activity | community                | Category 1.1           |

    # Add tags to the existing topic to trigger activity.
    And I am logged in as "content_creator"
    And I am editing the topic "Simple topic"
    And I select "Category 2.2" from "Category 2"
    And I press "Save"

    When the cache has been cleared
    And I wait for the queue to be empty

    # Check notifications/stream/emails.
    Given I am logged in as "follower"
    And I am at "notifications"
    Then I should see "Mike Tyson created a topic Topic with tags second with the tag(s) that you follow."
    Then I should see "Mike Tyson added tag(s) you follow to a topic."
    Then I should have an email with subject "Someone added content you might be interested in" and in the content:
      | content                                                                            |
      | Hi Jack Richer                                                                     |
      | Mike Tyson created a topic Topic with tags second with the tag(s) that you follow. |
      | the notification above is sent to you Immediately                                  |
    And I should have an email with subject "Someone added content you might be interested in" and in the content:
      | content                                                     |
      | Hi Jack Richer                                              |
      | Mike Tyson added tag(s) you follow to a topic Simple topic. |
      | the notification above is sent to you Immediately           |
    And I am on the homepage
    Then I should see "Mike Tyson added tag(s) you follow to a topic."
    And I should see "Simple topic"
