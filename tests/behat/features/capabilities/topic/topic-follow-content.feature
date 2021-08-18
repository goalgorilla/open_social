@api @topic @stability @perfect @critical @DS-2311 @DS-7612 @stability-3 @topic-follow-content
Feature: Follow Content
  Benefit: In order receive (email) notification  when a new comments or reply has been placed
  Role: As a LU
  Goal/desire: I want to be able to subscribe to content

  Scenario: Follow content
    Given I am logged in as an "authenticated user"
    And I am on "user"
    And I click "Topics"
    And I click "Create Topic"
    When I fill in "Title" with "This is a follow topic"
    When I fill in the following:
      | Title | This is a follow topic |
     And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
    And I click radio button "Discussion"
    And I press "Create topic"
    And I should see "Topic This is a follow topic has been created."
    And I should see "This is a follow topic" in the "Hero block"
    And I should see "Body description text" in the "Main content"
    And I should see the link "Follow content"
    And I should not see the link "Unfollow content"
    And I click "Follow content"
    And I wait for AJAX to finish
    And I should see the link "Unfollow content"
    And I should not see the link "Follow content"
    And I click "Unfollow content"
    And I wait for AJAX to finish
    And I should see the link "Follow content"
    And I should not see the link "Unfollow content"

  @email-spool
  Scenario: Receive (email) notification of topic you are following
    Given users:
      | name   | mail               | status |
      | Dude 1 | dude_1@example.com | 1      |
      | Dude 2 | dude_2@example.com | 1      |
      | Dude 3 | dude_3@example.com | 1      |
    Given I am logged in as "Dude 1"
      And I am on "user"
      And I click "Topics"
      And I click "Create Topic"
    When I fill in "Title" with "This is a follow topic"
      And I fill in the following:
        | Title | This is a follow topic |
      And I fill in the "edit-body-0-value" WYSIWYG editor with "Body description text"
      And I click radio button "Discussion"
      And I press "Create topic"
    Then I should see "Topic This is a follow topic has been created."
      And I should see "This is a follow topic" in the "Hero block"
      And I should see "Body description text" in the "Main content"
      And I should see the link "Follow content"
      And I should not see the link "Unfollow content"
    When I click "Follow content"
      And I wait for AJAX to finish
    Then I should see the link "Unfollow content"
      And I should not see the link "Follow content"

    Given I am logged in as "Dude 2"
      And I am on "/all-topics"
    Then I should see "This is a follow topic"
    When I click "This is a follow topic"
    Then I should see the link "Follow content"
      And I should not see the link "Unfollow content"
    When I click "Follow content"
      And I wait for AJAX to finish
    Then I should see the link "Unfollow content"
      And I should not see the link "Follow content"

    Given I am logged in as "Dude 3"
      And I am on "/all-topics"
    Then I should see "This is a follow topic"
    When I click "This is a follow topic"
      And I fill in the following:
        | Add a comment | This is a test comment |
      And I press "Comment"
    Then I should see the success message "Your comment has been posted."
      And I should see the heading "Comments (1)" in the "Main content"
      And I should see "This is a test comment" in the "Main content"
      And I should see "second"
      And I should see "ago"

    # Check if the Dude 1 got a notification.
    Given I am logged in as "Dude 1"
      And I wait for the queue to be empty
      And I am at "notifications"
    Then I should not see text matching "Dude 3 commented on Dude 1's topic This is a follow topic you are following"
      And I should not have an email with subject "Notification from Open Social" and in the content:
        | content                                                                     |
        | Hi Dude 1                                                                   |
        | Dude 3 commented on Dude 1's topic This is a follow topic you are following |
        | This is a test comment                                                      |

    # Check if the Dude 2 got a notification.
    Given I am logged in as "Dude 2"
      And I am at "notifications"
    Then I should see text matching "Dude 3 commented on Dude 1's topic This is a follow topic you are following"
      And I should have an email with subject "Notification from Open Social" and in the content:
        | content                                                                     |
        | Hi Dude 2                                                                   |
        | Dude 3 commented on Dude 1's topic This is a follow topic you are following |
        | This is a test comment                                                      |
