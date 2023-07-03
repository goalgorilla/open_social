@api @javascript
Feature: Filter community events
  Benefit: Ability to filter events on All events page
  Role: As a Verified
  Goal/desire: I want to filter events that have all day checkbox activated

  Scenario: Successfully use filters for ongoing and upcoming events with today's end date
    Given users:
      | name               | mail             | status | timezone | roles    |
      | regular_user       | some@example.com | 1      | UTC      | verified |
    And events with non-anonymous author:
      | title                                | body        | field_content_visibility | field_event_date    | field_event_date_end | field_event_all_day | langcode |
      | My awesome upcoming pepsi-cola party | lorem ipsum | public                   | today               | today                | 1                   | en       |
    And I am logged in as "regular_user"
    When I am on the event overview
    And I click radio button "Ongoing and upcoming events"
    And I press "Filter"
    Then I should see "My awesome upcoming pepsi-cola party"

  Scenario: Successfully use filters for ongoing and upcoming events
    Given users:
      | name               | mail             | status | timezone | roles    |
      | regular_user       | some@example.com | 1      | UTC      | verified |
    And events with non-anonymous author:
      | title                           | body        | field_content_visibility | field_event_date    | field_event_date_end | field_event_all_day	 | langcode |
      | My awesome upcoming kvass party | lorem ipsum | public                   | + 1 year            | + 1 year             | 1                    | en       |
    And I am logged in as "regular_user"
    When I am on the event overview
    And I click radio button "Ongoing and upcoming events"
    And I press "Filter"
    Then I should see "My awesome upcoming kvass party"

  Scenario: Successfully use filters for past events
    Given users:
      | name               | mail             | status | timezone | roles    |
      | regular_user       | some@example.com | 1      | UTC      | verified |
    And events with non-anonymous author:
      | title                                | body              | field_content_visibility | field_event_date    | field_event_date_end | field_event_all_day	 | langcode |
      | My beautiful last water kvass party  | lorem ipsum       | public                   | - 1 year            | - 1 year             | 1                     | en       |
    And I am logged in as "regular_user"
    When I am on the event overview
    And I click radio button "Past events"
    And I press "Filter"
    Then I should see "My beautiful last water kvass party"
