@api @enterprise @search @stability @DS-498 @DS-673 @stability-3 @search-multilingual
Feature: Search
  Benefit: In order to find specific multilingual content
  Role: As a LU
  Goal/desire: I want to search the site for multilingual content

  Scenario: Successfully search multilingual content
    Given I enable the module "social_language"
    And I enable the module "social_content_translation"

    Given the following languages are available:
      | languages |
      | fr       |
      | de       |
      | it       |

    And users:
      | name     | status | preferred_langcode |
      | user-fr  | 1      | fr       |
      | user-de  | 1      | de       |
      | user-it  | 1      | it       |

    And "topic" content:
      | title            | body                 | status | field_content_visibility | langcode |
      | Topic-fr         | Topic description FR | 1      | public                   | fr       |
      | Topic-de         | Topic description DE | 1      | community                | de       |
      | Topic-it         | Topic description IT | 1      | community                | it       |

    And Search indexes are up to date

    # Check search results for user with "France" default language.
    Given I am logged in as "user-fr"
    When I am on "fr/search/content"
    Then I should see "Topic-fr"

    When I am on "fr/search/all"
    Then I should see "Topic-fr"

    # Check search results for user with "German" default language.
    Given I am logged in as "user-de"
    When I am on "de/search/content"
    Then I should see "Topic-de"

    When I am on "de/search/all"
    Then I should see "Topic-de"

    # Check search results for user with "Italy" default language.
    Given I am logged in as "user-it"
    When I am on "it/search/content"
    Then I should see "Topic-it"

    When I am on "it/search/all"
    Then I should see "Topic-it"
