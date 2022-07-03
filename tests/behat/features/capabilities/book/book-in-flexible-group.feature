@api @group @book @book-in-flexible-group
Feature: Book page in Flexible Group
  Benefit: Correct behaviour for books in flexible groups
  Role: As a verified
  Goal/desire: I want to add and see books in flexible groups

  Scenario: See public book in a flexible group
    Given I enable the module "social_flexible_group_book"
    Then the cache has been cleared
    Given users:
      | name        | status | pass        | roles          |
      | Creator     | 1      | Creator     | contentmanager |
      | SeeUser     | 1      | SeeUser     | verified       |
    Given groups:
      | title                 | description      | author   | type           | language | alias               | field_content_visibility |
      | Test Flexible Group   | Description text | Creator  | flexible_group | en       | test-flexible-group | public                   |

    And I am logged in as "Creator"
    Then I go to "/my-groups"
    Then I click "Test Flexible Group"

    # Create book page in flexible group.
    Then I click "Books"
    And I click "Create Book"
    When I fill in the following:
      | title[0][value]          | Book #1 |
      | field_content_visibility | public  |
    And I select "- Create a new book -" from "Book"
    And I wait for AJAX to finish
    And I press "Create book page"
      Then I should see "Book #1"

    # Create a sub-book page in flexible group.
    When I go to "/my-groups"
    And I click "Test Flexible Group"
    Then I click "Books"
    Then I click "Create Book"
    And I fill in the following:
      | title[0][value]          | Sub-Book #1-1 |
      | field_content_visibility | public    |
    And I select "Book #1" from "Book"
    And I wait for AJAX to finish
    And I press "Create book page"
      Then I should see "Sub-Book #1-1"

    # Check view for verified user.
    Given I am logged in as "SeeUser"
    Then I go to "/all-groups"
    Then I click "Test Flexible Group"
    When I click "Books"
      Then I should see "Book #1"
      And I should not see "Sub-Book #1-1"
    When I click "Book #1"
      Then I should see "Book #1"
      And I should see "Sub-Book #1-1"

    # Check view for anonymous user.
    Given I am an anonymous user
    Then I go to "/all-groups"
    Then I click "Test Flexible Group"
    When I click "Books"
      Then I should see "Book #1"
      And I should not see "Sub-Book #1-1"
    When I click "Book #1"
      Then I should see "Book #1"
      And I should see "Sub-Book #1-1"
