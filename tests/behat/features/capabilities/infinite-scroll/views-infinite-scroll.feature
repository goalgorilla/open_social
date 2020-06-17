@api @stability @event @views-infinite-scroll

Feature: Enable social infinite scroll feature
  Benefit: In order to see infinite scroll instead of default pager
  Role: LU
  Goal/desire: I want to use infinite scroll for specific views

  Scenario: Successfully enable social_views_infinite_scroll module
    Given users:
      | name             | mail                    | status | roles       |
      | behatsitemanager | sitemanager@example.com | 1      | sitemanager |

    Given I enable the module "social_views_infinite_scroll"
    Given I am logged in as "behatsitemanager"
    When I am on "/admin/config/opensocial/infinite-scroll"
    Then I should see checked the box "(Upcoming) Community events"

  Scenario: Successfully use infinite scroll
    Given "event" content:
      | title    | body        | field_event_date | field_event_date_end | Time     | Location name |
      | Event 1  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 2  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 3  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 4  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 5  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 6  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 7  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 8  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 9  | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 10 | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 11 | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 12 | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 13 | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
      | Event 14 | Description | +80 days         | +90 days             | 11:00:00 | Technopark    |
    Given I am logged in as an "authenticated user"
    And I am on "/community-events"
    And I should see "Load More"
    And I should not see "Event 5"
    When I click "Load More"
    And I wait for AJAX to finish
    And I should see "Event 5"
