@api @search @stability @DS-498 @DS-673
Feature: Search
  Benefit: In order to find specific content
  Role: As a LU
  Goal/desire: I want to search the site for content

  Scenario: Successfully search content
    Given "event" content:
      | title             | body          |
      | Event one         | Description   |
      | Event two         | Description   |
    And "topic" content:
      | title             | body          |
      | Topic one         | Description   |
      | Topic two         | Description   |
    And I am logged in as an "authenticated user"
    #@TODO: Change "search/content" to the homepage when search block will be in the header
    And I am on "search/content"
    When I fill in the following:
      | search_input | one |
    And I press "Search"
    And I should see the heading "Search" in the "Hero block" region
    And I should see "Event one" in the "Main content"
    And I should see "Topic one"
    And I should not see "Event second"
    # Scenario: Successfully filter search results
#    When I select "topic" from "Content type"
#    And I press "Filter" in the "Sidebar second"
#    And I should see "Topic one"
#    And I should not see "Event one"
