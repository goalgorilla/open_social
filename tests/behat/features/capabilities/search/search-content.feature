@api @enterprise @search @stability @DS-498 @DS-673 @stability-3 @search-content
Feature: Search
  Benefit: In order to find specific content
  Role: As a LU
  Goal/desire: I want to search the site for content

  Scenario: Successfully search content
    Given "event" content:
      | title             | body          | status | field_content_visibility |
      | Event one         | Description   | 1      | public                   |
      | Event two         | Description   | 1      | public                   |
    And "topic" content:
      | title             | body          | status | field_content_visibility |
      | Topic one         | Description   | 1      | public                   |
      | Topic two         | Description   | 1      | community                |
      | Topic three       | Description   | 1      | community                |
      | Topic four        | Description   | 1      | community                |
      | Topic five        | Description   | 1      | community                |
      | Topic six         | Description   | 1      | community                |
      | Topic seven       | Description   | 1      | community                |
      | Topic eight       | Description   | 1      | community                |
      | Topic nine        | Description   | 1      | community                |
      | Topic ten         | Description   | 1      | community                |
      | Topic eleven      | Description   | 1      | community                |
      | Topic twelve      | Description   | 1      | community                |
      | Topic thirteen    | Description   | 1      | community                |
    And Search indexes are up to date
    And I am on "search/content"
    When I fill in the following:
      | search_input | one |
    And I press "Search"
    And I should see the heading "Search" in the "Hero block" region
    And I should see "Event one"
    And I should not see "Event second"
    And I should see "Topic one"
    And I should not see "Topic two"

    # Now test with "authenticated user"
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

    # Test the pager.
    When I am on "search/content"
    And I fill in the following:
      | search_input | topic |
    And I press "Search"
    And I click the xth "0" element with the css ".pager-nav .pager__item--next"
    And I should see "Topic thirteen"
    And I fill in the following:
      | search_input | four |
    And I press "Search"
    And I should see "Topic four"

        # Scenario: Successfully filter search results
#    When I select "topic" from "Content type"
#    And I press "Filter" in the "Sidebar second"
#    And I should see "Topic one"
#    And I should not see "Event one"