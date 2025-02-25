@api
Feature: Search
  Benefit: In order to find specific multilingual content
  Role: As a LU
  Goal/desire: I want to search the site for multilingual content

  Scenario: Successfully search multilingual content
    Given I enable the module "social_language"
    And I enable the module "social_content_translation"

    And the following languages are available:
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
    And I am logged in as "user-fr"

    When I am on "fr/search/content"

    Then I should see "Topic-fr"

    And I am on "fr/search/all"
    And I should see "Topic-fr"

    # Check search results for user with "German" default language.
    And I am logged in as "user-de"
    And I am on "de/search/content"
    And I should see "Topic-de"

    And I am on "de/search/all"
    And I should see "Topic-de"

    # Check search results for user with "Italy" default language.
    And I am logged in as "user-it"
    And I am on "it/search/content"
    And I should see "Topic-it"

    And I am on "it/search/all"
    And I should see "Topic-it"
