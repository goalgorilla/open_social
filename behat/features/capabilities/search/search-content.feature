@api @search @stability @DS-498 @DS-673
Feature: Search
  Benefit: In order to find specific content
  Role: As a LU
  Goal/desire: I want to search the site for content

  Scenario: Successfully search content
    Given "event" content:
      | title             | body          | field_event_date    | field_event_date_end  |
      | Event one         | Description   | 2016-04-14T12:00:00 | 2020-04-14T12:00:00   |
      | Event two         | Description   | 2016-04-14T12:00:00 | 2020-04-14T12:00:00   |
    And "topic" content:
      | title             | body          | field_topic_type    |
      | Topic one         | Description   | Blog                |
      | Topic two         | Description   | Blog                |
    And I am logged in as an "authenticated user"
    #@TODO: Change "search/content" to the homepage when search block will be in the header
    And I am on "search/content"
    When I fill in the following:
      | search_input | one |
    And I press "Search"
    And I should see "Search content" in the "Page title block"
    And I should see "Event one" in the "Main content"
    And I should see "Topic one"
    And I should not see "Event two"
    # Scenario: Successfully filter search results
    When I select "topic" from "Content type"
    And I press the "Filter" button
    And I should see "Topic one"
    And I should not see "Event one"
