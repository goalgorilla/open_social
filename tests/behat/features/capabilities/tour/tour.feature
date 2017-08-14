@api @tour @critical @DS-3710
Feature: Take the tour
  Benefit: In order to get to know the platform
  Role: As a LU
  Goal/desire: I want to take the tour

Scenario: Successfully take the tour and see all pop-ups
  Given I am logged in as an "authenticated user"
    And I enable the tour setting
    # Necessary, because homepage tour is already marked as seen
    And I reset tour "social-home"
    And I am on the homepage
   Then I should see "Welcome on the home page stream!"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Welcome on the home page stream!"
    And I should not see "Don't show tips like this anymore"

  Given I am on "all-topics"
   Then I should see "Use the topic filter to filter on topic type."
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Use the topic filter to filter on topic type."
    And I should not see "Don't show tips like this anymore"

  Given I am on "community-events"
   Then I should see "Use the event filter if you only want to see either upcoming or past events"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Use the event filter if you only want to see either upcoming or past events"
    And I should not see "Don't show tips like this anymore"

  Given I am on "all-groups"
   Then I should see "Use the plus button in the navigation bar to create a new topic, or to start a new event or group"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Use the plus button in the navigation bar to create a new topic, or to start a new event or group"
    And I should not see "Don't show tips like this anymore"

  Given I am on "/user"
   Then I should see "Welcome on your profile stream!"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Welcome on your profile stream!"
    And I should not see "Don't show tips like this anymore"

   When I click "Information"
   Then I should see "Click on the explore button to browse to all content in the community"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Click on the explore button to browse to all content in the community"
    And I should not see "Don't show tips like this anymore"

    Given topic content:
      | title         | field_topic_type | status | field_content_visibility |
      | Tour Topic 1  | Blog             | 1      | public                   |
    And I am on the homepage
   Then I should see "Tour Topic 1"
   When I click "Tour Topic 1"
   Then I should see "Follow an event or topic so you are always up-to-date! You will receive a notification whenever any activity takes place for this event or topic"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Follow an event or topic so you are always up-to-date! You will receive a notification whenever any activity takes place for this event or topic"
    And I should not see "Don't show tips like this anymore"

  Given I am on "/group/add/open_group"
    And I fill in "Title" with "Tour group"
    And I fill in the "edit-field-group-description-0-value" WYSIWYG editor with "Tour test group"
    And I press "Save"
    And I should see "Tour group" in the "Main content"

   When I click "Tour group"
   Then I should see "Welcome on this group page!"
    And I should see "Don't show tips like this anymore"
   When I close the open tip
   Then I should not see "Welcome on this group page!"
    And I should not see "Don't show tips like this anymore"

@nomoretips
Scenario: Stop showing me tips
   When I am logged in as an "authenticated user"
    And I enable the tour setting
    And I am on "all-topics"
   Then I should see "Use the topic filter to filter on topic type."
    And I should see "Don't show tips like this anymore"
   When I click "Don't show tips like this anymore"
   Then I should see "You will not see tips like this anymore."

  Given I am on "community-events"
   Then I should not see "Use the event filter if you only want to see either upcoming or past events"
    And I should not see "Don't show tips like this anymore"
