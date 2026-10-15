@api
Feature: Profile statistic counts
  Benefit: In order to see how active a member is
  Role: LU
  Goal/desire: See event and topic totals on the profile statistic block

  Scenario: Event and topic counts update after the user creates content
    Given users:
      | name             | mail                           | status | roles    |
      | statistic_owner  | statistic_owner@example.com    | 1      | verified |
    And I am logged in as "statistic_owner"
    And I am on "/user"
    And I should see "0" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-quantity" element
    And I should see "events" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-text" element
    And I should see "0" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-quantity" element
    And I should see "topics" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-text" element

    When topic content:
      | author          | title                 | body                   | status | field_content_visibility | field_topic_type | langcode |
      | statistic_owner | Statistic owner topic | Body description text  | 1      | public                   | News             | en       |
    And events:
      | author          | title                 | body                   | field_event_date    | field_event_date_end | field_content_visibility | status | langcode |
      | statistic_owner | Statistic owner event | Body description text  | 2100-01-01T12:00:00 | 2100-01-01T12:00:00  | public                   | 1      | en       |
    And I am on "/user"

    Then I should see "1" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-quantity" element
    And I should see "event" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-text" element
    And I should see "1" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-quantity" element
    And I should see "topic" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-text" element

    And I am editing the event "Statistic owner event"
    And I click "Delete"
    And I press "Delete"
    And I am on "/user"

    And I should see "0" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-quantity" element
    And I should see "events" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(1) .card__counter-text" element
    And I should see "1" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-quantity" element
    And I should see "topic" in the "#block-socialblue-profile-statistic-block .card__counter li:nth-child(2) .card__counter-text" element
