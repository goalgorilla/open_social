@api @group @book @book-in-flexible-group
Feature: Book page in Flexible Group
  Benefit: Correct behaviour for books in flexible groups
  Role: As a verified
  Goal/desire: I want to add and see books in flexible groups

  Background:
    Given I enable the module "social_flexible_group_book"
    And the cache has been cleared

  Scenario: Flexible group provides CTA for book creation
    Given I am logged in as a user with the contentmanager role
    And groups owned by current user:
      | label                 | field_group_description | enable_books | type           | langcode | path                 | field_flexible_group_visibility | field_group_allowed_visibility |
      | Test Flexible Group   | Description text        | 1            | flexible_group | en       | /test-flexible-group | public                          | public                         |

    When I am viewing the group "Test Flexible Group"
    And I click "Books"
    And I click "Create Book"

    Then I should be on the book creation form
    And the group "Test Flexible Group" should be preselected

  Scenario: Can create a book in a flexible Group
    Given I am logged in as a user with the contentmanager role
    And groups owned by current user:
      | label                 | field_group_description | enable_books | type           | langcode | path                 | field_flexible_group_visibility | field_group_allowed_visibility |
      | Test Flexible Group   | Description text        | 1            | flexible_group | en       | /test-flexible-group | public                          | public                         |

    When I create a book using its creation page:
      | Title       | Book #1               |
      | Description | It's the best         |
      | Group       | Test Flexible Group   |
      | Visibility  | Public                |
      | Book        | - Create a new book - |

    Then I should see the book I just created

  Scenario: Can create a sub-book in a flexible group
    Given I am logged in as a user with the contentmanager role
    And groups owned by current user:
      | label                 | field_group_description | enable_books | type           | langcode | path                 | field_flexible_group_visibility | field_group_allowed_visibility |
      | Test Flexible Group   | Description text        | 1            | flexible_group | en       | /test-flexible-group | public                          | public                         |
    And books:
      | title                 | description      | group               | field_content_visibility |
      | Book #1               | It's the best    | Test Flexible Group | public                   |

    When I create a book using its creation page:
      | Title       | Sub-Book #1           |
      | Description | Just a tribute        |
      | Group       | Test Flexible Group   |
      | Visibility  | Public                |
      | Book        | Book #1               |

    # @todo This should be "Then I should see the book I just created"
    # but there's a bug that causes the success message not to be shown.
    Then I should see "Sub-Book #1" in the "Hero block"
#    Then I should see the book I just created

  Scenario: Group books overview only shows top-level books
    Given I am logged in as a user with the contentmanager role
    And groups owned by current user:
      | label                 | field_group_description | enable_books | type           | langcode | path                | field_flexible_group_visibility | field_group_allowed_visibility |
      | Test Flexible Group   | Description text        | 1            | flexible_group | en       | /test-flexible-group | public                          | public                         |
    And books:
      | title    | description         | group               | book    | parent  | field_content_visibility |
      | Book #1  | It's the best       | Test Flexible Group |         |         | public                   |
      | Sub #1   | It's the best Again | Test Flexible Group | Book #1 | Book #1 | public                   |

    When I am viewing the group "Test Flexible Group"
    And I click "Books"

    Then I should see "Book #1"
    And I should not see "Sub #1"
