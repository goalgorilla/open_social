@api
Feature: Delete Event
  Benefit: In order to delete content created
  Role: As a Verified
  Goal/desire: I want to delete Events

  @verified @perfect @critical
  Scenario: Successfully create event within a group
    Given I enable the module "automated_cron"
    And users:
      | name             | status | roles        |
      | Event enrollee 1 |      1 | verified     |
      | Event enrollee 2 |      1 | verified     |
      | Event manager    |      1 | verified     |
    And groups with non-anonymous owner:
      | label      | field_group_description | type           | langcode | field_flexible_group_visibility | field_group_allowed_join_method |
      | Test group | Public visibility       | flexible_group | en       | public                          | direct                          |
    And group members:
      | group      | user             |
      | Test group | Event enrollee 1 |
      | Test group | Event enrollee 2 |
      | Test group | Event manager    |
    And event content:
      | title                   | body                   | field_event_date | field_event_date_end | groups     | field_content_visibility    | author            |
      | Public event in group   | Body description text. | +2 days          | +3 days              | 6          | public                      | Event manager     |
    And event enrollees:
      | event                 | user             |
      | Public event in group | Event enrollee 1 |
      | Public event in group | Event enrollee 2 |
      | Public event in group | Event manager    |
    And I run cron
    And I wait for the queue to be empty
    And I am logged in as "Event manager"
    And I should see "Event manager created an event in Test group"
    And I should see "Public event in group"
    And I am viewing the group "Test group"
    And I click "Stream"
    And I should see "Event manager created an event in Test group"
    And I should see "Public event in group"

    #delete event
    When I click "Public event in group"
    And I click "Edit content"
    And I click "Delete"
    And I should see "Are you sure you want to delete the content item Public event in group?"
    And I should see "This action cannot be undone."
    And I press "Delete"
    And I wait for AJAX to finish

    Then I should see "The Event Public event in group has been deleted."
    And I should not see "Event manager created an event in Test group"
    And I am viewing the group "Test group"
    And I click "Stream"
    And I should not see "Event manager created an event in Test group"